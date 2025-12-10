<?php
/** contact.php
 * Formulaire de contact anti-spam :
 * - Honeypot (champ caché que les bots remplissent)
 * - Tempo (temps minimal avant envoi)
 * - Token CSRF (nonce en session)
 * - Vérification CleanTalk API (check_message)
 */

// don't display ads for this page
$noAds = true;

$pageTitle = $label_contact." - ". $WebsiteName;

/* ====== CONFIG ====== */
const CLEANTALK_API_KEY     = 'YOUR_CLEANTALK_API_KEY'; // <-- remplace
const MIN_SUBMIT_SECONDS    = 3;       // Temps min entre affichage et submit
const MAX_SUBMIT_SECONDS    = 3600;    // Timeout d’une page affichée > 1h
const SEND_MAIL             = true;    // Passe à false si tu ne veux pas d’email
const MAIL_TO               = 'contact@example.com'; // <-- destinataire
const MAIL_SUBJECT          = 'Nouveau message via formulaire';

/* ====== HELPERS ====== */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function post_string(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}

function valid_email(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function now(): int { return time(); }

/**
 * Appel simple à l’API CleanTalk (check_message)
 * Docs : https://moderate.cleantalk.org/api2.0 (méthode 'check_message')
 * Retourne [allow(bool), comment(string)]
 */

function cleantalk_check(string $email, string $message, int $submit_delta): array {
    $endpoint = 'https://moderate.cleantalk.org/api2.0';

    $payload = [
        'method_name'  => 'check_message',
        'auth_key'     => "9aja3yhamu5ybuz",              // ta clé
        'sender_email' => $email,
        'message'      => $message,
        'sender_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
        'agent'        => 'custom-php-contact-form-1.0',  // identifiant libre
        'submit_time'  => max(0, $submit_delta),          // secondes écoulées
        // 'js_on'     => 1, // (optionnel) si tu poses un flag JS côté front
        // 'sender_info'=> json_encode(['site_url'=>$_SERVER['HTTP_HOST']??'']),
        // 'x_forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null, // si CF/proxy
    ];

    $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

    // cURL si dispo
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $jsonBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return [false, 'CleanTalk unreachable (cURL): '.$err];
        }
    } else {
        // Fallback stream
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $jsonBody,
                'timeout' => 8,
            ]
        ]);
        $resp = @file_get_contents($endpoint, false, $context);
        if ($resp === false) {
            return [false, 'CleanTalk unreachable (fopen)'];
        }
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return [false, 'CleanTalk invalid JSON'];
    }

    // Gestion des erreurs API
    if (!empty($data['error_no']) || !empty($data['error_message'])) {
        $msg = 'CleanTalk error';
        if (!empty($data['error_no']))       $msg .= ' #'.$data['error_no'];
        if (!empty($data['error_message']))  $msg .= ' - '.$data['error_message'];
        return [false, $msg];
    }

    $allow   = isset($data['allow']) ? (int)$data['allow'] : 0;
    $comment = (string)($data['comment'] ?? '');
    return [$allow === 1, $comment];
}



/* ====== PROCESS ====== */
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = post_string('email');
    $message    = post_string('message');
    $honeypot   = post_string('company');     // <== HONEYPOT
    $token      = post_string('token');       // <== CSRF
    $form_ts    = (int)($_POST['form_ts'] ?? 0);
    $delta      = now() - $form_ts;

    // 1) CSRF
    if (!check_csrf($token)) {
        $errors[] = "Requête invalide (token).";
    }

    // 2) Tempo
    if ($delta < MIN_SUBMIT_SECONDS) {
        $errors[] = "Soumission trop rapide.";
    }
    if ($delta > MAX_SUBMIT_SECONDS) {
        $errors[] = "Formulaire expiré. Recharge la page.";
    }

    // 3) Honeypot (doit rester vide)
    if ($honeypot !== '') {
        $errors[] = "Spam détecté (honeypot).";
    }

    // 4) Validation champs
    if (!valid_email($email)) {
        $errors[] = "Email invalide.";
    }
    if ($message === '' || mb_strlen($message) < 5) {
        $errors[] = "Message trop court.";
    }
    if (mb_strlen($message) > 5000) {
        $errors[] = "Message trop long.";
    }

    // 5) CleanTalk si pas d’erreurs
    if (!$errors) {
        [$allowed, $ct_comment] = cleantalk_check($email, $message, $delta);
        if (!$allowed) {
            $errors[] = "Rejeté par CleanTalk: ".($ct_comment ?: 'spam détecté');
        }
    }

    // 6) Enregistrement en base (remplace l'envoi d'email)
    if (!$errors) {
        // Au besoin, récup IP réelle derrière Cloudflare
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 512);
        $ref= substr((string)($_SERVER['HTTP_REFERER'] ?? ''),       0, 512);

        // $allowed et $ct_comment viennent de cleantalk_check()
        // $delta = submit_time_seconds ; $honeypot !== '' si piège
        $stmt = $pdo->prepare(
            "INSERT INTO contact_messages
            (email, message, sender_ip, user_agent, referrer,
            submit_time_seconds, honeypot_hit, csrf_ok, ct_allow, ct_comment)
            VALUES
            (:email, :message, :ip, :ua, :ref,
            :submit_time, :honeypot, :csrf_ok, :ct_allow, :ct_comment)"
        );
        $stmt->execute([
            ':email'       => $email,
            ':message'     => $message,
            ':ip'          => $ip,
            ':ua'          => $ua,
            ':ref'         => $ref,
            ':submit_time' => max(0, (int)$delta),
            ':honeypot'    => ($honeypot !== '') ? 1 : 0,
            ':csrf_ok'     => 1,                 // ici on est passé les checks
            ':ct_allow'    => $allowed ? 1 : 0,
            ':ct_comment'  => $ct_comment ?: null,
        ]);

        // Si tu veux refuser l’affichage "OK" quand CleanTalk dit spam :
        if (!$allowed) {
            $errors[] = "Rejeté par CleanTalk: ".($ct_comment ?: 'spam détecté');
        } else {
            $success = true;
            // Regénère le token pour éviter double-submit
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }    
}

/* ====== VIEW ====== */
$form_ts = now();
$token   = csrf_token();
?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>
    
<style>
/* Honeypot : reste visible dans le DOM (meilleur que type=hidden) mais invisible à l’œil */
.hp-wrap { position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden; }
form { max-width: 560px; margin: 2rem auto; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
label { display:block; margin: .5rem 0 .25rem; font-weight:600; }
input[type="email"], textarea { width:100%; padding:.6rem .8rem; border:1px solid #ccc; border-radius:6px; font-size:16px; }
button { margin-top: .8rem; padding:.7rem 1rem; border:0; border-radius:8px; font-weight:700; cursor:pointer; }
button[type=submit] { background:#111; color:#fff; }
.alert { max-width:560px; margin: 1rem auto; padding:.8rem 1rem; border-radius:8px; }
.alert.error { background:#ffe8e8; color:#7a0000; }
.alert.success { background:#e8fff0; color:#005e2e; }
.small { color:#666; font-size:12px; }
</style>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<section id="categories" class="py-6 sm:py-8">
    <div class="mb-5 flex items-center justify-between">
        
                
        <div class="mx-auto max-w-2xl">
            <h2 class="text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_contact, ENT_QUOTES, 'UTF-8') ?></h2>
            <?php if ($errors): ?>
                <div class="alert error">
                    <strong>Error :</strong>
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif ($success): ?>
                <div class="alert success">
                    <?=$label_contact_success;?>
                </div>
            <?php endif; ?>

            <div class="introtext">
                <p><?=$label_intro_contact;?></p>
            </div>

        <form method="post" action="">
            <!-- CSRF -->
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <!-- Tempo -->
            <input type="hidden" name="form_ts" value="<?= (int)$form_ts ?>">

            <!-- Honeypot (ne pas mettre type=hidden pour piéger les bots “c**”) -->
            <div class="hp-wrap" aria-hidden="true">
                <label for="company">Company</label>
                <input autocomplete="off" tabindex="-1" type="text" id="company" name="company" value="">
            </div>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="jhon@gmail.com" inputmode="email" autocapitalize="off" autocomplete="email">

            <label for="message"></label>
            <textarea id="message" name="message" rows="7" required placeholder=" ..." class="px-4 py-2"></textarea>

            <button type="submit" class="px-4 py-2"><?=$label_contact_send;?></button>
            <p class="small"><?=$label_contact_nospam;?></p>
        </form>

        </div>
    </div>
</section>    
</main>


    <?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>

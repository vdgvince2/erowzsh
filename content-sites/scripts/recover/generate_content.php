#!/usr/bin/env php
<?php
/**
 * generate_content.php — Génère le contenu AI pour les recovered_pages pending
 *
 * Usage :
 *   php generate_content.php --site-id=1 [--country=IE] [--limit=50]
 *   php generate_content.php --domain=minderlist.com --country=IE
 *
 * Utilise l'API OpenAI avec le modèle gpt-5-nano.
 * Lit OPENAI_API_KEY depuis .env ou variable d'environnement.
 */

define('CS_CLI', true);

$rootDir = dirname(__DIR__, 2);

// ── Parse args ────────────────────────────────────────────────────────────────

$opts    = getopt('', ['site-id:', 'domain:', 'country:', 'limit:']);
$siteId  = isset($opts['site-id']) ? (int)$opts['site-id'] : null;
$domain  = $opts['domain'] ?? null;
$country = strtoupper($opts['country'] ?? 'IE');
$limit   = (int)($opts['limit'] ?? 50);

if (!$siteId && !$domain) {
    fwrite(STDERR, "Usage: php generate_content.php --site-id=N [--country=IE] [--limit=50]\n");
    exit(1);
}

// ── Charge .env ───────────────────────────────────────────────────────────────

$envFile = $rootDir . '/../.env'; // remonte depuis content-sites/
if (!file_exists($envFile)) $envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (!getenv($k)) putenv("{$k}={$v}");
    }
}

$openaiKey = getenv('OPENAI_API_KEY');
if (!$openaiKey) { fwrite(STDERR, "OPENAI_API_KEY manquante.\n"); exit(1); }

// ── DB ────────────────────────────────────────────────────────────────────────

$pdo = new PDO(
    'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1') . ';port=' . (getenv('DB_PORT') ?: '8889') . ';dbname=' . (getenv('DB_NAME') ?: 'CONTENT') . ';charset=utf8mb4',
    getenv('DB_USER') ?: '', getenv('DB_PASS') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// ── Charge le site ────────────────────────────────────────────────────────────

if ($siteId) {
    $stmt = $pdo->prepare('SELECT * FROM recovered_sites WHERE id = ? LIMIT 1');
    $stmt->execute([$siteId]);
} else {
    $stmt = $pdo->prepare('SELECT * FROM recovered_sites WHERE domain = ? AND status = "active" LIMIT 1');
    $stmt->execute([$domain]);
}
$site = $stmt->fetch();
if (!$site) { fwrite(STDERR, "Site introuvable.\n"); exit(1); }

$siteId = (int)$site['id'];
$lang   = $site['language'];
echo "[AI] Site: {$site['domain']} | Langue: $lang | Limite: $limit\n";

// ── Pages pending ─────────────────────────────────────────────────────────────

$stmtPending = $pdo->prepare('
    SELECT id, title, slug, original_path FROM recovered_pages
    WHERE site_id = ? AND status = "pending"
    ORDER BY id ASC
    LIMIT ' . $limit
);
$stmtPending->execute([$siteId]);
$pages = $stmtPending->fetchAll();

// Debug : compte total des pages par statut
$stmtStats = $pdo->prepare('SELECT status, COUNT(*) AS n FROM recovered_pages WHERE site_id = ? GROUP BY status');
$stmtStats->execute([$siteId]);
echo "[DEBUG] Pages par statut : ";
foreach ($stmtStats->fetchAll() as $row) echo $row['status'] . '=' . $row['n'] . ' ';
echo "\n";

if (empty($pages)) {
    echo "Aucune page en attente de génération.\n";
    exit(0);
}

echo "[AI] " . count($pages) . " pages à générer\n";

// ── Prompts ───────────────────────────────────────────────────────────────────

$languageNames = [
    'EN' => 'English', 'FR' => 'French', 'DE' => 'German', 'IT' => 'Italian',
    'NL' => 'Dutch', 'ES' => 'Spanish', 'PT' => 'Portuguese',
];
$langName = $languageNames[$lang] ?? 'English';

function build_system_prompt(string $langName, string $domain): string
{
    return "You are a helpful content writer for the website {$domain}.
Write in {$langName} only. Output ONLY valid HTML — no markdown, no code fences.
Structure:
1. <h1> — article title (based on the given title)
2. <p class=\"article-intro\"> — engaging 2-sentence introduction
3. <h2> — main section heading
4. 3-4 <p> paragraphs of informative content
5. <h2> — practical tips or key points
6. <ul> with 4-6 <li> items
7. <h2> — conclusion
8. <p> — conclusion paragraph

Rules:
- Write at least 400 words of real content.
- Do NOT use placeholder text.
- Be informative, helpful, and natural.
- Keep a friendly, expert tone.";
}

function build_user_prompt(string $title, string $lang): string
{
    return "Write an article titled: \"{$title}\"\nLanguage: {$lang}";
}

// ── Appel API OpenAI ──────────────────────────────────────────────────────────

$stmtUpdate = $pdo->prepare('
    UPDATE recovered_pages SET content_html = ?, status = ?, error_msg = ?, updated_at = NOW()
    WHERE id = ?
');

$generated = 0;
$errors    = 0;
$systemPrompt = build_system_prompt($langName, $site['domain']);

foreach ($pages as $page) {
    echo "  [{$page['id']}] {$page['title']} ... ";

    $payload = json_encode([
        'model'    => 'gpt-5-nano',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => build_user_prompt($page['title'], $langName)],
        ],
        'max_completion_tokens' => 6000,  // reasoning model : tokens split entre raisonnement + output
        'reasoning_effort'      => 'low', // minimise les tokens de raisonnement interne
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
        ],
    ]);

    $resp      = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "HTTP $httpCode | ";

    if (!$resp) {
        $errMsg = "cURL error: $curlError";
        echo "ERREUR: $errMsg\n";
        $stmtUpdate->execute([null, 'error', mb_substr($errMsg, 0, 500), $page['id']]);
        $errors++;
        continue;
    }

    echo "resp_len=" . strlen($resp) . " | ";
    $data = json_decode($resp, true);

    if ($httpCode !== 200) {
        $errMsg = $data['error']['message'] ?? "HTTP $httpCode — " . mb_substr($resp, 0, 300);
        echo "ERREUR: $errMsg\n";
        $stmtUpdate->execute([null, 'error', mb_substr($errMsg, 0, 500), $page['id']]);
        $errors++;
        continue;
    }

    $finishReason    = $data['choices'][0]['finish_reason']         ?? 'unknown';
    $reasoningTokens = $data['usage']['completion_tokens_details']['reasoning_tokens'] ?? '?';
    $outputTokens    = ($data['usage']['completion_tokens'] ?? 0) - (int)$reasoningTokens;
    $content         = trim($data['choices'][0]['message']['content'] ?? '');

    echo "finish=$finishReason | reasoning_tk=$reasoningTokens | output_tk=$outputTokens | content_len=" . strlen($content) . " | ";

    if (!$content) {
        $errMsg = "Empty content. finish=$finishReason reasoning_tk=$reasoningTokens raw=" . mb_substr($resp, 0, 400);
        echo "ERREUR\n$errMsg\n";
        $stmtUpdate->execute([null, 'error', mb_substr($errMsg, 0, 500), $page['id']]);
        $errors++;
        continue;
    }

    // Strip éventuels code fences si le modèle les inclut quand même
    $content = preg_replace('/^```html?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    $stmtUpdate->execute([$content, 'generated', null, $page['id']]);
    $generated++;
    echo "OK\n";

    // Petit délai pour éviter le rate-limit
    usleep(200000); // 200ms
}

echo "\n[OK] Générées: $generated | Erreurs: $errors\n";

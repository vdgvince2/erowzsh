<?php
declare(strict_types=1);

/**
 * Proxy d'image avec option WebP + placeholder en erreur.
 * Jamais de 4xx/5xx vers le client final : on renvoie une image de fallback.
 *
 * Usage:
 *   image.php?url=<base64(url)>&sig=<base64(hmac_sha256(url, secret))>&q=82&webp=1
 */

ini_set('display_errors', '0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Access-Control-Allow-Origin: *');

const IMG_PROXY_SECRET   = 'change-me-very-strong'; // change-moi
// Si tu veux un placeholder custom, mets un chemin absolu ici (png/jpg/webp/svg)
const PLACEHOLDER_PATH   = ''; // ex: __DIR__ . '/placeholder.png'

// ---------- Helpers généraux ----------
function send_cache_headers(): void {
    header('Cache-Control: public, max-age=31536000, immutable');
    header('X-Robots-Tag: noindex');
}

function client_wants_webp(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return stripos($accept, 'image/webp') !== false;
}
function can_convert_webp(): bool {
    return function_exists('imagewebp');
}
function convert_bytes_to_webp(string $bytes, int $quality): ?string {
    if (!can_convert_webp()) return null;
    $im = @imagecreatefromstring($bytes);
    if ($im === false) return null;
    @imagepalettetotruecolor($im);
    @imagealphablending($im, true);
    @imagesavealpha($im, true);
    ob_start();
    $ok = @imagewebp($im, null, $quality);
    imagedestroy($im);
    $out = $ok ? ob_get_clean() : (ob_end_clean() || '');
    return $ok && $out !== '' ? $out : null;
}
function output_bytes(string $mime, string $bytes, bool $varyAccept = false): never {
    header('Content-Type: ' . $mime);
    if ($varyAccept) header('Vary: Accept');
    send_cache_headers();
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
}

/**
 * Sert un placeholder et termine (200).
 * - Si PLACEHOLDER_PATH est défini et lisible, on l’utilise (avec conversion webp si demandé/possible).
 * - Sinon, fallback:
 *     - si WebP souhaité & possible: on génère un 1x1 transparent en WebP
 *     - sinon: on sert un tiny PNG transparent embarqué (base64)
 */
function serve_placeholder_and_exit(bool $preferWebp, int $quality, string $reason = ''): never {
    // Optionnel: journaliser en serveur (pas transmis au client)
    if ($reason !== '') error_log('[image-proxy] placeholder: ' . $reason);

    // Placeholder custom local
    if (PLACEHOLDER_PATH !== '' && is_file(PLACEHOLDER_PATH) && is_readable(PLACEHOLDER_PATH)) {
        $bytes = @file_get_contents(PLACEHOLDER_PATH);
        if ($bytes !== false && $bytes !== '') {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'application/octet-stream';
            if ($preferWebp && can_convert_webp()) {
                $webp = convert_bytes_to_webp($bytes, $quality);
                if ($webp !== null) {
                    output_bytes('image/webp', $webp, true);
                }
            }
            // sinon: servir tel quel
            output_bytes($mime, $bytes, $preferWebp);
        }
    }

    // Génération dynamique 1x1 si WebP souhaité & possible
    if ($preferWebp && can_convert_webp() && function_exists('imagecreatetruecolor')) {
        $im = @imagecreatetruecolor(1, 1);
        if ($im !== false) {
            // transparent
            imagesavealpha($im, true);
            $trans = imagecolorallocatealpha($im, 0, 0, 0, 127);
            imagefill($im, 0, 0, $trans);
            ob_start();
            @imagewebp($im, null, $quality);
            imagedestroy($im);
            $webp = ob_get_clean();
            if ($webp !== false && $webp !== '') {
                output_bytes('image/webp', $webp, true);
            }
        }
    }

    // Tiny 1x1 PNG transparent (base64, ~67 bytes)
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAA'
               . 'AAC0lEQVR42mP8/x8AAwMB/ak1Jd8AAAAASUVORK5CYII=';
    $png = base64_decode($pngBase64, true);
    output_bytes('image/png', $png ?: '', $preferWebp);
}

/** Vérifie (facultatif) la signature; en cas d’erreur -> placeholder */
function check_signature_or_placeholder(?string $b64Url, ?string $b64Sig, bool $preferWebp, int $quality): string {
    if (!$b64Url) serve_placeholder_and_exit($preferWebp, $quality, 'missing url b64');
    $url = base64_decode($b64Url, true);
    if ($url === false || !filter_var($url, FILTER_VALIDATE_URL)) {
        serve_placeholder_and_exit($preferWebp, $quality, 'invalid url');
    }
    if ($b64Sig !== null && $b64Sig !== '') {
        $calc = base64_encode(hash_hmac('sha256', $url, IMG_PROXY_SECRET, true));
        if (!hash_equals($calc, $b64Sig)) {
            serve_placeholder_and_exit($preferWebp, $quality, 'invalid signature');
        }
    }
    return $url;
}

/** Fetch amont; en cas d’erreur -> placeholder */
function http_fetch_or_placeholder(string $url, bool $preferWebp, int $quality): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION   => true,
        CURLOPT_RETURNTRANSFER   => true,
        CURLOPT_CONNECTTIMEOUT   => 6,
        CURLOPT_TIMEOUT          => 20,
        CURLOPT_USERAGENT        => 'ImgProxy/1.0',
        CURLOPT_SSL_VERIFYPEER   => true,
        CURLOPT_HEADER           => true,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch) ?: 'curl error';
        curl_close($ch);
        serve_placeholder_and_exit($preferWebp, $quality, 'curl: ' . $err);
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $rawHeaders = substr($resp, 0, $hsz);
    $body = substr($resp, $hsz);
    curl_close($ch);

    $headers = [];
    foreach (explode("\r\n", $rawHeaders) as $line) {
        $p = strpos($line, ':');
        if ($p !== false) {
            $k = strtolower(trim(substr($line, 0, $p)));
            $v = trim(substr($line, $p + 1));
            if ($k !== '') $headers[$k] = $v;
        }
    }
    if ($code < 200 || $code >= 300) {
        serve_placeholder_and_exit($preferWebp, $quality, 'upstream http ' . $code);
    }
    return [$headers, $body];
}

function get_header(array $headers, string $key, ?string $def = null): ?string {
    $k = strtolower($key);
    return $headers[$k] ?? $def;
}

// ---------- Entrées ----------
$b64Url = $_GET['url'] ?? null;
$b64Sig = $_GET['sig'] ?? null;
$q      = isset($_GET['q']) ? max(60, min(100, (int)$_GET['q'])) : 82;
$force  = isset($_GET['webp']) && $_GET['webp'] === '1';
$preferWebp = $force || client_wants_webp();

// Vérif + récupération
$srcUrl = check_signature_or_placeholder($b64Url, $b64Sig, $preferWebp, $q);
[$upHeaders, $body] = http_fetch_or_placeholder($srcUrl, $preferWebp, $q);

// Vérifie que c’est une image, sinon placeholder
$mime = get_header($upHeaders, 'content-type', 'application/octet-stream') ?? 'application/octet-stream';
if (stripos($mime, 'image/') !== 0) {
    serve_placeholder_and_exit($preferWebp, $q, 'not an image');
}

// ---------- Branche WebP si souhaité ----------
if ($preferWebp && can_convert_webp()) {
    $webp = convert_bytes_to_webp($body, $q);
    if ($webp !== null) {
        output_bytes('image/webp', $webp, true);
    }
    // sinon on tombera en passthrough
}

// ---------- Passthrough original ----------
output_bytes($mime, $body, $preferWebp);

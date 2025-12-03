<?php
/**
 * status.php — Simple uptime checker (parallel) for HTML endpoints.
 * - Checks HTTP 200, content-type text/html, presence of <html> ... </body|/html>
 * - Follows redirects (up to 5)
 * - Measures timings (DNS, connect, TTFB, total)
 * - Auto-refresh every 5s
 */

declare(strict_types=1);

// ---------------------------- Config ----------------------------
$URLS = [
    "https://www.site-annonce.be/poele-a-bois",
    "https://www.for-sale.ie/skean-dhu",
    "https://www.for-sale.co.uk/little-nipper",
    "https://www.used.forsale/4-cabinet-rolling-drawer",
    "https://www.gebraucht-kaufen.de/signiert-nummeriert",
    "https://www.in-vendita.it/zenith-port-royal",
];

// Timeouts
const CONNECT_TIMEOUT = 6;   // seconds
const TOTAL_TIMEOUT   = 12;  // seconds

// Max bytes to download (we don't need full pages for checks)
const MAX_BYTES = 1_500_000; // 1.5 MB

// User-Agent (avoid being blocked by some CDNs)
const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) StatusBot/1.0 Chrome/119 Safari/537.36';

// Disable output buffering for quick render
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', '1');

// HTTP headers to prevent caching and allow quick reloads
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');

// ---------------------------- Helpers ----------------------------
function clamp_download(int $maxBytes): callable {
    // Returns a CURLOPT_WRITEFUNCTION to limit download size
    return function ($ch, string $chunk) use ($maxBytes) {
        $data = curl_getinfo($ch, CURLINFO_PRIVATE);
        // Use globals via static store
        static $buffers = [];
        if (!isset($buffers[$data])) $buffers[$data] = '';
        $remain = $maxBytes - strlen($buffers[$data]);
        if ($remain <= 0) return 0;
        $buffers[$data] .= ($remain >= strlen($chunk)) ? $chunk : substr($chunk, 0, $remain);
        $GLOBALS['__body_store'][$data] = $buffers[$data];
        return strlen($chunk);
    };
}

function extract_title(string $html): ?string {
    if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $m)) {
        $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $title = preg_replace('/\s+/u', ' ', $title);
        return $title !== '' ? $title : null;
    }
    return null;
}

function is_html_complete(string $html): bool {
    $hasOpen  = (bool)preg_match('~<html[^>]*>~i', $html);
    $hasClose = (bool)preg_match('~</(body|html)>~i', $html);
    return $hasOpen && $hasClose;
}

function nice_bytes(int $b): string {
    if ($b >= 1<<20) return sprintf('%.1f MB', $b / (1<<20));
    if ($b >= 1<<10) return sprintf('%.1f KB', $b / (1<<10));
    return $b . ' B';
}

function ms(float $seconds): string {
    return sprintf('%.0f ms', $seconds * 1000);
}

function badge(string $text, string $class): string {
    return "<span class=\"badge $class\">$text</span>";
}

// ---------------------------- cURL multi ----------------------------
$mh = curl_multi_init();
$handles = [];
$GLOBALS['__body_store'] = []; // body buffers keyed by ID

$writeFn = clamp_download(MAX_BYTES);

foreach ($URLS as $idx => $url) {
    $ch = curl_init();
    $id = (string)$idx; // key for body store
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => false,   // we capture via WRITEFUNCTION
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT        => TOTAL_TIMEOUT,
        CURLOPT_USERAGENT      => UA,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fr,en;q=0.9',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
        CURLOPT_HEADER         => false,
        CURLOPT_NOBODY         => false,
        CURLOPT_ENCODING       => '',      // allow gzip/deflate
        CURLOPT_PRIVATE        => $id,     // store id
        CURLOPT_WRITEFUNCTION  => $writeFn,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$id] = $ch;
}

$running = 0;
do {
    $mrc = curl_multi_exec($mh, $running);
    curl_multi_select($mh, 1.0);
} while ($running > 0 && $mrc === CURLM_OK);

// Collect results
$rows = [];
foreach ($handles as $id => $ch) {
    $info = curl_getinfo($ch);
    $errno = curl_errno($ch);
    $error = $errno ? curl_error($ch) : null;
    $body  = $GLOBALS['__body_store'][$id] ?? '';

    $statusCode = $info['http_code'] ?? 0;
    $ctype      = $info['content_type'] ?? '';
    $finalUrl   = $info['url'] ?? '';
    $size       = strlen($body);

    $isHtmlType = stripos((string)$ctype, 'text/html') !== false || stripos((string)$ctype, 'application/xhtml+xml') !== false;
    $htmlOk     = $isHtmlType && is_html_complete($body);
    $ok200      = ($statusCode === 200);

    $title = $isHtmlType ? (extract_title($body) ?? '—') : '—';

    $rows[] = [
        'url'     => $finalUrl,
        'code'    => $statusCode,
        'ctype'   => $ctype ?: '—',
        'title'   => $title,
        'dns'     => $info['namelookup_time']  ?? 0.0,
        'connect' => $info['connect_time']     ?? 0.0,
        'ttfb'    => $info['starttransfer_time'] ?? 0.0,
        'total'   => $info['total_time']       ?? 0.0,
        'size'    => $size,
        'html'    => $htmlOk,
        'ok200'   => $ok200,
        'err'     => $error,
        'redir'   => ($info['redirect_count'] ?? 0),
        'primary' => $info['primary_ip'] ?? '',
    ];

    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// Overall summary
$up = 0; $down = 0;
foreach ($rows as $r) {
    ($r['ok200'] && $r['html']) ? $up++ : $down++;
}

// ---------------------------- Render ----------------------------
$now = new DateTimeImmutable('now');
$autoRefreshSec = 0;
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta http-equiv="refresh" content="<?= (int)$autoRefreshSec ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Status — Uptime HTML (<?= $up ?>/<?= count($rows) ?> OK)</title>
<style>
    :root { --bg:#0b0f14; --card:#121822; --muted:#8aa0b3; --ok:#16a34a; --warn:#f59e0b; --err:#ef4444; --txt:#e5eef6; }
    html,body { background:var(--bg); color:var(--txt); font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif; }
    .wrap { max-width: 1200px; margin: 24px auto; padding: 0 16px; }
    h1 { font-size: 20px; margin: 0 0 10px; }
    .meta { color: var(--muted); font-size: 13px; margin-bottom: 16px; }
    table { width:100%; border-collapse: collapse; background:var(--card); border-radius: 12px; overflow: hidden; }
    th, td { padding: 10px 12px; border-bottom: 1px solid #1d2633; font-size: 14px; vertical-align: middle; }
    th { text-align: left; color: var(--muted); font-weight: 600; }
    tr:last-child td { border-bottom: none; }
    a { color:#93c5fd; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .badge { padding: 4px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; display: inline-block; }
    .ok { background: rgba(22,163,74,.15); color:#86efac; border:1px solid rgba(22,163,74,.35); }
    .err { background: rgba(239,68,68,.15); color:#fecaca; border:1px solid rgba(239,68,68,.35); }
    .warn { background: rgba(245,158,11,.15); color:#fde68a; border:1px solid rgba(245,158,11,.35); }
    .muted { color: var(--muted); }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; font-size: 12px; }
    .t-right { text-align: right; }
    .pill { display:inline-block; padding: 2px 6px; background:#1b2432; border-radius:8px; color:#a7b8c8; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>Status — Pages HTML</h1>
    <div class="meta">
        Dernière vérification : <strong><?= htmlspecialchars($now->format('Y-m-d H:i:s')) ?></strong> —
        Rafraîchissement auto <span class="pill"><?= $autoRefreshSec ?>s</span> —
        Santé : <?= badge("$up OK", $down ? 'ok' : 'ok') ?> <?= $down ? badge("$down KO", 'err') : '' ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>URL</th>
                <th>HTTP</th>
                <th>HTML</th>
                <th>Titre</th>
                <th>TTFB</th>
                <th>Total</th>
                <th>Taille</th>
                <th>IP</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                    $httpBadge = $r['ok200'] ? badge('200', 'ok') :
                                 ($r['code'] ? badge((string)$r['code'], 'err') : badge('ERR', 'err'));
                    $htmlBadge = $r['html'] ? badge('OK', 'ok') :
                                 ($r['ok200'] && $r['ctype'] !== '—' ? badge('Incomplet', 'warn') : badge('N/A', 'err'));
                    $title = htmlspecialchars($r['title']);
                    $urlDisp = htmlspecialchars($r['url']);
                    $urlHref = $r['url'];
                    $ttfb = ms((float)$r['ttfb']);
                    $total = ms((float)$r['total']);
                    $size = nice_bytes((int)$r['size']);
                    $ip   = $r['primary'] ? htmlspecialchars($r['primary']) : '—';
                    $info = [];
                    if ($r['redir']) $info[] = $r['redir'].'→';
                    if ($r['ctype']) $info[] = preg_split('/\s*;\s*/', $r['ctype'])[0];
                    if ($r['err'])   $info[] = 'cURL: '.htmlspecialchars($r['err']);
                    $infoStr = $info ? implode(' · ', $info) : '—';
                ?>
                <tr>
                    <td class="mono"><a href="<?= htmlspecialchars($urlHref) ?>" target="_blank" rel="noopener noreferrer"><?= $urlDisp ?></a></td>
                    <td><?= $httpBadge ?></td>
                    <td><?= $htmlBadge ?></td>
                    <td><?= $title ?></td>
                    <td class="t-right"><?= $ttfb ?></td>
                    <td class="t-right"><?= $total ?></td>
                    <td class="t-right"><?= $size ?></td>
                    <td class="mono"><?= $ip ?></td>
                    <td class="muted"><?= $infoStr ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="meta" style="margin-top:12px;">
        Règles: HTTP=200, Content-Type HTML, structure HTML complète (<code class="mono">&lt;html&gt; ... &lt;/body&gt;|&lt;/html&gt;</code>). Téléchargement limité à <?= number_format(MAX_BYTES) ?> octets.
    </p>
</div>
</body>
</html>

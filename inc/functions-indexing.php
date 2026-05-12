<?php
/**
 * Search engine indexing notifications — deals pages only.
 *
 * • IndexNow          → Bing, Yandex, Seznam (instant, no auth)
 * • Pingomatic        → 30+ aggregators via XML-RPC (no auth)
 * • Sitemap ping      → Google + Bing GET endpoint (no auth)
 * • Google Indexing API → Direct URL notification to Google (<24h)
 *                         Requires a service account JSON key file.
 *                         Path: inc/google_indexing_sa.json
 *                         Note: officially for job postings / broadcast events,
 *                         but widely used for all page types.
 */

define('INDEXNOW_KEY',      'b4e8f2c7d1a9b3e5c2f8d6a4b1e7c3f9');
define('INDEXNOW_ENDPOINT', 'https://api.indexnow.org/indexnow');

// Load RAPID_INDEXER_API_KEY from .env if not already in environment
if (!getenv('RAPID_INDEXER_API_KEY')) {
    foreach ([__DIR__ . '/../.env', __DIR__ . '/../content-sites/.env'] as $_envPath) {
        if (!file_exists($_envPath)) continue;
        foreach (file($_envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_l) {
            if ($_l[0] === '#' || !str_contains($_l, '=')) continue;
            [$_ek, $_ev] = explode('=', $_l, 2);
            if (trim($_ek) === 'RAPID_INDEXER_API_KEY') { putenv('RAPID_INDEXER_API_KEY=' . trim($_ev)); break 2; }
        }
    }
}
unset($_envPath, $_l, $_ek, $_ev);

define('RAPID_INDEXER_ENDPOINT', 'https://rapid-indexer.com/api/v1/index.php');

// Path to the Google service account JSON key (not web-accessible)
define('GOOGLE_SA_FILE',     __DIR__ . '/google_indexing_sa.json');
// Temp file for caching the OAuth2 access token (valid 55 min)
define('GOOGLE_TOKEN_CACHE', sys_get_temp_dir() . '/sh_google_indexing_token.json');

// Production domain map — country code → HTTPS root (no trailing slash)
const DEALS_PROD_DOMAINS = [
    'IE'    => 'https://for-sale.ie',
    'GB'    => 'https://for-sale.co.uk',
    'FR'    => 'https://site-annonce.fr',
    'DE'    => 'https://gebraucht-kaufen.de',
    'BE'    => 'https://site-annonce.be',
    'IT'    => 'https://in-vendita.it',
    'US'    => 'https://used.forsale',
    'EROWZ' => 'https://erowz.com',
];


// ── Shared helpers ────────────────────────────────────────────────────────────

function indexing_prod_domain(string $countryCode): ?string
{
    return DEALS_PROD_DOMAINS[$countryCode] ?? null;
}

function indexing_is_local(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? gethostname();
    return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
}

function _b64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}


// ── IndexNow ─────────────────────────────────────────────────────────────────

/**
 * Notifies IndexNow (Bing, Yandex, Seznam…) with one or more URLs.
 * Silent fail — errors are never surfaced to the user.
 *
 * @param string $host  Hostname only, e.g. "for-sale.ie"
 * @param array  $urls  Absolute HTTPS URLs (max 10 000 per call)
 */
function indexnow_ping(string $host, array $urls): void
{
    if (empty($urls) || indexing_is_local()) return;

    $payload = json_encode([
        'host'        => $host,
        'key'         => INDEXNOW_KEY,
        'keyLocation' => 'https://' . $host . '/' . INDEXNOW_KEY . '.txt',
        'urlList'     => array_values($urls),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init(INDEXNOW_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}


// ── Pingomatic ────────────────────────────────────────────────────────────────

/**
 * Pings Pingomatic via XML-RPC weblogUpdates.extendedPing.
 * Notifies ~30 aggregators (Google Blog Search, Technorati, Weblogs.com…).
 */
function pingomatic_ping(string $blogName, string $pageUrl, string $sitemapUrl): void
{
    if (indexing_is_local()) return;

    $e = fn(string $s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
         . '<methodCall>'
         . '<methodName>weblogUpdates.extendedPing</methodName>'
         . '<params>'
         . '<param><value><string>' . $e($blogName)   . '</string></value></param>'
         . '<param><value><string>' . $e($pageUrl)    . '</string></value></param>'
         . '<param><value><string>' . $e($pageUrl)    . '</string></value></param>'
         . '<param><value><string>' . $e($sitemapUrl) . '</string></value></param>'
         . '</params>'
         . '</methodCall>';

    $ch = curl_init('http://rpc.pingomatic.com/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $xml,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: text/xml',
            'Content-Length: ' . strlen($xml),
        ],
        CURLOPT_TIMEOUT => 8,
    ]);
    curl_exec($ch);
    curl_close($ch);
}


// ── Google + Bing sitemap ping ────────────────────────────────────────────────

/**
 * Notifies Google and Bing of a sitemap update via their public GET endpoints.
 */
function sitemap_ping(string $sitemapUrl): void
{
    if (indexing_is_local()) return;

    $encoded   = urlencode($sitemapUrl);
    $endpoints = [
        'https://www.google.com/ping?sitemap=' . $encoded,
        'https://www.bing.com/ping?sitemap='   . $encoded,
    ];

    $mh = curl_multi_init();
    $handles = [];
    foreach ($endpoints as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[] = $ch;
    }
    do { curl_multi_exec($mh, $running); } while ($running > 0);
    foreach ($handles as $ch) { curl_multi_remove_handle($mh, $ch); curl_close($ch); }
    curl_multi_close($mh);
}


// ── Google Indexing API ───────────────────────────────────────────────────────

/**
 * Returns a valid OAuth2 access token using the service account key file.
 * Token is cached on disk for 55 minutes to avoid redundant token requests.
 *
 * Returns null if the key file is missing or malformed.
 */
function google_indexing_get_token(): ?string
{
    // Return cached token if still valid
    if (file_exists(GOOGLE_TOKEN_CACHE)) {
        $cache = json_decode(file_get_contents(GOOGLE_TOKEN_CACHE), true);
        if (!empty($cache['token']) && !empty($cache['expires']) && time() < (int)$cache['expires']) {
            return $cache['token'];
        }
    }

    if (!file_exists(GOOGLE_SA_FILE)) return null;

    $sa = json_decode(file_get_contents(GOOGLE_SA_FILE), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) return null;

    // Build JWT header + claims
    $now    = time();
    $header = _b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = _b64url(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ]));

    // Sign with RS256
    $signingInput = $header . '.' . $claims;
    if (!openssl_sign($signingInput, $sig, $sa['private_key'], OPENSSL_ALGO_SHA256)) return null;
    $jwt = $signingInput . '.' . _b64url($sig);

    // Exchange JWT for access token
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT    => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data  = json_decode($resp, true);
    $token = $data['access_token'] ?? null;
    if (!$token) return null;

    // Cache the token (55 min safety margin vs Google's 60 min validity)
    file_put_contents(GOOGLE_TOKEN_CACHE, json_encode([
        'token'   => $token,
        'expires' => $now + 3300,
    ]));

    return $token;
}

/**
 * Notifies the Google Indexing API that a single URL has been updated.
 * Requires inc/google_indexing_sa.json (service account key).
 *
 * @param string $url   Absolute HTTPS URL
 * @param string $type  'URL_UPDATED' (default) or 'URL_DELETED'
 */
function google_indexing_ping(string $url, string $type = 'URL_UPDATED'): void
{
    if (indexing_is_local()) return;

    $token = google_indexing_get_token();
    if (!$token) return; // key file not yet configured — silent skip

    $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['url' => $url, 'type' => $type]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Batch Google Indexing API — pings multiple URLs sequentially.
 * Google's default quota: 200 URL notifications/day per project.
 * Adds a 150ms delay between calls to stay within rate limits.
 */
function google_indexing_ping_batch(array $urls, string $type = 'URL_UPDATED'): void
{
    if (indexing_is_local() || empty($urls)) return;

    $token = google_indexing_get_token();
    if (!$token) return;

    foreach ($urls as $url) {
        $ch = curl_init('https://indexing.googleapis.com/v3/urlNotifications:publish');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['url' => $url, 'type' => $type]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
        usleep(150000); // 150ms between calls
    }
}


// ── Rapid Indexer ─────────────────────────────────────────────────────────────

/**
 * Submits one or more URLs to Rapid Indexer for Google indexing.
 * Requires RAPID_INDEXER_API_KEY in the environment or .env file.
 *
 * @param array  $urls   Absolute HTTPS URLs (up to ~1 000 per call is safe)
 * @param string $title  Optional task label visible in the Rapid Indexer dashboard
 */
function rapid_indexer_ping(array $urls, string $title = ''): void
{
    if (empty($urls) || indexing_is_local()) return;

    $apiKey = getenv('RAPID_INDEXER_API_KEY');
    if (!$apiKey) return; // key not configured — silent skip

    $payload = json_encode(array_filter([
        'urls'   => array_values($urls),
        'type'   => 'indexer',
        'engine' => 'google',
        'title'  => $title ?: null,
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init(RAPID_INDEXER_ENDPOINT . '?action=create_task');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/**
 * Batch Rapid Indexer — splits large URL lists into chunks of 500 to stay
 * within API limits, firing one task per chunk.
 */
function rapid_indexer_ping_batch(array $urls, string $title = ''): void
{
    if (empty($urls) || indexing_is_local()) return;

    foreach (array_chunk($urls, 500) as $i => $chunk) {
        $chunkTitle = $title ? "{$title} (part " . ($i + 1) . ')' : '';
        rapid_indexer_ping($chunk, $chunkTitle);
    }
}


// ── High-level convenience ────────────────────────────────────────────────────

/**
 * Full ping for a single deals page:
 * IndexNow + Pingomatic + Google Indexing API.
 *
 * Called from deals.php on every cache refresh (≈ once per hour per URL).
 */
function deals_page_ping(string $countryCode, string $siteName, string $dealsPath): void
{
    $root = indexing_prod_domain($countryCode);
    if (!$root) return;

    $host       = parse_url($root, PHP_URL_HOST);
    $pageUrl    = $root . $dealsPath;
    $sitemapUrl = $root . '/sitemap-deals.xml';

    indexnow_ping($host, [$pageUrl]);
    pingomatic_ping($siteName, $pageUrl, $sitemapUrl);
    google_indexing_ping($pageUrl);
}

/**
 * Batch ping: all deals pages for one country.
 * IndexNow (one call, all URLs) + Pingomatic (once) + Google API (per URL) + sitemap ping.
 *
 * Called from bihourly.sh via scripts/ping_deals_all.php.
 */
function deals_ping_all(string $countryCode, string $siteName, array $catalog): void
{
    $root = indexing_prod_domain($countryCode);
    if (!$root) return;

    $host       = parse_url($root, PHP_URL_HOST);
    $sitemapUrl = $root . '/sitemap-deals.xml';

    $urls = [];
    foreach ($catalog as $catSlug => $catData) {
        foreach ($catData['countries'][$cc]['keywords'] ?? [] as $kw) {
            $urls[] = $root . '/deals/' . $catSlug . '/' . $kw['slug'];
        }
    }

    if (empty($urls)) return;

    // IndexNow — one call for all URLs
    foreach (array_chunk($urls, 100) as $chunk) {
        indexnow_ping($host, $chunk);
    }

    // Pingomatic — one ping for the /deals/ section
    pingomatic_ping($siteName, $root . '/deals/', $sitemapUrl);

    // Google sitemap ping
    sitemap_ping($sitemapUrl);

    // Google Indexing API — per URL (respects 200/day quota)
    google_indexing_ping_batch($urls);
}

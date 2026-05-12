#!/usr/bin/env php
<?php
/**
 * fetch_commoncrawl.php — Importe les URLs d'un domaine depuis CommonCrawl
 *
 * Usage :
 *   php fetch_commoncrawl.php --site-id=1
 *   php fetch_commoncrawl.php --domain=minderlist.com --country=IE
 *
 * Ce script :
 *   1. Récupère les URLs du domaine via l'API CDX de CommonCrawl
 *   2. Filtre les URLs invalides (< 10 chars, sans lettres, extensions médias)
 *   3. Génère un titre propre depuis l'URL
 *   4. Génère un slug propre (nouvelle URL)
 *   5. Insère dans recovered_pages (max 200, skip doublons)
 *   6. Met à jour recovered_sites.crawled_at
 */

define('CS_CLI', true);

// ── Config ────────────────────────────────────────────────────────────────────

$rootDir = dirname(__DIR__, 2); // /content-sites

// Parse args
$opts    = getopt('', ['site-id:', 'domain:', 'country:', 'limit:', 'api-url:']);
$siteId  = isset($opts['site-id'])  ? (int)$opts['site-id']  : null;
$domain  = $opts['domain']  ?? null;
$country = strtoupper($opts['country'] ?? 'IE');
$limit   = min(200, (int)($opts['limit'] ?? 200));
$apiUrlArg = rtrim($opts['api-url'] ?? '', '/');

if (!$siteId && !$domain) {
    fwrite(STDERR, "Usage: php fetch_commoncrawl.php --site-id=N  OR  --domain=example.com --country=IE\n");
    exit(1);
}

// ── Config API front ─────────────────────────────────────────────────────────

$_envFile = $rootDir . '/.env';
if (!file_exists($_envFile)) $_envFile = $rootDir . '/../.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line[0] === '#' || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        if (!getenv(trim($_k))) putenv(trim($_k) . '=' . trim($_v));
    }
}

// URL de l'API : priorité à --api-url (passé par recover-admin.php), sinon fallback .env
$_apiBase = $apiUrlArg ?: rtrim(getenv('RECOVER_API_URL') ?: '', '/');
if (!$_apiBase) {
    fwrite(STDERR, "Passe --api-url=https://tondomain.com ou définis RECOVER_API_URL dans .env\n");
    exit(1);
}
$apiBase  = $_apiBase . '/admin/recover-api.php';
$apiToken = getenv('RECOVER_API_TOKEN') ?: '';

if (!$apiToken) {
    fwrite(STDERR, "RECOVER_API_TOKEN manquant dans .env\n");
    exit(1);
}

/**
 * Appelle l'API web interne (évite PDO CLI).
 * @return mixed parsed JSON ou null en cas d'erreur
 */
function api_call(string $url, string $method = 'GET', mixed $body = null): mixed
{
    global $apiToken;
    $opts = [
        'http' => [
            'method'  => $method,
            'header'  => "X-Api-Token: {$apiToken}\r\nContent-Type: application/json\r\n",
            'timeout' => 15,
        ],
    ];
    if ($body !== null) {
        $opts['http']['content'] = json_encode($body);
    }
    $resp = @file_get_contents($url, false, stream_context_create($opts));
    if ($resp === false) return null;
    return json_decode($resp, true);
}

// ── Charge le site récupéré via API ──────────────────────────────────────────

if ($siteId) {
    $site = api_call($apiBase . '?action=get_site&site_id=' . $siteId);
    if (!$site) { fwrite(STDERR, "Site id=$siteId introuvable.\n"); exit(1); }
    $domain = $site['domain'];
} else {
    $site = api_call($apiBase . '?action=get_site_by_domain&domain=' . urlencode($domain));
    if (!$site) { fwrite(STDERR, "Domaine $domain non configuré dans recovered_sites.\n"); exit(1); }
    $siteId = (int)$site['id'];
}

// crawl_domain = vrai domaine à interroger sur CommonCrawl (≠ hostname local)
$crawlDomain = !empty($site['crawl_domain']) ? $site['crawl_domain'] : $site['domain'];

echo "[CommonCrawl] Routing host : $domain | Crawl domain : $crawlDomain (site_id=$siteId, limit=$limit)\n";

// ── Fonctions utilitaires ─────────────────────────────────────────────────────

/**
 * Convertit un chemin URL en titre lisible.
 * /how-to-buy-vintage-watches → "How to Buy Vintage Watches"
 */
function path_to_title(string $path): string
{
    // Supprime l'extension et les paramètres
    $path = preg_replace('/\?.*$/', '', $path);
    $path = preg_replace('/\.(html?|php|asp|aspx|jsp|cfm|cgi)$/i', '', $path);

    // Extrait la dernière partie significative du chemin
    $parts = array_filter(explode('/', $path));
    $slug  = end($parts) ?: reset($parts);
    if (!$slug) return '';

    // Remplace séparateurs par espace
    $title = preg_replace('/[-_]+/', ' ', $slug);
    // Supprime les caractères non-lettre/chiffre/espace
    $title = preg_replace('/[^a-zA-Z0-9 ]/', ' ', $title);
    $title = preg_replace('/\s+/', ' ', trim($title));

    return ucwords(strtolower($title));
}

/**
 * Génère un slug propre depuis un chemin URL.
 */
function path_to_slug(string $path): string
{
    $path = preg_replace('/\?.*$/', '', $path);
    $path = preg_replace('/\.(html?|php|asp|aspx|jsp|cfm|cgi)$/i', '', $path);

    $parts = array_filter(explode('/', $path));
    $slug  = implode('-', $parts);

    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug ?: 'page';
}

/**
 * Vérifie qu'un chemin est valide (contient des lettres, >= 10 chars).
 */
function is_valid_path(string $path): bool
{
    // Ignore les ressources statiques
    if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|pdf|zip|xml|rss|atom|txt|woff|woff2|ttf|eot)(\?|$)/i', $path)) {
        return false;
    }
    // Ignore les chemins trop courts
    if (strlen($path) < 10) return false;
    // Doit contenir au moins 2 lettres consécutives
    if (!preg_match('/[a-zA-Z]{2}/', $path)) return false;
    // Ignore les chemins purement techniques
    if (preg_match('#^/(wp-|admin|login|logout|register|api/|feed|sitemap|robots)#i', $path)) return false;

    return true;
}

// ── Récupère la liste des indexes CommonCrawl disponibles ─────────────────────

echo "[CommonCrawl] Récupération de la liste des indexes...\n";

$collInfoUrl = 'https://index.commoncrawl.org/collinfo.json';
$collJson    = @file_get_contents($collInfoUrl);
if (!$collJson) {
    fwrite(STDERR, "Impossible de contacter index.commoncrawl.org\n");
    exit(1);
}
$collections = json_decode($collJson, true);
if (!$collections) {
    fwrite(STDERR, "Réponse invalide de collinfo.json\n");
    exit(1);
}

// Trie par date décroissante, prend les 5 plus récents
usort($collections, fn($a, $b) => strcmp($b['id'] ?? '', $a['id'] ?? ''));
$recentIndexes = array_slice($collections, 0, 5);

echo "[CommonCrawl] " . count($recentIndexes) . " indexes récents à interroger\n";

// ── Interroge l'API CDX ───────────────────────────────────────────────────────

$allUrls     = [];
$seenPaths   = [];

foreach ($recentIndexes as $index) {
    if (count($allUrls) >= $limit) break;

    $indexId  = $index['id'];
    $cdxUrl   = "https://index.commoncrawl.org/{$indexId}-index?"
              . http_build_query([
                    'url'    => $crawlDomain . '/*',
                    'output' => 'json',
                    'fl'     => 'url,status',
                    'limit'  => $limit * 3, // large pour compenser les filtres
                    'filter' => 'status:200',
                ]);

    echo "[CommonCrawl] Index $indexId → $cdxUrl\n";

    $ctx  = stream_context_create(['http' => ['timeout' => 30]]);
    $resp = @file_get_contents($cdxUrl, false, $ctx);

    if (!$resp) {
        echo "  → Aucun résultat ou erreur réseau\n";
        continue;
    }

    // Réponse = 1 JSON par ligne (NDJSON)
    $lines = array_filter(explode("\n", trim($resp)));
    foreach ($lines as $line) {
        if (count($allUrls) >= $limit) break;

        $entry = json_decode($line, true);
        if (!$entry || empty($entry['url'])) continue;

        $parsed = parse_url($entry['url']);
        $path   = $parsed['path'] ?? '/';

        // Normalise : sans trailing slash sauf racine
        if ($path !== '/') $path = rtrim($path, '/');

        // Déduplique et valide
        if (isset($seenPaths[$path])) continue;
        $seenPaths[$path] = true;

        if (!is_valid_path($path)) continue;

        $allUrls[] = $path;
    }

    echo "  → " . count($allUrls) . " URLs valides accumulées\n";
}

echo "[CommonCrawl] Total URLs retenues : " . count($allUrls) . "\n";

if (empty($allUrls)) {
    echo "Aucune URL valide trouvée. Fin.\n";
    exit(0);
}

// ── Prépare le batch à insérer via API ───────────────────────────────────────

$usedSlugs = [];
$batch     = [];

foreach ($allUrls as $path) {
    $title = path_to_title($path);
    if (!$title) continue;

    $slug = path_to_slug($path);

    $baseSlug = $slug;
    $suffix   = 2;
    while (isset($usedSlugs[$slug])) {
        $slug = $baseSlug . '-' . $suffix++;
    }
    $usedSlugs[$slug] = true;

    $batch[] = ['path' => $path, 'slug' => $slug, 'title' => $title];
}

// ── Envoie le batch à l'API ───────────────────────────────────────────────────

$result = api_call(
    $apiBase . '?action=insert_urls',
    'POST',
    ['site_id' => $siteId, 'urls' => $batch]
);

$inserted = $result['inserted'] ?? 0;
$skipped  = $result['skipped']  ?? 0;

// Met à jour crawled_at via API
api_call($apiBase . '?action=update_crawled&site_id=' . $siteId, 'POST');

echo "[OK] Insérées : $inserted | Ignorées/doublons : $skipped\n";
echo "Utilisez generate_content.php pour générer le contenu AI.\n";

<?php


ini_set('memory_limit', '512M');
date_default_timezone_set('UTC');

/**
 * Retourne le country code (ISO-2) passé en CLI, ou exit avec message d'aide.
 * Usage:
 *   php generate_sitemaps.php --country=FR
 *   php generate_sitemaps.php -c fr
 *   php generate_sitemaps.php FR
 */
function getCountryCodeFromCli(): string
{
    if (PHP_SAPI !== 'cli') {
        fwrite(STDERR, "This script must be run from CLI.\n");
        exit(1);
    }

    global $argv;

    // 1) getopt: --country / -c
    $opts = getopt('c:', ['country:']);
    $cc = $opts['c'] ?? $opts['country'] ?? null;

    // 2) Sinon positionnel: $argv[1]
    if ($cc === null && isset($argv[1]) && $argv[1] !== '' && $argv[1][0] !== '-') {
        $cc = $argv[1];
    }

    // 3) Normalisation & validation
    $cc = strtolower(trim((string)$cc));
    if (!preg_match('/^[a-z]{2}$/', $cc)) {
        $script = basename($argv[0] ?? 'script.php');
        $msg = <<<TXT
Usage:
  php {$script} --country=FR
  php {$script} -c fr
  php {$script} FR

Options:
  -c, --country   ISO 3166-1 alpha-2 country code (2 letters)

Examples:
  php {$script} --country=IE
  php {$script} fr

TXT;
        fwrite(STDERR, $msg);
        exit(2);
    }

    return $cc;
}

function getSitemapOutDir(string $countryCode, ?string $baseDir = null): string
{
    $baseDir = $baseDir ?? (__DIR__ . '/../sitemaps');

    // Normalisation & validation du code pays
    $cc = strtolower(trim($countryCode));
    if (!preg_match('/^[a-z]{2}$/', $cc)) {
        throw new RuntimeException("Invalid country code: '{$countryCode}' (expected 2 letters)");
    }

    // Chemin final: <base>/fr, <base>/us, etc.
    $dir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cc;

    // Création récursive si nécessaire
    if (!is_dir($dir) && !@mkdir(strtoupper($dir), 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create directory: {$dir}");
    }

    // Vérif écriture
    if (!is_writable($dir)) {
        throw new RuntimeException("Directory not writable: {$dir}");
    }

        return $dir;
    }

$countryCode = getCountryCodeFromCli();      // ex: 'fr'
$OUT_DIR     = getSitemapOutDir($countryCode);  // ta fonction précédente

// check if the country is ready
if(!file_exists(__DIR__ . '/../tenants/'.$countryCode.'.php')){ echo "no ".strtoupper($countryCode)." exists \n"; exit; }

require __DIR__ . '/../inc/config.php'; 
require __DIR__ . '/../inc/functions.php'; 


/* ====== CONFIG ====== */
// 2) Base URL (sans slash final) — adapte au contexte local/prod
$BASE_URL = $rootDomain;      // ex prod: https://for-sale.ie
$BASE_PATH = $base;                            // ex local sous-dossier: '/SH' ; prod: ''


// 4) Options
$MAX_URLS_PER_FILE = 50000;   // norme
$GZIP = false;                // true => écrit .xml.gz
$DEFAULT_CHANGEFREQ = 'weekly';
$DEFAULT_PRIORITY = '0.5';

// 5) Cartographie des PAGES statiques (slugs → URL)
$STATIC_PAGE_SLUGS = [
    // legacy
    'cookies', 'help', 'privacy', 'press', 'about', 'home/categoriesindex',
    // nouvelles
    'registration', 'money-back-guarantee', 'bidding-and-buying-help', 'stores',
    'start-selling', 'learn-to-sell', 'business-sellers', 'seller-centre',
    'developers', 'security-centre', 'site-map', 'official-time',
];

/* ====== UTILS ====== */

function ensureDir(string $dir): void {
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create dir: $dir");
    }
}

function xmlHeader(): string {
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
}

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function todayIso(): string {
    return gmdate('Y-m-d');
}

function writeSitemapChunk(array $rows, string $basename, int $part, string $outDir, bool $gzip): string {
    $fname = sprintf('%s-%03d.xml', $basename, $part);
    $path = rtrim($outDir, '/').'/'.$fname;

    $xml  = xmlHeader();
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($rows as $r) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>".esc($r['loc'])."</loc>\n";
        if (!empty($r['lastmod']))     $xml .= "    <lastmod>".esc($r['lastmod'])."</lastmod>\n";
        if (!empty($r['changefreq']))  $xml .= "    <changefreq>".esc($r['changefreq'])."</changefreq>\n";
        if (!empty($r['priority']))    $xml .= "    <priority>".esc($r['priority'])."</priority>\n";
        $xml .= "  </url>\n";
    }
    $xml .= "</urlset>\n";

    if ($gzip) {
        $path .= '.gz';
        $gz = gzopen($path, 'wb9');
        if (!$gz) throw new RuntimeException("Cannot write: $path");
        gzwrite($gz, $xml);
        gzclose($gz);
    } else {
        if (file_put_contents($path, $xml) === false) {
            throw new RuntimeException("Cannot write: $path");
        }
    }
    return basename($path);
}

function writeSitemapIndex(array $files, string $baseUrl, string $basePath, string $outDir, bool $gzip): string {
    $indexPath = rtrim($outDir, '/').'/sitemap-index.xml';
    $xml  = xmlHeader();
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $today = todayIso();

    foreach ($files as $f) {
        $url = rtrim($baseUrl.$basePath, '/').'/sitemaps/'.$f;
        $xml .= "  <sitemap>\n";
        $xml .= "    <loc>".esc($url)."</loc>\n";
        $xml .= "    <lastmod>".esc($today)."</lastmod>\n";
        $xml .= "  </sitemap>\n";
    }
    $xml .= "</sitemapindex>\n";

    if (file_put_contents($indexPath, $xml) === false) {
        throw new RuntimeException("Cannot write: $indexPath");
    }
    return basename($indexPath);
}

/* ====== BUILDERS ====== */

function buildPages(string $baseUrl, string $basePath, array $slugs, string $changefreq, string $priority): array {
    $out = [];
    $today = todayIso();
    foreach ($slugs as $slug) {
        // Pages sont sous /s/<slug>
        $loc = rtrim($baseUrl.$basePath, '/').'/s/'.ltrim($slug, '/');
        $out[] = [
            'loc'        => $loc,
            'lastmod'    => $today,
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }
    return $out;
}

function buildCategories(PDO $pdo, string $baseUrl, string $basePath, string $changefreq, string $priority): iterable {
    // On ne lit plus updated_at/created_at
    $stmt = $pdo->query("SELECT id, slug_path FROM categories");
    $today = gmdate('Y-m-d');

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $path = $row['slug_path'] ?? '';
        if (!$path) continue;
        $loc = rtrim($baseUrl.$basePath, '/').'/s'.(strpos($path, '/')===0 ? $path : '/'.$path);

        yield [
            'loc'        => $loc,
            'lastmod'    => $today,        // ← NOW()
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }
}


function buildKeywords(PDO $pdo, string $baseUrl, string $basePath, string $changefreq, string $priority): iterable {
    $stmt = $pdo->query("
        SELECT id, keywordURL AS url
        FROM keywords
        WHERE keywordURL IS NOT NULL AND keywordURL <> ''
    ");
    $today = gmdate('Y-m-d');

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $raw = trim((string)$row['url']);
        if ($raw === '') continue;

        if (preg_match('#^https?://#i', $raw)) {
            $loc = $raw;
        } else {
            $slug = trim($raw, '/');
            if ($slug === '') continue;
            $loc = rtrim($baseUrl.$basePath, '/').'/'.$slug;
        }

        yield [
            'loc'        => $loc,
            'lastmod'    => $today,        // ← NOW()
            'changefreq' => $changefreq,
            'priority'   => $priority,
        ];
    }
}

/* ====== MAIN ====== */

ensureDir($OUT_DIR);

// On (ré)initialise la liste de fichiers écrits pour l’index
$indexFiles = [];

// PAGES
$pages = buildPages($BASE_URL, $BASE_PATH, $STATIC_PAGE_SLUGS, 'monthly', '0.4');
if (!empty($pages)) {
    $part = 1;
    $chunks = array_chunk($pages, $MAX_URLS_PER_FILE);
    foreach ($chunks as $chunk) {
        $f = writeSitemapChunk($chunk, 'sitemap-pages', $part++, $OUT_DIR, $GZIP);
        $indexFiles[] = $f;
    }
}

// CATEGORIES (streaming en chunks)
$part = 1;
$bucket = [];
foreach (buildCategories($pdo, $BASE_URL, $BASE_PATH, 'weekly', '0.6') as $row) {
    $bucket[] = $row;
    if (count($bucket) >= $MAX_URLS_PER_FILE) {
        $f = writeSitemapChunk($bucket, 'sitemap-categories', $part++, $OUT_DIR, $GZIP);
        $indexFiles[] = $f;
        $bucket = [];
    }
}
if ($bucket) {
    $f = writeSitemapChunk($bucket, 'sitemap-categories', $part++, $OUT_DIR, $GZIP);
    $indexFiles[] = $f;
}

// KEYWORDS (streaming en chunks)
$part = 1;
$bucket = [];
foreach (buildKeywords($pdo, $BASE_URL, $BASE_PATH, 'daily', '0.7') as $row) {
    $bucket[] = $row;
    if (count($bucket) >= $MAX_URLS_PER_FILE) {
        $f = writeSitemapChunk($bucket, 'sitemap-keywords', $part++, $OUT_DIR, $GZIP);
        $indexFiles[] = $f;
        $bucket = [];
    }
}
if ($bucket) {
    $f = writeSitemapChunk($bucket, 'sitemap-keywords', $part++, $OUT_DIR, $GZIP);
    $indexFiles[] = $f;
}

// INDEX
$idx = writeSitemapIndex($indexFiles, $BASE_URL, $BASE_PATH, $OUT_DIR, $GZIP);

echo "[OK] Generated ".count($indexFiles)." sitemap file(s) + index ($idx) in $OUT_DIR\n";
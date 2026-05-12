<?php
declare(strict_types=1);
if (!defined('CS_CLI')) session_start();

// ── .env ─────────────────────────────────────────────────────────────────────
$_envFile = __DIR__ . '/../.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line[0] === '#' || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!getenv($_k)) putenv("{$_k}={$_v}");
    }
}
unset($_envFile, $_line, $_k, $_v);

// ── Routing (définit $currentDomain, $countryCode, $nicheSlug, etc.) ─────────
require_once __DIR__ . '/routing.php';

// ── Config du site depuis sites.json ─────────────────────────────────────────
$_sitesFile = __DIR__ . '/../sites.json';
$_sites     = file_exists($_sitesFile) ? json_decode(file_get_contents($_sitesFile), true) : [];

// Pour CLI : $currentDomain est déjà le code pays (IE, GB…) ou un domaine
$_key   = $currentDomain;
$_entry = $_sites[$_key] ?? null;
if (is_string($_entry)) {
    $_entry = $_sites[$_entry] ?? null;
} elseif (is_array($_entry) && isset($_entry['ref'])) {
    $_base  = $_sites[$_entry['ref']] ?? [];
    $_entry = array_merge($_base, $_entry);
    unset($_entry['ref']);
}

if (!$_entry) {
    die("Site non configuré dans sites.json pour la clé : {$_key}");
}

$countryCode         = $_entry['country']          ?? 'IE';
$mainLanguage        = $_entry['language']          ?? 'EN';
$currency            = $_entry['currency']          ?? '€';
$priceCurrencySchema = $_entry['currency_schema']   ?? 'EUR';
$countryLabel        = $_entry['country_label']     ?? '';
$ebay_marketplace    = $_entry['ebay_marketplace']  ?? 'EBAY_IE';
$ebay_mkrid          = $_entry['ebay_mkrid']        ?? '';
$ebay_campid         = $_entry['ebay_campid']       ?? '';
$ebay_siteid         = (int)($_entry['ebay_siteid'] ?? 205);
$ebayRootURL         = $_entry['ebay_root_url']     ?? 'https://www.ebay.ie';

unset($_sitesFile, $_sites, $_key, $_entry);

// ── Clés eBay partagées avec le projet de base ────────────────────────────────
define('EBAY_CLIENT_ID',     getenv('EBAY_CLIENT_ID')     ?: '');
define('EBAY_CLIENT_SECRET', getenv('EBAY_CLIENT_SECRET') ?: '');
const CS_EBAY_TOKEN_CACHE = __DIR__ . '/../../scripts/crawler/.ebay_oauth_token.json';

// ── Clés API (depuis .env) ────────────────────────────────────────────────────
define('ANTHROPIC_API_KEY',       getenv('ANTHROPIC_API_KEY')       ?: '');
define('GOOGLE_TRANSLATE_API_KEY', getenv('GOOGLE_TRANSLATE_API_KEY') ?: '');
define('PEXELS_API_KEY',          getenv('PEXELS_API_KEY')          ?: '');
const  ANTHROPIC_MODEL = 'claude-sonnet-4-6';
const  CS_EBAY_PRODUCTS_PER_ARTICLE = 12;

// ── DB unique (CONTENT) depuis .env ──────────────────────────────────────────
try {
    $_db_host    = getenv('DB_HOST')    ?: '127.0.0.1';
    $_db_port    = getenv('DB_PORT')    ?: '8889';
    $_db_name    = getenv('DB_NAME')    ?: 'CONTENT';
    $_db_user    = getenv('DB_USER')    ?: '';
    $_db_pass    = getenv('DB_PASS')    ?: '';

    $pdo = new PDO(
        "mysql:host={$_db_host};port={$_db_port};dbname={$_db_name};charset=utf8mb4",
        $_db_user,
        $_db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('DB connection error: ' . $e->getMessage());
}
unset($_db_host, $_db_port, $_db_name, $_db_user, $_db_pass);

require_once __DIR__ . '/functions-ebay.php';
require_once __DIR__ . '/functions-ai.php';
require_once __DIR__ . '/functions-articles.php';
require_once __DIR__ . '/functions-eeat.php';
require_once __DIR__ . '/../../inc/functions-indexing.php';

// ── URL de base ───────────────────────────────────────────────────────────────
// En local MAMP : content-sites est sous /SH/content-sites/
// En prod       : le vhost pointe directement sur le dossier → base = '/'
$isLocal ??= false; // peut ne pas être défini si routing.php est court-circuité (admin)
$base = $isLocal ? '/SH/content-sites/' : '/';

$nicheBaseUrl = ($SERVER_Protocol ?? 'http') . '://' . ($nicheRootHost ?? 'localhost') . ($portStr ?? '') . $base;
$faviconUrl   = $base . 'favicon.ico';

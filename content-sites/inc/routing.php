<?php
/**
 * Content-Sites — Routing
 *
 * Produit depuis le hostname :
 *   $currentDomain  clé DB du site courant — code pays (IE/GB…) en local/CLI,
 *                   domaine réel (antiques.ie) en production
 *   $countryCode    GB | FR | DE | IT | BE | IE  (depuis sites.json)
 *   $nicheSlug      slug de la niche       (ex: antiques)
 *   $subNicheSlug   slug de la sous-niche  (ex: antique-clocks) ou null
 *   $articleSlug    slug de l'article      (ex: guide-to-buying-...) ou null
 *   $nicheRootHost  domaine racine de la niche (ex: antiques.localhost)
 *   $portStr        ex: ':8888' ou ''
 *   $isLocal        bool
 *
 * Structure hostname :
 *   antiques.localhost                → niche=antiques, sub=null
 *   antique-clocks.antiques.localhost → niche=antiques, sub=antique-clocks
 *   antiques.co.uk                   → niche=antiques, sub=null
 *   antique-clocks.antiques.co.uk    → niche=antiques, sub=antique-clocks
 */

// CLI : $currentDomain déjà défini par le script → on saute tout
if (isset($currentDomain)) return;

// ── Détection host ────────────────────────────────────────────────────────────

$isLocal = false;
if (isset($_SERVER['HTTP_HOST'])) {
    $SERVER_Protocol    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $SERVER_WebsiteName = $SERVER_Protocol . '://' . $_SERVER['HTTP_HOST'];
    $SERVER_PageFullURL = $SERVER_Protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $isLocal = (bool) preg_match('/^(.*\.)?localhost(:\d+)?$/i', $_SERVER['HTTP_HOST']);
}

$hostWithPort = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
$hostParts    = explode(':', $hostWithPort);
$rawHost      = preg_replace('/^www\./', '', $hostParts[0]);
$portStr      = (isset($hostParts[1]) && !in_array($hostParts[1], ['80', '443']))
                ? ':' . $hostParts[1] : '';

$URI = trim($_SERVER['REQUEST_URI'] ?? '/', '/');

// ── Parse le hostname : niche + subniche ──────────────────────────────────────

$parts = explode('.', $rawHost);
$count = count($parts);

$multiPartTlds = ['co.uk', 'ac.uk', 'gov.uk', 'com.au', 'com.br', 'co.nz'];
$tldSuffix     = ($count >= 2) ? $parts[$count - 2] . '.' . $parts[$count - 1] : '';
$isMultiTld    = in_array($tldSuffix, $multiPartTlds, true);

$tldPartCount = $isMultiTld ? 2 : 1;
$nicheIndex   = $count - $tldPartCount - 1;

$nicheSlug    = ($nicheIndex >= 0) ? $parts[$nicheIndex] : null;
$subNicheSlug = ($nicheIndex > 0)  ? $parts[$nicheIndex - 1] : null;

$tldStr        = implode('.', array_slice($parts, -$tldPartCount));
$nicheRootHost = $nicheSlug ? ($nicheSlug . '.' . $tldStr) : $rawHost;

// ── Résolution du site depuis sites.json ──────────────────────────────────────

$_sitesFile = __DIR__ . '/../sites.json';
$_sites     = file_exists($_sitesFile) ? json_decode(file_get_contents($_sitesFile), true) : [];

$_entry = $_sites[$nicheRootHost] ?? null;
if (is_string($_entry)) {
    $_entry = $_sites[$_entry] ?? null;                      // alias string → config pays
} elseif (is_array($_entry) && isset($_entry['ref'])) {
    $_base  = $_sites[$_entry['ref']] ?? [];
    $_entry = array_merge($_base, $_entry);                  // merge : config pays + overrides
    unset($_entry['ref']);
}

// En local : clé DB = code pays (partage data avec CLI)
// En prod  : clé DB = domaine réel (isolation par site)
$currentDomain = $isLocal
    ? ($_entry['country'] ?? 'IE')
    : $nicheRootHost;

$countryCode = $_entry['country'] ?? 'IE';

unset($_sitesFile, $_sites, $_entry);

// ── Article slug ──────────────────────────────────────────────────────────────

$articleSlug = null;

if (!$isLocal && $subNicheSlug !== null && $URI !== '') {
    $articleSlug = $URI;
}

if ($isLocal) {
    $articleSlug = (!empty($_GET['article'])) ? $_GET['article'] : null;
}

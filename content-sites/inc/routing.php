<?php
/**
 * Content-Sites — Routing (subdirectory mode)
 *
 * Produit depuis le hostname et l'URI :
 *   $currentDomain  clé DB du site courant — code pays (IE/GB…) en local/CLI,
 *                   domaine réel (antiques.ie) en production
 *   $countryCode    GB | FR | DE | IT | BE | IE  (depuis sites.json)
 *   $nicheSlug      slug de la niche       (ex: antiques)
 *   $subNicheSlug   slug de la sous-niche  (ex: antique-clocks) ou null
 *   $articleSlug    slug de l'article      (ex: guide-to-buying-...) ou null
 *   $nicheRootHost  domaine de la niche    (ex: antiques.localhost)
 *   $portStr        ex: ':8888' ou ''
 *   $isLocal        bool
 *
 * Structure URL :
 *   antiques.localhost:8888/SH/content-sites/                          → niche homepage
 *   antiques.localhost:8888/SH/content-sites/antique-clocks/           → sous-niche homepage
 *   antiques.localhost:8888/SH/content-sites/antique-clocks/guide-slug → article
 *   antiques.co.uk/                                                     → niche homepage
 *   antiques.co.uk/antique-clocks/                                      → sous-niche homepage
 *   antiques.co.uk/antique-clocks/guide-slug                            → article
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

// ── Parse l'URI : sous-niche + article ───────────────────────────────────────

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$uriBase = $isLocal ? '/SH/content-sites/' : '/';
$relPath  = trim(substr($uriPath, strlen($uriBase)), '/');

$pathSegments = ($relPath !== '') ? explode('/', $relPath) : [];
$subNicheSlug = ($pathSegments[0] ?? '') !== '' ? $pathSegments[0] : null;
$articleSlug  = ($pathSegments[1] ?? '') !== '' ? $pathSegments[1] : null;

// ── Résolution du site depuis sites.json ──────────────────────────────────────

$_sitesFile  = __DIR__ . '/../sites.json';
$_sites      = file_exists($_sitesFile) ? json_decode(file_get_contents($_sitesFile), true) : [];

$nicheRootHost = $rawHost;
$_entry        = $_sites[$nicheRootHost] ?? null;
if (is_string($_entry)) {
    $_entry = $_sites[$_entry] ?? null;                      // alias string → config pays
} elseif (is_array($_entry) && isset($_entry['ref'])) {
    $_base  = $_sites[$_entry['ref']] ?? [];
    $_entry = array_merge($_base, $_entry);                  // merge : config pays + overrides
    unset($_entry['ref']);
}

// Clé DB = code pays (IE/GB…) dans tous les contextes → cohérence avec le CLI
$currentDomain = $_entry['country'] ?? 'IE';

$countryCode = $_entry['country'] ?? 'IE';
$nicheSlug   = $_entry['niche']   ?? null;

unset($_sitesFile, $_sites, $_entry);

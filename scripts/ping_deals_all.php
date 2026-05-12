<?php
/**
 * CLI batch ping — submits all /deals/ URLs to IndexNow + Pingomatic.
 *
 * Usage:
 *   php scripts/ping_deals_all.php IE
 *   php scripts/ping_deals_all.php FR
 *   php scripts/ping_deals_all.php   (pings all countries)
 *
 * Add to bihourly.sh or daily.sh per country.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$root = dirname(__DIR__);
require $root . '/inc/functions-indexing.php';

// Accept country filter via argv
$targetCountry = isset($argv[1]) ? strtoupper(trim($argv[1])) : null;

$catalog  = json_decode(file_get_contents($root . '/assets/JSON/deals_catalog.json'), true) ?? [];
$tenantDir = $root . '/tenants';

// Map countryCode → site name (for Pingomatic blogName)
$siteNames = [
    'IE'    => 'For Sale',
    'GB'    => 'For Sale',
    'FR'    => 'Site Annonce France',
    'DE'    => 'Gebraucht Kaufen',
    'BE'    => 'Site Annonce Belgique',
    'IT'    => 'In Vendita',
    'US'    => 'For Sale USA',
    'EROWZ' => 'eRowz',
];

$countries = $targetCountry
    ? [$targetCountry]
    : array_keys(DEALS_PROD_DOMAINS);

foreach ($countries as $cc) {
    $domain = indexing_prod_domain($cc);
    if (!$domain) {
        echo "[$cc] No production domain configured — skipped.\n";
        continue;
    }
    $siteName = $siteNames[$cc] ?? $cc;

    echo "[$cc] Pinging " . count($catalog) . " categories → {$domain}/deals/...\n";
    deals_ping_all($cc, $siteName, $catalog);
    echo "[$cc] Done.\n";
}

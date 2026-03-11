<?php
/**
 * Deals Price History Crawler
 *
 * Crawls all keywords in deals_catalog.json for a given country and records
 * hourly price snapshots used by the chart on deal pages.
 *
 * Usage (CLI):
 *   php deals_history_crawler.php IE
 *   php deals_history_crawler.php GB
 *
 * One snapshot per keyword per hour max (throttled inside deals_record_snapshot).
 * Add to cron alongside pageAccessor.php (same hourly slot, runs in ~seconds).
 */

require '../../inc/config.php';
require '../../inc/functions-bargain.php';
require '../../inc/functions-deals-history.php';
require 'ebay_browse_crawler.php';

$catalogFile = __DIR__ . '/../../assets/JSON/deals_catalog.json';
if (!file_exists($catalogFile)) {
    exit("deals_catalog.json not found\n");
}

$catalog  = json_decode(file_get_contents($catalogFile), true) ?? [];
$cacheDir = __DIR__ . '/../../cache';
@mkdir($cacheDir, 0755, true);

$EBAY_MARKETPLACE_ID  = $ebay_marketplace;
$EBAY_BROWSE_TOKEN    = get_access_token();
$EBAY_BROWSE_ENDPOINT = 'https://api.ebay.com/buy/browse/v1/item_summary/search';

$total = 0;
$skipped = 0;

echo "=== Deals history crawler | " . date('Y-m-d H:i:s') . " | country: $countryCode ===" . PHP_EOL;

foreach ($catalog as $catSlug => $cat) {
    $minPrice = (int)($cat['min_price'] ?? 0);

    foreach ($cat['keywords'] as $kw) {
        $kwSlug      = $kw['slug'];
        $searchTerm  = str_replace('-', ' ', $kwSlug);
        $safeKey     = preg_replace('/[^a-z0-9_\-]/i', '_', $catSlug . '_' . $kwSlug . '_' . $countryCode);
        $historyFile = $cacheDir . '/prices_' . $safeKey . '.json';

        // Skip if a snapshot was recorded less than 1 hour ago (save API quota)
        $existing = deals_load_history($historyFile);
        if (!empty($existing)) {
            $last = end($existing);
            if (isset($last['ts']) && (time() - (int)$last['ts']) < 3600) {
                echo "  skip  $catSlug/$kwSlug (fresh)" . PHP_EOL;
                $skipped++;
                continue;
            }
        }

        $queryParams = ['q' => $searchTerm, 'limit' => 20, 'offset' => 0];
        $filter      = implode(',', [
            'deliveryCountry:' . $countryCode,
            "price:[{$minPrice}..999999]",
            "priceCurrency:{$priceCurrencySchema}",
        ]);

        $browseData = ebay_browse_search($queryParams, $filter, null, null);

        if ($browseData !== null) {
            $products = map_browse_to_products($browseData, null);
            deals_record_snapshot($historyFile, $products);
            echo "  ok    $catSlug/$kwSlug (" . count($products) . " listings)" . PHP_EOL;
            $total++;
        } else {
            echo "  error $catSlug/$kwSlug" . PHP_EOL;
        }

        sleep(1); // polite pacing — ~1 API call/sec
    }
}

echo "Done. $total snapshots recorded, $skipped skipped." . PHP_EOL;

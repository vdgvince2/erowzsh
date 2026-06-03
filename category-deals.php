<?php
/**
 * API — Live eBay deals for a homepage category.
 * Returns JSON { html, count, category, slug }
 * Cache TTL: 3 minutes per category slug.
 *
 * GET params:
 *   slug  — category URL slug (from categories.url)
 *   limit — number of results (default 30, max 30)
 */
ob_start(); // buffer any accidental output from bootstrap

require_once __DIR__ . '/scripts/crawler/ebay_browse_crawler.php';
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/inc/functions-bargain.php';

ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

// eBay globals (same pattern as bargain.php)
$EBAY_MARKETPLACE_ID  = $ebay_marketplace;
$EBAY_BROWSE_TOKEN    = get_access_token();
$EBAY_BROWSE_ENDPOINT = 'https://api.ebay.com/buy/browse/v1/item_summary/search';

function cat_json_error(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Input ---
$slug      = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'] ?? '')));
$limit     = min(30, max(8, (int)($_GET['limit'] ?? 30)));
$nobids    = isset($_GET['nobids']) && $_GET['nobids'] === '1'; // filter: auctions with 0 bids

if (!$slug) {
    cat_json_error(400, 'Missing slug');
}

// --- Cache (3 min TTL) ---
$cacheDir  = __DIR__ . '/cache';
$cacheSuffix = $nobids ? '_nobids' : '_all';
$cacheFile = $cacheDir . '/homepage_cat_' . $slug . $cacheSuffix . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 180) {
    echo file_get_contents($cacheFile);
    exit;
}

// --- DB: fetch category ---
$stmt = $pdo->prepare('SELECT id, name, url FROM categories WHERE url = ? LIMIT 1');
$stmt->execute([$slug]);
$cat = $stmt->fetch();

if (!$cat) {
    cat_json_error(404, 'Category not found');
}

// --- eBay API ---
$filter = "deliveryCountry:{$countryCode}";
if ($nobids) {
    // Auctions ending soon with exactly 0 bids — prime snipe opportunities
    $filter .= ',buyingOptions:{AUCTION},bidCount:[0..0]';
}
$data = ebay_browse_search(
    ['q' => $cat['name'], 'limit' => $limit],
    $filter,
    null,
    'endingSoonest'
);

$products = ($data && !empty($data['itemSummaries'])) ? map_browse_to_products($data) : [];

// --- Render HTML cards ---
ob_start();
if (empty($products)) {
    echo '<p class="col-span-full text-center text-gray-400 py-12 text-sm">No live deals right now — try again in a few minutes.</p>';
} else {
    foreach ($products as $prod) {
        $score      = computeBargainScore($prod);
        $scoreColor = $score >= 70 ? 'text-green-500' : ($score >= 40 ? 'text-amber-500' : 'text-red-400');
        $link       = htmlspecialchars(tracking_link_builder($cat['name'], $countryCode, $prod['url']), ENT_QUOTES);
        $title      = htmlspecialchars($prod['title_original'], ENT_QUOTES);
        $photo      = htmlspecialchars($prod['photo'] ?? '', ENT_QUOTES);
        $price      = htmlspecialchars($currency . number_format((float)$prod['price'], 2), ENT_QUOTES);
        $isAuction  = !empty($prod['is_auction']);
        $isFreeShip = !empty($prod['is_free_shipping']);
        $discPct    = !empty($prod['marketing_discount_pct']) ? (int)$prod['marketing_discount_pct'] : 0;
        $endTime    = $prod['end_time'] ?? null;
        $shipCost   = isset($prod['shipping_cost']) && $prod['shipping_cost'] > 0
                        ? htmlspecialchars($currency . number_format($prod['shipping_cost'], 0), ENT_QUOTES)
                        : null;
        ?>
        <a href="<?= $link ?>" target="_blank" rel="noopener noreferrer"
           class="group rounded-2xl border border-gray-200 bg-white overflow-hidden hover:shadow-sm transition-shadow flex flex-col">

            <?php if ($photo): ?>
            <div class="aspect-square bg-gray-50 overflow-hidden">
                <img src="<?= $photo ?>" alt="<?= $title ?>"
                     class="w-full h-full object-contain"
                     loading="lazy">
            </div>
            <?php else: ?>
            <div class="aspect-square bg-gray-100 flex items-center justify-center">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 9.75A.75.75 0 013.75 9h16.5a.75.75 0 01.75.75v9a.75.75 0 01-.75.75H3.75A.75.75 0 013 18.75V9.75z"/>
                </svg>
            </div>
            <?php endif; ?>

            <div class="px-3 py-2 flex flex-col gap-1 flex-1">

                <div class="flex items-center justify-between gap-1 flex-wrap">
                    <span class="text-xs font-semibold <?= $scoreColor ?>">🔥 <?= $score ?>/100</span>
                    <?php if ($discPct >= 5): ?>
                        <span class="text-[10px] font-semibold bg-orange-50 text-orange-600 border border-orange-200 px-1.5 py-0.5 rounded-full">-<?= $discPct ?>%</span>
                    <?php elseif ($isAuction): ?>
                        <span class="text-[10px] font-semibold text-purple-600 uppercase tracking-wide">Auction</span>
                    <?php endif; ?>
                </div>

                <p class="text-xs text-gray-800 font-medium line-clamp-2 leading-snug"><?= $title ?></p>

                <div class="mt-auto pt-1 flex items-baseline gap-1 flex-wrap">
                    <span class="text-base font-bold text-gray-900"><?= $price ?></span>
                    <?php if ($isFreeShip): ?>
                        <span class="text-[10px] text-green-600 font-medium">Free</span>
                    <?php elseif ($shipCost): ?>
                        <span class="text-[10px] text-gray-400">+<?= $shipCost ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($isAuction && $endTime): ?>
                <div class="text-[10px] text-orange-500 font-semibold countdown-timer"
                     data-endtime="<?= htmlspecialchars($endTime, ENT_QUOTES) ?>">⏱ …</div>
                <?php endif; ?>

            </div>
        </a>
        <?php
    }
}
$html = ob_get_clean();

$response = json_encode([
    'html'     => $html,
    'count'    => count($products),
    'category' => $cat['name'],
    'slug'     => $slug,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Write to cache
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
file_put_contents($cacheFile, $response, LOCK_EX);

echo $response;

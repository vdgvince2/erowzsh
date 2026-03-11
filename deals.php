<?php
/***************************
 * DEALS PAGE — Curated SEO landing page
 * URL: /deals/{category}/{keyword}
 * Only keywords defined in deals_catalog.json are served. All others → 404.
 ***************************/

ob_start();
$noAds = true;

require __DIR__ . '/scripts/crawler/ebay_browse_crawler.php';
require __DIR__ . '/inc/functions-bargain.php';
require __DIR__ . '/inc/functions-deals-history.php';

// ── Load catalog ─────────────────────────────────────────────────────────────
$catalogFile = __DIR__ . '/assets/JSON/deals_catalog.json';
$catalog     = json_decode(file_get_contents($catalogFile), true) ?? [];

// ── Resolve category + keyword from URL ──────────────────────────────────────
$catSlug     = trim($_GET['deal_cat']     ?? '');
$keywordSlug = trim($_GET['deal_keyword'] ?? '');

// Validate against catalog (any unknown slug → 404)
$catData     = $catalog[$catSlug] ?? null;
$kwData      = null;
if ($catData) {
    foreach ($catData['keywords'] as $kw) {
        if ($kw['slug'] === $keywordSlug) { $kwData = $kw; break; }
    }
}

if (!$catData || !$kwData) {
    http_response_code(404);
    require 'fallback.php';
    exit;
}

$searchTerm     = str_replace('-', ' ', $keywordSlug);
$keywordDisplay = $kwData['label'];

// ── User-adjustable GET params (do NOT affect canonical) ─────────────────────
$sortUi   = in_array($_GET['sort'] ?? '', ['price_asc', 'price_desc', 'ending_soon', 'newly_listed'], true)
            ? $_GET['sort'] : 'best';
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (int)$_GET['max_price'] : '';

// Sort string for eBay API
$sortMap = [
    'price_asc'    => 'price',
    'price_desc'   => '-price',
    'ending_soon'  => 'endingSoonest',
    'newly_listed' => 'newlyListed',
];
$sort = $sortMap[$sortUi] ?? null;

// ── Placeholder helper ────────────────────────────────────────────────────────
function deals_fill(string $tpl, array $vars): string {
    $keys = array_map(fn($k) => '{' . $k . '}', array_keys($vars));
    return str_replace($keys, array_values($vars), $tpl);
}

// ── Page meta (uses language labels) ─────────────────────────────────────────
$dealVars = [
    'keyword'   => $keywordDisplay,
    'currency'  => $currency,
    'min_price' => (string)(int)$catData['min_price'],
    'site'      => $WebsiteName,
];
$pageTitle           = deals_fill($label_deals_page_title,  $dealVars);
$additionnalMetaDesc = deals_fill($label_deals_meta_desc,   $dealVars);
$canonicalUrl        = $rootDomain . $base . 'deals/' . $catSlug . '/' . $keywordSlug;
$extraHeadTags       = '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES) . '">'
                     . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>';

// ── Cache ─────────────────────────────────────────────────────────────────────
$cacheDir    = __DIR__ . '/cache';
$historyFile = $cacheDir . '/prices_' . preg_replace('/[^a-z0-9_\-]/i', '_', $catSlug . '_' . $keywordSlug . '_' . $countryCode) . '.json';
$cacheKey  = $catSlug . '_' . $keywordSlug . '_' . $countryCode . '_' . $sortUi . ($maxPrice !== '' ? '_mp' . $maxPrice : '');
$cacheFile = $cacheDir . '/deals_' . preg_replace('/[^a-z0-9_\-]/i', '_', $cacheKey) . '.json';
$cacheTTL  = 3600;

$products = [];
$errorMsg = null;

$cacheValid = file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL;
if ($cacheValid) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if (is_array($cached)) $products = $cached;
}

if (!$cacheValid || empty($products)) {
    $EBAY_MARKETPLACE_ID  = $ebay_marketplace;
    $EBAY_BROWSE_TOKEN    = get_access_token();
    $EBAY_BROWSE_ENDPOINT = 'https://api.ebay.com/buy/browse/v1/item_summary/search';

    $queryParams = ['q' => $searchTerm, 'limit' => 20, 'offset' => 0];

    $filterParts = ['deliveryCountry:' . $countryCode];
    $minP = (int)$catData['min_price'];
    $maxP = $maxPrice !== '' ? (int)$maxPrice : 999999;
    $filterParts[] = "price:[{$minP}..{$maxP}]";
    $filterParts[] = "priceCurrency:{$priceCurrencySchema}";

    $browseData = ebay_browse_search($queryParams, implode(',', $filterParts), null, $sort);

    if ($browseData !== null) {
        $products = map_browse_to_products($browseData, null);
        // Record history snapshot BEFORE sorting (raw fetch = consistent data)
        @mkdir($cacheDir, 0755, true);
        deals_record_snapshot($historyFile, $products);
        if ($sortUi === 'best' || $sortUi === '') {
            usort($products, function ($a, $b) {
                return computeBargainScore($b) - computeBargainScore($a);
            });
        }
        file_put_contents($cacheFile, json_encode($products));
    } else {
        $errorMsg = 'Unable to load deals at this time. Please try again later.';
    }
}

// ── Price history ─────────────────────────────────────────────────────────────
$rawHistory   = deals_load_history($historyFile);
$dailyHistory = deals_aggregate_daily($rawHistory, 30);
$dailyHistory = deals_smooth_medians($dailyHistory);
$priceTrend   = deals_compute_trend($dailyHistory);
$latestSnap   = !empty($dailyHistory) ? end($dailyHistory) : null;
$chartDays    = count($dailyHistory);

// ── Helpers ───────────────────────────────────────────────────────────────────
function deals_sort_link(string $value, string $label, string $currentSort, string $base_url): string {
    $active = $value === $currentSort;
    $cls = $active
        ? 'bg-blue-600 text-white border-blue-600'
        : 'bg-white border-gray-300 hover:bg-blue-50 hover:border-blue-400';
    return '<a href="' . htmlspecialchars($base_url . '&sort=' . $value, ENT_QUOTES) . '" '
         . 'class="text-sm px-3 py-1 rounded-full border transition ' . $cls . '">'
         . htmlspecialchars($label, ENT_QUOTES) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage); ?>" class="js">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4 sm:py-8">

    <!-- Breadcrumb -->
    <nav class="text-sm text-gray-500 mb-4" aria-label="Breadcrumb">
        <a href="<?= $rootDomain . $base; ?>" class="hover:underline"><?= htmlspecialchars($WebsiteName, ENT_QUOTES); ?></a>
        <span class="mx-1">›</span>
        <span><?= htmlspecialchars($catData['label'], ENT_QUOTES); ?></span>
        <span class="mx-1">›</span>
        <span class="text-gray-900 font-medium"><?= htmlspecialchars($keywordDisplay, ENT_QUOTES); ?></span>
    </nav>

    <!-- ── Tab nav — segmented control ─────────────────────────────────────── -->
    <?php
    if ($priceTrend['direction'] === 'up') {
        $teaserBadge = '<span class="deals-tab-badge bg-gray-200 text-gray-600">↑+' . $priceTrend['pct'] . '%</span>';
    } elseif ($priceTrend['direction'] === 'down') {
        $teaserBadge = '<span class="deals-tab-badge bg-gray-200 text-gray-600">↓' . $priceTrend['pct'] . '%</span>';
    } else {
        $teaserBadge = '';
    }
    ?>
    <div class="sticky top-0 z-20 deals-tab-bar shadow-md -mx-4 px-2 py-2 sm:-mx-6 sm:px-4 lg:-mx-8 lg:px-6 mb-6">
        <div class="grid grid-cols-3 rounded-xl p-1 gap-1 max-w-2xl mx-auto" style="background:rgba(255,255,255,.08);">
            <button data-tab="offers" class="deals-tab active-tab rounded-lg py-2.5 px-1 sm:px-3 transition-all flex items-center justify-center gap-1.5 min-w-0">
                <span class="deals-tab-label">
                    <span class="hidden sm:inline"><?= htmlspecialchars($label_deals_tab_offers ?? 'Offers', ENT_QUOTES); ?></span>
                    <span class="sm:hidden">☰ Offers</span>
                </span>
                <?php if (!empty($products)): ?>
                <span class="deals-tab-badge bg-blue-100 text-blue-700"><?= count($products); ?></span>
                <?php endif; ?>
            </button>
            <button data-tab="chart" class="deals-tab rounded-lg py-2.5 px-1 sm:px-3 transition-all flex items-center justify-center gap-1.5 min-w-0">
                <span class="deals-tab-label">
                    <span class="hidden sm:inline">📈 <?= htmlspecialchars($label_deals_tab_chart ?? 'Price chart', ENT_QUOTES); ?></span>
                    <span class="sm:hidden">📈 Chart</span>
                </span>
                <?= $teaserBadge; ?>
            </button>
            <button data-tab="alert" class="deals-tab rounded-lg py-2.5 px-1 sm:px-3 transition-all flex items-center justify-center gap-1.5 min-w-0">
                <span class="deals-tab-label">
                    <span class="hidden sm:inline">🔔 <?= htmlspecialchars($label_deals_tab_alert ?? 'Set alert', ENT_QUOTES); ?></span>
                    <span class="sm:hidden">🔔 Alert</span>
                </span>
            </button>
        </div>
    </div>

    <!-- ── Tab: Offers ──────────────────────────────────────────────────────── -->
    <div id="tab-offers" class="tab-content">

    <!-- Page title -->
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-bold">
            <?= htmlspecialchars($label_deals_h1, ENT_QUOTES); ?>
            <span class="text-blue-600"><?= htmlspecialchars($keywordDisplay, ENT_QUOTES); ?></span>
        </h1>
        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($catData['label'], ENT_QUOTES); ?></p>
    </div>

    <!-- Mobile filter bar — always visible on mobile, hidden on desktop ───── -->
    <div class="lg:hidden mb-4 bg-gray-100 rounded-xl p-3">
        <form method="get" action="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES); ?>" class="flex gap-2 items-end flex-wrap">
            <div class="flex-1 min-w-0">
                <label class="block text-xs text-gray-500 mb-1"><?= htmlspecialchars($label_deals_sort_label, ENT_QUOTES); ?></label>
                <select name="sort" class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-50">
                    <option value="best"         <?= $sortUi === 'best'         ? 'selected' : ''; ?>><?= htmlspecialchars($label_deals_sort_best,   ENT_QUOTES); ?></option>
                    <option value="price_asc"    <?= $sortUi === 'price_asc'    ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_pricelow,   ENT_QUOTES); ?></option>
                    <option value="price_desc"   <?= $sortUi === 'price_desc'   ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_pricehight, ENT_QUOTES); ?></option>
                    <option value="ending_soon"  <?= $sortUi === 'ending_soon'  ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_endingsoon, ENT_QUOTES); ?></option>
                    <option value="newly_listed" <?= $sortUi === 'newly_listed' ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_newly,      ENT_QUOTES); ?></option>
                </select>
            </div>
            <div class="w-24 flex-shrink-0">
                <label class="block text-xs text-gray-500 mb-1"><?= htmlspecialchars($label_deals_max_price, ENT_QUOTES); ?> (<?= htmlspecialchars($currency, ENT_QUOTES); ?>)</label>
                <input type="number" name="max_price"
                       value="<?= htmlspecialchars($maxPrice !== '' ? $maxPrice : '', ENT_QUOTES); ?>"
                       min="<?= (int)$catData['min_price']; ?>"
                       placeholder="—"
                       class="w-full border rounded-lg px-2 py-2 text-sm bg-gray-50">
            </div>
            <button type="submit" class="flex-shrink-0 bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg text-sm">
                <?= htmlspecialchars($label_deals_apply, ENT_QUOTES); ?>
            </button>
        </form>
    </div>

    <div class="lg:grid lg:grid-cols-3 lg:gap-8">

        <!-- Sidebar — desktop only ─────────────────────────────────────────── -->
        <aside class="hidden lg:block lg:col-span-1">

            <div class="bg-white rounded-xl shadow p-5 mb-5">
                <p class="text-sm text-gray-700 leading-relaxed">
                    <?= htmlspecialchars(deals_fill($label_deals_intro, $dealVars), ENT_QUOTES); ?>
                </p>
            </div>

            <div class="bg-gray-100 rounded-xl p-5 mb-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">⚙ <?= htmlspecialchars($label_deals_filter_title, ENT_QUOTES); ?></h2>
                <form method="get" action="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES); ?>" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            <?= htmlspecialchars($label_deals_max_price, ENT_QUOTES); ?> (<?= htmlspecialchars($currency, ENT_QUOTES); ?>)
                        </label>
                        <input type="number" name="max_price"
                               value="<?= htmlspecialchars($maxPrice !== '' ? $maxPrice : '', ENT_QUOTES); ?>"
                               class="w-full border rounded px-3 py-2 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1"><?= htmlspecialchars($label_deals_sort_label, ENT_QUOTES); ?></label>
                        <select name="sort" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="best"         <?= $sortUi === 'best'         ? 'selected' : ''; ?>><?= htmlspecialchars($label_deals_sort_best,   ENT_QUOTES); ?></option>
                            <option value="price_asc"    <?= $sortUi === 'price_asc'    ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_pricelow,   ENT_QUOTES); ?></option>
                            <option value="price_desc"   <?= $sortUi === 'price_desc'   ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_pricehight, ENT_QUOTES); ?></option>
                            <option value="ending_soon"  <?= $sortUi === 'ending_soon'  ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_endingsoon, ENT_QUOTES); ?></option>
                            <option value="newly_listed" <?= $sortUi === 'newly_listed' ? 'selected' : ''; ?>><?= htmlspecialchars($label_bargain_newly,      ENT_QUOTES); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-2 rounded-lg text-sm">
                        <?= htmlspecialchars($label_deals_apply, ENT_QUOTES); ?>
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">
                    <?= htmlspecialchars($label_deals_more_in, ENT_QUOTES); ?> <?= htmlspecialchars($catData['label'], ENT_QUOTES); ?>
                </h2>
                <ul class="space-y-1">
                    <?php foreach ($catData['keywords'] as $kw2): ?>
                    <li>
                        <?php if ($kw2['slug'] === $keywordSlug): ?>
                            <span class="text-sm font-semibold text-blue-600">→ <?= htmlspecialchars($kw2['label'], ENT_QUOTES); ?></span>
                        <?php else: ?>
                            <a href="<?= $rootDomain . $base; ?>deals/<?= $catSlug; ?>/<?= rawurlencode($kw2['slug']); ?>"
                               class="text-sm text-gray-600 hover:text-blue-600 hover:underline">
                                <?= htmlspecialchars($kw2['label'], ENT_QUOTES); ?>
                            </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </aside>

        <!-- Results ──────────────────────────────────────────────────────────── -->
        <section class="lg:col-span-2">

            <!-- Count + sort chips (desktop) -->
            <div class="hidden lg:flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="text-sm text-gray-500">
                    <?php if (!empty($products)): ?>
                        <?= count($products); ?> <?= htmlspecialchars($label_deals_count, ENT_QUOTES); ?>
                    <?php endif; ?>
                </p>
                <div class="flex gap-2 flex-wrap">
                    <?php
                    $bUrl = htmlspecialchars($canonicalUrl, ENT_QUOTES) . '?sort=';
                    $sortChips = [
                        'best'         => $label_deals_sort_best,
                        'price_asc'    => $label_bargain_pricelow,
                        'price_desc'   => $label_bargain_pricehight,
                        'ending_soon'  => $label_bargain_endingsoon,
                        'newly_listed' => $label_bargain_newly,
                    ];
                    foreach ($sortChips as $val => $lbl):
                        $active = $val === $sortUi;
                        $cls = $active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 hover:bg-blue-50 hover:border-blue-400';
                    ?>
                    <a href="<?= $bUrl . $val . ($maxPrice !== '' ? '&max_price=' . $maxPrice : ''); ?>"
                       class="text-xs px-3 py-1 rounded-full border transition <?= $cls; ?>">
                        <?= htmlspecialchars($lbl, ENT_QUOTES); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Count — mobile only -->
            <p class="lg:hidden text-sm text-gray-500 mb-3">
                <?php if (!empty($products)): ?>
                    <?= count($products); ?> <?= htmlspecialchars($label_deals_count, ENT_QUOTES); ?>
                <?php endif; ?>
            </p>

            <!-- Products -->
            <div id="results">
                <?php render_bargain_results('', $searchTerm, $errorMsg, $products, $currency, $rootDomain, $base, $label_viewdetails, 'standard'); ?>
            </div>

            <!-- CTA → Bargain Finder -->
            <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-5 text-center">
                <p class="text-gray-700 font-medium mb-2">
                    <?= htmlspecialchars($label_deals_cta_text, ENT_QUOTES); ?>
                </p>
                <a href="<?= $rootDomain . $base; ?>s/bargain?q=<?= rawurlencode($searchTerm); ?>&min_price=<?= (int)$catData['min_price']; ?>"
                   class="inline-block bg-blue-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    <?= htmlspecialchars($label_deals_cta_button, ENT_QUOTES); ?>
                </a>
            </div>

        </section>
    </div>
    </div><!-- /tab-offers -->

    <!-- ── Tab: Chart ───────────────────────────────────────────────────────── -->
    <div id="tab-chart" class="tab-content hidden">
    <?php
    $chartTitle = deals_fill($label_deals_chart_title ?? 'Price history — {keyword}', ['keyword' => $keywordDisplay]);

    // Prepare trend text
    $trendVars = ['pct' => $priceTrend['pct']];
    if ($priceTrend['direction'] === 'up') {
        $trendText = deals_fill($label_deals_chart_trend_up   ?? '', $trendVars);
        $trendCls  = 'text-red-600 bg-red-50 border-red-200';
        $trendIcon = '↑';
    } elseif ($priceTrend['direction'] === 'down') {
        $trendText = deals_fill($label_deals_chart_trend_down ?? '', $trendVars);
        $trendCls  = 'text-green-700 bg-green-50 border-green-200';
        $trendIcon = '↓';
    } else {
        $trendText = $label_deals_chart_trend_stable ?? '';
        $trendCls  = 'text-gray-600 bg-gray-50 border-gray-200';
        $trendIcon = '→';
    }
    ?>

    <?php if ($chartDays >= 2): ?>
    <?php
    // Prepare data arrays
    $chartLabels = []; $chartMedian = []; $chartP25 = []; $chartP75 = [];
    foreach ($dailyHistory as $day) {
        $chartLabels[] = $day['date'] ?? '';
        $chartMedian[] = $day['median_smooth'] ?? $day['median'];
        $chartP25[]    = $day['p25'];
        $chartP75[]    = $day['p75'];
    }
    $statsVars = [
        'currency' => $currency,
        'median'   => number_format((float)($latestSnap['median'] ?? 0), 2),
        'p25'      => number_format((float)($latestSnap['p25']    ?? 0), 2),
        'p75'      => number_format((float)($latestSnap['p75']    ?? 0), 2),
        'count'    => (string)($latestSnap['count'] ?? 0),
    ];
    $descVars = ['keyword' => $keywordDisplay, 'days' => (string)$chartDays];
    ?>

    <!-- Trend banner -->
    <div class="border rounded-xl px-4 py-3 mb-6 flex items-center gap-3 <?= $trendCls; ?>">
        <span class="text-2xl font-bold"><?= $trendIcon; ?></span>
        <p class="text-sm font-medium"><?= htmlspecialchars($trendText, ENT_QUOTES); ?></p>
    </div>

    <!-- Chart + explanation -->
    <div class="bg-white rounded-xl shadow p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4"><?= htmlspecialchars($chartTitle, ENT_QUOTES); ?></h2>
        <div class="lg:grid lg:grid-cols-3 lg:gap-8 items-start">
            <div class="lg:col-span-2">
                <canvas id="priceHistoryChart" height="260"></canvas>
            </div>
            <div class="mt-6 lg:mt-0 text-sm text-gray-700 space-y-4">
                <!-- Stats snapshot -->
                <div class="bg-gray-50 rounded-lg p-3 text-xs text-gray-600 space-y-1">
                    <div class="flex justify-between"><span><?= htmlspecialchars($label_deals_chart_median ?? '', ENT_QUOTES); ?></span><strong><?= htmlspecialchars($currency . number_format((float)($latestSnap['median'] ?? 0), 0), ENT_QUOTES); ?></strong></div>
                    <div class="flex justify-between"><span><?= htmlspecialchars($label_deals_chart_range ?? '', ENT_QUOTES); ?></span><strong><?= htmlspecialchars($currency . number_format((float)($latestSnap['p25'] ?? 0), 0) . '–' . $currency . number_format((float)($latestSnap['p75'] ?? 0), 0), ENT_QUOTES); ?></strong></div>
                    <div class="flex justify-between"><span><?= $chartDays; ?> days tracked</span><strong><?= (int)($latestSnap['count'] ?? 0); ?> listings</strong></div>
                </div>
                <p><?= htmlspecialchars(deals_fill($label_deals_chart_desc ?? '', $descVars), ENT_QUOTES); ?></p>
                <p class="text-gray-500 text-xs"><?= htmlspecialchars($label_deals_chart_howto ?? '', ENT_QUOTES); ?></p>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var labels      = <?= json_encode($chartLabels); ?>;
        var median      = <?= json_encode($chartMedian); ?>;
        var p25         = <?= json_encode($chartP25); ?>;
        var p75         = <?= json_encode($chartP75); ?>;
        var labelMedian = <?= json_encode($label_deals_chart_median ?? 'Median price'); ?>;
        var labelRange  = <?= json_encode($label_deals_chart_range  ?? 'Typical range (P25–P75)'); ?>;
        var bandData    = p25.map(function(v, i) { return p75[i] - v; });

        var ctx = document.getElementById('priceHistoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        type: 'line',
                        label: labelMedian,
                        data: median,
                        borderColor: '#2563eb',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563eb',
                        tension: 0.35,
                        order: 1,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: labelRange,
                        data: bandData,
                        backgroundColor: 'rgba(37,99,235,0.10)',
                        borderColor: 'transparent',
                        base: p25,
                        order: 2,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                if (c.datasetIndex === 0) return labelMedian + ': ' + c.parsed.y.toFixed(2);
                                var i = c.dataIndex;
                                return labelRange + ': ' + p25[i].toFixed(2) + '–' + p75[i].toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: { ticks: { callback: function(v) { return v.toFixed(0); } } },
                    x: { ticks: { maxTicksLimit: 8 } }
                }
            }
        });
    });
    </script>

    <?php else: ?>
    <div class="bg-white rounded-xl shadow p-10 text-center text-gray-500">
        <p class="text-4xl mb-4">📊</p>
        <p class="font-medium text-gray-700 mb-2"><?= htmlspecialchars($chartTitle, ENT_QUOTES); ?></p>
        <p class="text-sm"><?= htmlspecialchars($label_deals_chart_nodata ?? '', ENT_QUOTES); ?></p>
    </div>
    <?php endif; ?>
    </div><!-- /tab-chart -->

    <!-- ── Tab: Alert ───────────────────────────────────────────────────────── -->
    <div id="tab-alert" class="tab-content hidden">
    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-xl shadow p-8 text-center">
            <div class="text-5xl mb-4">🔔</div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">
                <?= htmlspecialchars(($label_subscription_H2 ?? 'Be first to see new') . ' ' . $keywordDisplay, ENT_QUOTES); ?>
            </h2>
            <p class="text-sm text-gray-600 mb-6">
                <?= htmlspecialchars($label_subscription_explainer ?? '', ENT_QUOTES); ?>
            </p>
            <form method="post" action="<?= $rootDomain . $base; ?>subscribe" class="space-y-3">
                <input type="hidden" name="keyword" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES); ?>">
                <input type="hidden" name="min_price" value="<?= (int)$catData['min_price']; ?>">
                <input type="hidden" name="source" value="deals">
                <input type="email"
                       name="email"
                       required
                       placeholder="<?= htmlspecialchars($label_subscription_email ?? 'Enter your email', ENT_QUOTES); ?>"
                       class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg text-sm transition">
                    <?= htmlspecialchars($label_subscription_button ?? 'Get alerts in advance', ENT_QUOTES); ?>
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-4"><?= htmlspecialchars($label_contact_nospam ?? '', ENT_QUOTES); ?></p>
        </div>
    </div>
    </div><!-- /tab-alert -->

    <!-- ── Styles + Tab JS ───────────────────────────────────────────────────── -->
    <style>
        /* Segmented tab control */
        .deals-tab-bar { background: #e5e7eb; } /* gray-200 */
        .deals-tab { color: #6b7280; background: transparent; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .deals-tab:hover { color: #111827; }
        .deals-tab.active-tab { background: #fff; color: #2563eb; box-shadow: 0 2px 6px rgba(0,0,0,.15); }
        .deals-tab-label { font-size: .75rem; font-weight: 600; }
        @media (min-width: 640px) { .deals-tab-label { font-size: .875rem; } }
        .deals-tab-badge { font-size: .65rem; font-weight: 700; padding: .1rem .38rem; border-radius: 999px; flex-shrink: 0; line-height: 1.4; }
    </style>
    <script>
    (function() {
        var tabs   = document.querySelectorAll('.deals-tab');
        var panels = document.querySelectorAll('.tab-content');

        function activate(name) {
            tabs.forEach(function(b) {
                b.classList.toggle('active-tab', b.dataset.tab === name);
            });
            panels.forEach(function(p) {
                p.classList.toggle('hidden', p.id !== 'tab-' + name);
            });
            history.replaceState(null, '', window.location.pathname + window.location.search + '#' + name);
        }

        tabs.forEach(function(btn) {
            btn.addEventListener('click', function() { activate(btn.dataset.tab); });
        });

        var hash = (window.location.hash || '').replace('#', '');
        if (hash && document.getElementById('tab-' + hash)) activate(hash);
    })();
    </script>

</main>

<!-- JSON-LD: ItemList -->
<?php if (!empty($products)): ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "Best deals on <?= htmlspecialchars($keywordDisplay); ?> over <?= htmlspecialchars($currency); ?><?= (int)$catData['min_price']; ?>",
  "url": "<?= htmlspecialchars($canonicalUrl); ?>",
  "numberOfItems": <?= count($products); ?>,
  "itemListElement": [
    <?php
    $i = 1; $total = min(10, count($products));
    foreach (array_slice($products, 0, $total) as $prod):
        $comma  = ($i < $total) ? ',' : '';
        $pTitle = addslashes(str_replace(['"', '\\'], ['', ''], $prod['title_original']));
        $pPrice = number_format((float)($prod['price'] ?? 0), 2, '.', '');
        $pUrl   = htmlspecialchars(tracking_link_builder($searchTerm, $countryCode, $prod['url']));
    ?>
    {
      "@type": "ListItem",
      "position": <?= $i; ?>,
      "item": {
        "@type": "Product",
        "name": "<?= $pTitle; ?>",
        "offers": {
          "@type": "Offer",
          "priceCurrency": "<?= $priceCurrencySchema; ?>",
          "price": "<?= $pPrice; ?>",
          "availability": "https://schema.org/InStock",
          "url": "<?= $pUrl; ?>"
        }
      }
    }<?= $comma; ?>
    <?php $i++; endforeach; ?>
  ]
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
</body>
</html>

<?php
    require __DIR__ . '/inc/product-category.php';
?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>
    <!-- Breadcrumbs -->
    <div class="container mx-auto px-4 py-3 text-sm">
        <div class="flex items-center space-x-2 text-gray-600">
            <a href="<?=$rootDomain.$base;?>" class="hover:text-blue-600"><?=$breadcrumb_home;?></a>
            <span>/</span>
            <span class="maxHeightLine"><?php if(isset($breadcrumbLink)) echo $breadcrumbLink; else echo $breadcrumb_all;?></span>
            <span>/</span>
            <span class="font-medium maxHeightLine"><?=ucfirst($pageTitle);?></span>
        </div>
    </div>

    <!-- Main Content — full width -->
    <div class="container mx-auto px-2 sm:px-4">

        <!-- Title + tagline + grid selector + related categories -->
        <div class="bg-white rounded-lg shadow px-4 py-3 mb-3">
            <div class="flex flex-wrap items-start gap-3">
                <!-- H1 -->
                <div class="flex-1 min-w-0">
                    <?php if (isset($matched)): ?>
                    <h1 class="text-xl font-bold">
                        <?= htmlspecialchars(str_replace('{category}', ucfirst($catDisplayName ?? $ebaySearchKeyword), $label_category_page_title ?? $ebaySearchKeyword), ENT_QUOTES) ?>
                    </h1>
                    <?php else: ?>
                    <h1 class="text-xl font-bold"><?= ucfirst($ebaySearchKeyword); ?></h1>
                    <?php endif; ?>
                    <h2 class="text-sm text-gray-500 mt-0.5"><?=ucfirst($ebaySearchKeyword." ".$tagline);?></h2>
                </div>

                <!-- Grid density slider -->
                <div class="flex items-center gap-2 flex-shrink-0 pt-1" id="grid-selector">
                    <!-- dense icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 flex-shrink-0" viewBox="0 0 16 16" fill="currentColor">
                        <rect x="0" y="0" width="4" height="4" rx="0.5"/><rect x="6" y="0" width="4" height="4" rx="0.5"/><rect x="12" y="0" width="4" height="4" rx="0.5"/>
                        <rect x="0" y="6" width="4" height="4" rx="0.5"/><rect x="6" y="6" width="4" height="4" rx="0.5"/><rect x="12" y="6" width="4" height="4" rx="0.5"/>
                        <rect x="0" y="12" width="4" height="4" rx="0.5"/><rect x="6" y="12" width="4" height="4" rx="0.5"/><rect x="12" y="12" width="4" height="4" rx="0.5"/>
                    </svg>
                    <input type="range" id="cols-slider" min="2" max="8" step="1" value="4"
                           class="grid-slider w-28 sm:w-36 accent-blue-600 cursor-pointer h-1.5 rounded-full"
                           title="<?= $label_grid_cols ?? 'Columns per row' ?>">
                    <!-- large icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400 flex-shrink-0" viewBox="0 0 16 16" fill="currentColor">
                        <rect x="0" y="0" width="7" height="7" rx="1"/><rect x="9" y="0" width="7" height="7" rx="1"/>
                        <rect x="0" y="9" width="7" height="7" rx="1"/><rect x="9" y="9" width="7" height="7" rx="1"/>
                    </svg>
                    <span id="cols-label" class="text-xs text-gray-500 w-4 text-right tabular-nums">4</span>
                </div>
            </div>

            <?php if (!empty($relatedCategories)): ?>
            <section class="mt-3">
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($relatedCategories as $cat): ?>
                    <a href="<?=$rootDomain.$base."s".htmlspecialchars($cat['slug_path'], ENT_QUOTES) ?>"
                       class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs hover:shadow-sm hover:border-blue-400 transition relatedbutton">
                        <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>

        <!-- Top description -->
        <div class="mb-3 px-1">
            <?php if(isset($ContentArray['part1'])) echo $ContentArray['part1']; ?>
        </div>

        <?php
        $bargainBannerUrl   = $rootDomain . $base . 's/bargain?q=' . rawurlencode($ebaySearchKeyword);
        $bargainBannerTitle = str_replace('{keyword}', htmlspecialchars($ebaySearchKeyword, ENT_QUOTES), $label_bargain_banner_title ?? 'Looking for more {keyword}?');
        $productIndex = 0;
        ?>

        <!-- Product Grid -->
        <section id="results" class="product-grid grid gap-1 sm:gap-1.5">

        <?php foreach ($products as $prod):
            $productIndex++;
            $AffiliateSearchLink    = tracking_link_builder($ebaySearchKeyword, $countryCode, null, null, null);
            $AffiliateSearchLinkB64 = base64_encode($AffiliateSearchLink);

            try {
                $titleGenerator = new titleGenerator();
                $adTitle = $titleGenerator->fullprocess($prod['title_original'], $prod['title_original'], $countryCode, $mainLanguage, "nodebug");
                $adTitle = htmlspecialchars($adTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } catch(Throwable $e) {
                $adTitle = htmlspecialchars($prod['title_original'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            $watchCount = (int)($prod['watch_count'] ?? 0);
        ?>
        <!-- Product Card -->
        <div class="bg-white rounded-lg overflow-hidden product-card transition duration-200 flex flex-col clickable-product cursor-pointer"
             data-url="<?= $AffiliateSearchLinkB64 ?>">

            <!-- Image — no background, edge to edge -->
            <div class="relative overflow-hidden" style="padding-top:85%">
                <img src="<?=$rootDomainForAssets;?>image.php?url=<?= base64_encode($prod['photo']) ?>"
                     alt="<?= $adTitle ?>"
                     class="absolute inset-0 w-full h-full object-cover object-center"
                     fetchpriority="<?= $productIndex <= 12 ? 'high' : 'auto' ?>"
                     width="200" height="200">
                <?php if ($watchCount > 0): ?>
                <span class="absolute top-1 left-1 bg-white/90 text-gray-700 text-[10px] font-semibold px-1.5 py-0.5 rounded-full leading-none flex items-center gap-0.5 shadow-sm">
                    <?= $watchCount ?> <span class="text-red-400">♥</span>
                </span>
                <?php endif; ?>
            </div>

            <!-- Card body -->
            <div class="flex flex-col flex-1 px-1.5 pt-1 pb-1.5 gap-1">
                <p class="text-[11px] sm:text-xs font-medium text-gray-800 leading-tight line-clamp-2 flex-1">
                    <?= $adTitle ?>
                </p>
                <p class="text-[11px] sm:text-xs leading-none">
                    <strong class="text-gray-900"><?=$currency;?><?= htmlspecialchars($prod['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                    <span class="text-gray-400 text-[10px]"> · Buy It Now</span>
                </p>
                <span class="block w-full bg-blue-500 text-white text-[10px] sm:text-xs font-semibold py-1 rounded text-center leading-tight">
                    &#187; <?= $label_viewdetails ?? 'See on eBay' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
        </section>

        <!-- Bargain banner — replaces pagination, entire banner is clickable -->
        <a href="<?= $bargainBannerUrl ?>" class="mt-4 bg-gradient-to-r from-blue-600 to-blue-500 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-white hover:from-blue-700 hover:to-blue-600 transition block">
            <div>
                <p class="font-semibold text-sm"><?= $bargainBannerTitle ?></p>
                <p class="text-xs text-blue-100 mt-0.5"><?= htmlspecialchars($label_bargain_banner_desc ?? '', ENT_QUOTES) ?></p>
            </div>
            <span class="flex-shrink-0 bg-white text-blue-700 font-semibold text-sm px-4 py-2 rounded-full whitespace-nowrap">
                🔍 <?= htmlspecialchars($label_bargain_live_btn ?? 'Search live deals', ENT_QUOTES) ?>
            </span>
        </a>

        <!-- Bargain search form (keyword pages) -->
        <?php if (!isset($_GET['categ'])): ?>
        <div class="mt-3 bg-white rounded-xl shadow p-4 flex flex-col gap-3">
            <form method="get" action="<?= $rootDomain . $base ?>s/bargain" class="flex gap-2 items-center">
                <input type="text" name="q"
                       value="<?= htmlspecialchars($ebaySearchKeyword, ENT_QUOTES) ?>"
                       class="flex-1 min-w-0 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 bg-gray-50">
                <button type="submit"
                        class="flex-shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-full transition whitespace-nowrap">
                    🔍 <?= htmlspecialchars($label_bargain_live_btn ?? 'Search live deals', ENT_QUOTES) ?>
                </button>
            </form>
            <?php if (isset($kwPriceStats)):
                $kwCtxText = str_replace(
                    ['{count}', '{currency}{min}', '{currency}{avg}'],
                    [$kwPriceStats['count'], $currency.$kwPriceStats['min'], $currency.$kwPriceStats['avg']],
                    $label_kw_price_context ?? '{count} listings · from {currency}{min} · avg {currency}{avg}'
                ); ?>
            <div class="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                <span>📊</span><span><?= htmlspecialchars($kwCtxText, ENT_QUOTES) ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($kwDaysSinceUpdate) && $kwDaysSinceUpdate > 7):
                $kwFreshnessUrl = $rootDomain . $base . 's/bargain?q=' . rawurlencode($ebaySearchKeyword); ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg px-3 py-2 text-sm text-yellow-800 flex items-center justify-between gap-3">
                <span><?= htmlspecialchars(str_replace('{days}', $kwDaysSinceUpdate, $label_kw_stale_notice ?? '⚠️ Listings last updated {days} days ago.'), ENT_QUOTES) ?></span>
                <a href="<?= $kwFreshnessUrl ?>" class="flex-shrink-0 text-yellow-700 underline font-medium whitespace-nowrap">
                    <?= htmlspecialchars($label_kw_stale_cta ?? 'Search fresh listings', ENT_QUOTES) ?>
                </a>
            </div>
            <?php endif; ?>
            <?php if (isset($kwPriceStats)):
                $kwIntroShort = str_replace(
                    ['{count}', '{keyword}', '{currency}{min}', '{currency}{max}', '{currency}{avg}'],
                    [$kwPriceStats['count'], htmlspecialchars($ebaySearchKeyword, ENT_QUOTES), $currency.$kwPriceStats['min'], $currency.$kwPriceStats['max'], $currency.$kwPriceStats['avg']],
                    $label_kw_intro_short ?? ''
                );
                if ($kwIntroShort): ?>
            <p class="text-sm text-gray-600 leading-relaxed"><?= $kwIntroShort ?></p>
            <?php endif; endif; ?>
        </div>
        <?php endif; ?>

        <!-- Try this — related keywords -->
        <?php
        $topKeywords = array_splice($relatedKeywords, 0, 10);
        if (!empty($topKeywords)) {
            $links = [];
            foreach ($topKeywords as $kw) {
                $rawHref = $kw['keywordURL'];
                if ($kw['source'] === 'subdomain') {
                    $href = normalizeRootDomain($rawHref, $rootDomain, $SERVER_Protocol, $base);
                } else {
                    $href = htmlspecialchars($rootDomain.$base.$rawHref ?: '#', ENT_QUOTES, 'UTF-8');
                }
                $links[] = "<a href=\"{$href}\" class=\"text-gray-600\">{$kw['keyword_name']}</a>";
            }
            echo '<p class="text-sm text-gray-500 mt-6 mb-2">'.$label_topTemplate_related.implode(' | ', $links).'</p>';
        }
        ?>

        <!-- Middle description -->
        <div class="mb-4 mt-3 px-1">
            <?php if(isset($ContentArray['part2'])) echo $ContentArray['part2']; ?>
        </div>
    </div><!-- /container -->

    <section class="mt-8 mb-5 px-4" id="makemoney">
    <?php if (!$isLocal && !isset($noAds)) echo $googleadsense_body; ?>
    </section>

    <div class="container mx-auto px-4">
    <?php if (!empty($subDomainInternalLinks)): ?>
    <section class="mt-8" id="related-categories">
        <h2 class="text-lg font-semibold"><?=$label_template_internalLinkingSubdom;?></h2>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach ($subDomainInternalLinks as $link): ?>
            <a href="<?=normalizeRootDomain($link['subdomain'], $rootDomain, $SERVER_Protocol, $base);?>"
               class="rounded-xl border border-gray-200 bg-white px-4 hover:shadow-sm relatedbutton">
                <?= htmlspecialchars($link['keyword_name'], ENT_QUOTES) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($relatedLevel1Categories) && $isSub == false): ?>
    <section class="mt-8" id="related-categories">
        <h2 class="text-lg font-semibold"><?= htmlspecialchars($sectionLevel1Title, ENT_QUOTES) ?></h2>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach ($relatedLevel1Categories as $catL1): ?>
            <a href="<?=$rootDomain.$base."s".htmlspecialchars($catL1['slug_path'], ENT_QUOTES); ?>"
               class="rounded-xl border border-gray-200 bg-white px-4 hover:shadow-sm relatedbutton">
                <?= htmlspecialchars($catL1['name'], ENT_QUOTES) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($relatedKeywords) && $isSub == false): ?>
    <section class="mt-8" id="related-keywords">
        <h2 class="text-lg font-semibold"><?=$label_related;?></h2>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($relatedKeywords as $kw): ?>
            <a href="<?= $rootDomain.$base.htmlspecialchars($kw['keywordURL'], ENT_QUOTES) ?>"
               class="rounded-xl border px-4 hover:shadow-sm relatedbutton">
                <?= htmlspecialchars($kw['keyword_name'], ENT_QUOTES) ?>
            </a>
        <?php endforeach; ?>
        </div>
        <div class="mb-4 mt-3">
            <?php if(isset($ContentArray['part3'])) echo "<h3 class='text-xl mb-4'>".$label_FAQ."</h3>".$ContentArray['part3']; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!isset($_GET['categ'])):
        $priceRanges = [
            ['label' => '< '.$currency.'25',                  'filter' => '&_udhi=25'],
            ['label' => $currency.'25 – '.$currency.'50',     'filter' => '&_udlo=25&_udhi=50'],
            ['label' => $currency.'50 – '.$currency.'100',    'filter' => '&_udlo=50&_udhi=100'],
            ['label' => $currency.'100 – '.$currency.'200',   'filter' => '&_udlo=100&_udhi=200'],
            ['label' => $currency.'200 – '.$currency.'500',   'filter' => '&_udlo=200&_udhi=500'],
            ['label' => '> '.$currency.'500',                 'filter' => '&_udlo=500'],
        ]; ?>
    <section class="mt-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3"><?= htmlspecialchars($label_price_ranges ?? 'Shop by budget', ENT_QUOTES) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($priceRanges as $range):
                $url = tracking_link_builder($ebaySearchKeyword, $countryCode, null, $range['filter'], null); ?>
            <a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"
               target="_blank" rel="noopener sponsored"
               class="px-3 py-1.5 border border-gray-200 rounded-full text-sm text-gray-600 hover:border-blue-400 hover:text-blue-600 transition">
                <?= htmlspecialchars($range['label'], ENT_QUOTES) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php
    if (isset($matched['url'])) {
        render_deals_widget($matched['url'], $rootDomain, $base, $currency);
    }

    if (!isset($_GET['categ']) && isset($kwPriceStats)) {
        $kwFullText = str_replace(
            ['{count}', '{keyword}', '{currency}{min}', '{currency}{max}', '{currency}{avg}'],
            [$kwPriceStats['count'], htmlspecialchars($ebaySearchKeyword, ENT_QUOTES), $currency.$kwPriceStats['min'], $currency.$kwPriceStats['max'], $currency.$kwPriceStats['avg']],
            $label_kw_intro_full ?? ''
        );
        $kwFullH2 = str_replace('{keyword}', htmlspecialchars($ebaySearchKeyword, ENT_QUOTES), $label_kw_intro_full_h2 ?? 'About {keyword}');
        if ($kwFullText): ?>
    <section id="kw-full-desc" class="mt-10 bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold mb-3"><?= $kwFullH2 ?></h2>
        <p class="text-sm text-gray-700 leading-relaxed"><?= $kwFullText ?></p>
    </section>
    <?php endif; }
    ?>
    </div><!-- /container -->

    <?php require __DIR__ . '/inc/footer.php'; ?>
    <?php require __DIR__ . '/inc/jsonld.php'; ?>

    <!-- Grid density slider script -->
    <script>
    (function() {
        var grid    = document.getElementById('results');
        var slider  = document.getElementById('cols-slider');
        var label   = document.getElementById('cols-label');
        var key     = 'sh_grid_cols';

        // Breakpoint-aware defaults: desktop ≥768px → 6, mobile → 4
        var defaultCols = window.innerWidth >= 768 ? 6 : 4;
        slider.min   = 2;
        slider.max   = window.innerWidth >= 768 ? 8 : 6;
        slider.value = defaultCols;

        function applyGrid(n) {
            if (!grid) return;
            // strip all existing grid-cols-* classes
            grid.className = grid.className.replace(/\bgrid-cols-\d+\b/g, '').trim();
            grid.classList.add('grid-cols-' + n);
            label.textContent = n;
            slider.value = n;
            try { localStorage.setItem(key, n); } catch(e) {}
        }

        slider.addEventListener('input', function() { applyGrid(parseInt(this.value)); });

        // Restore saved preference
        try {
            var saved = parseInt(localStorage.getItem(key));
            if (saved >= 2 && saved <= 8) { applyGrid(saved); return; }
        } catch(e) {}
        applyGrid(defaultCols);
    })();
    </script>

    <!-- C — Sticky bottom bar, mobile only -->
    <div id="bargain-sticky"
         class="lg:hidden fixed bottom-0 left-0 right-0 z-40 bg-blue-600 text-white px-4 py-3 flex items-center justify-between gap-3 shadow-lg translate-y-full transition-transform duration-300">
        <span class="text-sm font-medium truncate">🔍 <?= htmlspecialchars($ebaySearchKeyword, ENT_QUOTES) ?></span>
        <a href="<?= $bargainBannerUrl ?? ($rootDomain . $base . 's/bargain?q=' . rawurlencode($ebaySearchKeyword)) ?>"
           class="flex-shrink-0 bg-white text-blue-700 font-semibold text-xs px-3 py-1.5 rounded-full hover:bg-blue-50 transition whitespace-nowrap">
            <?= htmlspecialchars($label_bargain_sticky_cta ?? 'Search live on eBay', ENT_QUOTES) ?>
        </a>
        <button onclick="document.getElementById('bargain-sticky').classList.add('hidden')"
                class="flex-shrink-0 text-blue-200 hover:text-white text-lg leading-none">✕</button>
    </div>
    <script>
    (function() {
        var bar = document.getElementById('bargain-sticky');
        if (!bar) return;
        var shown = false;
        window.addEventListener('scroll', function() {
            var scrolled = window.scrollY > 320;
            if (scrolled && !shown) { bar.classList.remove('translate-y-full'); shown = true; }
            else if (!scrolled && shown) { bar.classList.add('translate-y-full'); shown = false; }
        }, { passive: true });
    })();
    </script>
</body>
</html>

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

    <!-- Main Content -->
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row">
            <!-- Filters Sidebar -->
            <?php require __DIR__ . '/inc/sidebar.php'; ?>
            
            <!-- Products List -->
            <div class="w-full md:w-3/4 lg:w-4/5">
                <div class="bg-white rounded-lg shadow p-4 mb-4">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <?php if (isset($matched)): // category page — enriched H1 ?>
                        <h1 class="text-xl font-bold mb-2 md:mb-0">
                            <?= htmlspecialchars(str_replace('{category}', ucfirst($catDisplayName ?? $ebaySearchKeyword), $label_category_page_title ?? $ebaySearchKeyword), ENT_QUOTES) ?>
                        </h1>
                        <?php else: // keyword page — keep original title ?>
                        <h1 class="text-xl font-bold mb-2 md:mb-0"><?= ucfirst($ebaySearchKeyword); ?></h1>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-sm text-gray-600"><?=ucfirst($ebaySearchKeyword." ".$tagline);?></h2>

                    <!-- A — Bargain Finder search bar injected after 5th product (see loop below) -->

                    <?php
                    /* internal linking categories */
                    if (!empty($relatedCategories)): ?>
                    <section class="mt-3 mb-4">
                    <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <?php foreach ($relatedCategories as $cat): ?>
                        <a href="<?=$rootDomain.$base."s".htmlspecialchars($cat['slug_path'], ENT_QUOTES) ?>"
                            class="rounded-xl border border-gray-200 bg-white px-3 hover:shadow-sm relatedbutton">
                            <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    </section>                    
                    <?php endif; ?>   

                </div>

                <!-- top description -->
                <div class="mb-4">
                    <?php if(isset($ContentArray['part1'])) echo $ContentArray['part1']; ?>
                </div>
                
                <!-- Product Grid -->
                <section id="results" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <?php
                $bargainBannerUrl     = $rootDomain . $base . 's/bargain?q=' . rawurlencode($ebaySearchKeyword);
                $bargainBannerTitle   = str_replace('{keyword}', htmlspecialchars($ebaySearchKeyword, ENT_QUOTES), $label_bargain_banner_title ?? 'Looking for more {keyword}?');
                $productIndex = 0;
                foreach ($products as $prod) :
                $productIndex++;

                // Prepare eBay link Tracker for both : categories & products
                    $AffiliateSearchLink = tracking_link_builder($ebaySearchKeyword, $countryCode, null, null, null);
                    $AffiliateSearchLink  = base64_encode($AffiliateSearchLink);
                ?>
                <!-- Product Card 1 -->                            
                   <div class="bg-white rounded-lg shadow overflow-hidden product-card transition duration-300 clickable-product cursor-pointer flex flex-row md:flex-col" 
                        data-url="<?= $AffiliateSearchLink; ?>">
                        <div class="flex-shrink-0 w-32 md:w-full flex flex-col">
                            <div class="w-32 h-32 md:w-full md:h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
                                <img src="<?=$rootDomainForAssets;?>image.php?url=<?= base64_encode($prod['photo']) ?>" 
                                    alt="<?= htmlspecialchars($prod['title_original'] ?? 'Image produit', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" 
                                    class="max-w-full max-h-full object-contain" fetchpriority="high" width=128 height=128>
                                <?php /* <div class="top-2 left-2 bg-red-600 text-white text-xs px-2 py-1 rounded"><?= randomSticker();?></div> */?>
                                
                            </div>
                        <span class="block w-full text-xs text-gray-500 mt-1 text-center">#sponsored</span>
                        </div>
                        <div class="flex-1 flex flex-col gap-2 p-3 md:p-4">
                            <span class="font-bold text-m mb-1 line-clamp-2">
                                <?php
                                try{
                                    $titleGenerator = new titleGenerator();
                                    $adTitle = $titleGenerator->fullprocess($prod['title_original'], $prod['title_original'], $countryCode, $mainLanguage, "nodebug");
                                    echo htmlspecialchars($adTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                }catch(Throwable $e){
                                    //echo $e;
                                    echo htmlspecialchars($prod['title_original'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                }; 
                                ?> - <?=$currency;?><?= htmlspecialchars($prod['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <div class="items-center justify-between min-h-[80px]">
                                
                                <div class="flex items-start gap-2 text-sm text-gray-600">
                                    <img src="<?=$rootDomainForAssets;?>assets/online-shopping.png" alt="<?=$label_addtocart;?>" width="16" height="16" class="hrink-0 w-4 h-4 object-contain" />
                                    <span><?php if($prod['description_itemspecs'] != null) echo html_entity_decode($prod['description_itemspecs'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                                    - <?=$label_freepostage;?> - <?=$label_condition;?></span>
                                </div>
                            </div>
                            <div class="mt-4">                              
                                <button class="w-full bg-blue-500 text-white py-2 rounded-md mt-3"><strong>&raquo;</strong> <?=$label_viewdetails;?></button>                                
                            </div>
                        </div>
                    </div>
                <?php
                // A — Bargain Finder search bar + info blocks after 5th product (keyword pages only)
                if ($productIndex === 5 && !isset($_GET['categ'])): ?>
                <div class="col-span-full bg-white rounded-xl shadow p-4 flex flex-col gap-3">
                    <form method="get" action="<?= $rootDomain . $base ?>s/bargain"
                          class="flex gap-2 items-center">
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
                        <span>📊</span>
                        <span><?= htmlspecialchars($kwCtxText, ENT_QUOTES) ?></span>
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
                <?php
                // B — Banner after 4th product
                if ($productIndex === 4): ?>
                <div class="col-span-full bg-gradient-to-r from-blue-600 to-blue-500 rounded-xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3 text-white">
                    <div>
                        <p class="font-semibold text-sm"><?= $bargainBannerTitle ?></p>
                        <p class="text-xs text-blue-100 mt-0.5"><?= htmlspecialchars($label_bargain_banner_desc ?? '', ENT_QUOTES) ?></p>
                    </div>
                    <a href="<?= $bargainBannerUrl ?>"
                       class="flex-shrink-0 bg-white text-blue-700 font-semibold text-sm px-4 py-2 rounded-full hover:bg-blue-50 transition whitespace-nowrap">
                        🔍 <?= htmlspecialchars($label_bargain_live_btn ?? 'Search live deals', ENT_QUOTES) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </section>


            <!-- Pagination -->            
            <div class="flex flex-wrap items-center">  
                <?php
                // Add the pagination in the custom ID.
                $AffiliateSearchLink = str_replace("customid=".$countryCode."_", "customid=".$countryCode."_PAGINATION_", $AffiliateSearchLink);
                ?> 
            <div class="w-full items-center justify-center flex mt-8 items-center clickable-product cursor-pointer" data-url="<?= $AffiliateSearchLink; ?>">
                <nav class="w-full items-center justify-center flex items-center space-x-1">
                    <?php
                    $i = 0; $imax = 7;
                    for($i=0;$i<=$imax;$i++){

                        $sign = $i;
                        if($i == 0) $sign = "←";
                        if($i == $imax) $sign = "→";

                        echo '<button class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">'.$sign.'</button>';

                    }
                    ?>                       
                </nav>
            </div> 
            </div>                        
            <!-- Try this — related keywords (below the fold) -->
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
            <!-- middle description -->
            <div class="mb-4 mt-3">
                <?php if(isset($ContentArray['part2'])) echo $ContentArray['part2']; ?>
            </div>              
        </div>
    </div>
    
    <section class="mt-8 mb-5" id="makemoney"> 
    <?php
    if (!$isLocal && !isset($noAds)) {
        echo $googleadsense_body;
    }
    ?>
    </section>
    <?php
    /* internal linking SUB DOMAIN */
    if (!empty($subDomainInternalLinks)): ?>
    <section class="mt-8"  id="related-categories">
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

    <?php
    /* internal linking CATEGORIES */
    if (!empty($relatedLevel1Categories) && $isSub == false): ?>
    <section class="mt-8"  id="related-categories">
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

    <?php 
    /* internal linking PRODUCTS */
    if (!empty($relatedKeywords) && $isSub == false): ?>
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
            <!-- middle description -->
            <div class="mb-4 mt-3">
                <?php if(isset($ContentArray['part3'])) echo "<H3 class='text-xl mb-4'>".$label_FAQ."</H3>".$ContentArray['part3']; ?>
            </div>          
    </section>
    <?php endif; ?>


    <?php
    // Price range links — simple eBay search links filtered by budget
    if (!isset($_GET['categ'])):
        $priceRanges = [
            ['label' => '< '.$currency.'25',                  'filter' => '&_udhi=25'],
            ['label' => $currency.'25 – '.$currency.'50',     'filter' => '&_udlo=25&_udhi=50'],
            ['label' => $currency.'50 – '.$currency.'100',    'filter' => '&_udlo=50&_udhi=100'],
            ['label' => $currency.'100 – '.$currency.'200',   'filter' => '&_udlo=100&_udhi=200'],
            ['label' => $currency.'200 – '.$currency.'500',   'filter' => '&_udlo=200&_udhi=500'],
            ['label' => '> '.$currency.'500',                 'filter' => '&_udlo=500'],
        ];
    ?>
    <section class="mt-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3"><?= htmlspecialchars($label_price_ranges ?? 'Shop by budget', ENT_QUOTES) ?></h2>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($priceRanges as $range):
                $url = tracking_link_builder($ebaySearchKeyword, $countryCode, null, $range['filter'], null);
            ?>
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
    // Deals catalog widget — category pages only (links to curated /deals/ pages)
    if (isset($matched['url'])) {
        render_deals_widget($matched['url'], $rootDomain, $base, $currency);
    }

    // 3 — Full keyword text (keyword pages only)
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

    <?php require __DIR__ . '/inc/footer.php'; ?>

    <?php require __DIR__ . '/inc/jsonld.php'; ?>

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
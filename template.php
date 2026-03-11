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
                        <h1 class="text-xl font-bold mb-2 md:mb-0"><?=ucfirst($ebaySearchKeyword);?></h1>                        
                    </div>
                    <h2 class="text-sm text-gray-600"><?=ucfirst($ebaySearchKeyword." ".$tagline);?></h2>

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

                    <?php /* internal linking products toppage */
                    $topKeywords = array_splice($relatedKeywords, 0, 10);
                    if (!empty($topKeywords)) {
                        $links = [];

                        foreach ($topKeywords as $kw) {
                            
                            $label = $kw['keyword_name'];
                            $rawHref = $kw['keywordURL'];
                            
                            // SPECIAL LINK for subdomains
                            if($kw['source']=="subdomain"){
                                $href = normalizeRootDomain($rawHref, $rootDomain, $SERVER_Protocol, $base);                                
                            }elseif($kw['source']=="maindomain"){
                                $href = htmlspecialchars($rootDomain.$base.$rawHref ?: '#', ENT_QUOTES, 'UTF-8');
                            }
                            
                            $links[] = "<a href=\"{$href}\" class=\"text-gray-600\">{$label}</a>";
                        }

                        echo '<p class="text-sm maxHeightLine text-gray-600">'.$label_topTemplate_related.implode(' | ', $links).'</p>';
                    }
                    ?>
                </div>

                <!-- top description -->
                <div class="mb-4">
                    <?php if(isset($ContentArray['part1'])) echo $ContentArray['part1']; ?>
                </div>
                
                <!-- Product Grid -->
                <section id="results" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <?php foreach ($products as $prod) : 
                // Prepare eBay link Tracker for both : categories & products
                    $AffiliateSearchLink = tracking_link_builder($ebaySearchKeyword, $countryCode, null, null, null);
                    $AffiliateSearchLink  = base64_encode($AffiliateSearchLink);
                ?>
                <!-- Product Card 1 -->                            
                   <div class="bg-white rounded-lg shadow overflow-hidden product-card transition duration-300 clickable-product cursor-pointer flex flex-row md:flex-col" 
                        data-url="<?= $AffiliateSearchLink; ?>">
                        <div class="flex-shrink-0 w-24 md:w-full flex flex-col">
                            <div class="w-24 h-24 md:w-full md:h-48 bg-gray-50 flex items-center justify-center overflow-hidden">
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
    // Deals widget — shown on DIY/tools category pages
    if (isset($matched['url'])) {
        require_once __DIR__ . '/inc/functions-bargain.php';
        render_deals_widget($matched['url'], $rootDomain, $base, $currency);
    }
    ?>

    <?php require __DIR__ . '/inc/footer.php'; ?>

    <?php require __DIR__ . '/inc/jsonld.php'; ?>
</body>
</html>
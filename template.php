<?php
    require __DIR__ . '/inc/config.php'; 
    require __DIR__ . '/inc/functions.php'; 
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
            <span class="font-medium maxHeightLine"><?=$pageTitle;?></span>
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
                        <div class="flex items-center mb-2">
                            <span class="text-sm text-gray-600"></span>
                            <!--<form action="" method="">
                            <select class="border border-gray-300 px-3 py-1 rounded text-sm mb-5">
                                <option><?=$sortingArray[0];?></option>
                                <option><?=$sortingArray[1];?></option>
                                <option><?=$sortingArray[2];?></option>
                                <option><?=$sortingArray[3];?></option>
                            </select>
                            </form>-->
                        </div>
                    </div>
                    <h2 class="text-sm text-gray-500"><?=ucfirst($ebaySearchKeyword." ".$tagline);?></h2>

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
                            
                            $href = htmlspecialchars($rootDomain.$base.$rawHref ?: '#', ENT_QUOTES, 'UTF-8');
                            $links[] = "<a href=\"{$href}\">{$label}</a>";
                        }

                        echo '<p class="text-sm maxHeightLine">'.$label_topTemplate_related.implode(' | ', $links).'</p>';
                    }
                    ?>
                </div>

                <!-- top description -->
                <div class="mb-4">
                    <?php if(isset($ContentArray['part1'])) echo $ContentArray['part1']; ?>
                </div>
                
                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                
                 <?php foreach ($products as $prod) : ?>
                <!-- Product Card 1 -->                            
                    <div class="clickable-product cursor-pointer" data-url="<?= base64_encode($AffiliateSearchLink) ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 bg-white rounded-lg shadow overflow-hidden product-card transition duration-300">
                        <div class="relative">
                            <img src="<?=$rootDomain.$base;?>image.php?url=<?= base64_encode($prod['photo']) ?>" alt="<?= htmlspecialchars($prod['title_original'] ?? 'Image produit', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" class="w-full h-48 object-cover" fetchpriority="high">
                            <div class="absolute top-2 left-2 bg-red-600 text-white text-xs px-2 py-1 rounded"><?= randomSticker();?></div>
                        </div>
                        <div class="px-2">
                            <h3 class="font-medium text-sm mb-1 line-clamp-2 prettyprint">
                                <?php
                                try{
                                    $titleGenerator = new titleGenerator();
                                    $adTitle = $titleGenerator->fullprocess($prod['title_original'], $prod['title_original'], $countryCode, $mainLanguage, "nodebug");
                                    echo htmlspecialchars($adTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                }catch(Throwable $e){
                                    //echo $e;
                                    echo htmlspecialchars($prod['title_original'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                                }; 
                                ?></h3>
                            <div class="items-center justify-between">
                                <strong><?=$currency;?><?= htmlspecialchars($prod['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                                <p class="text-sm text-gray-600">
                                    <?=$label_freepostage;?><br>
                                    <?=$label_condition;?>
                                </p>
                            </div>
                            <div class="mt-4">
                                <button class="w-full border border-bluecustom rounded-md bluecustom"><?=$label_addtocart;?></button>
                                <button class="w-full bg-bluecustom text-white py-2 rounded-md mt-3"><?=$label_viewdetails;?></button>
                                <span class="text-xs text-gray-500">#sponsored</span>
                            </div>
                        </div>
                    </div>
                 </div>
                <?php endforeach; ?>                                     
            </div>
            <!-- Pagination -->            
            <div class="flex flex-wrap items-center">   
            <div class="w-full items-center justify-center flex mt-8 items-center clickable-product cursor-pointer" data-url="<?= base64_encode($AffiliateSearchLink) ?>">
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

    <!-- email subscription -->
    <?php 
    /* 1/12/2025 : temporary deactivation : less than 1 subscribe per day. SHould be replaced by a getsitecontrol.
    <section class="mt-8 mb-5"> 
        <div class="bg-white rounded-lg shadow p-4 flex flex-col md:flex-row md:items-center md:justify-between">
             <div class="mb-4 md:mb-0 md:pr-4"> 
                <h2 class="text-lg font-bold mb-1"><?=$label_subscription_H2." ".$ebaySearchKeyword;?></h2> 
                <p class="text-sm text-gray-600"> <?=$label_subscription_explainer;?></p> 
            </div> 
                <form action="<?=$rootDomain.$base;?>subscribe.php" method="post" class="w-full md:w-auto flex flex-col md:flex-row md:items-center"> 
                    <input type="text" name="website" autocomplete="off" style="display:none">
                    <input type="hidden" name="alert_keyword" value="<?php if(isset($ebaySearchKeyword)) echo $ebaySearchKeyword;?>"> 
                    <input type="email" name="email" required placeholder="<?=$label_subscription_email;?>" class="w-full border border-gray-300 px-4 py-2 rounded-md mb-2 md:mb-0 md:mr-2 focus:outline-none focus:ring-2 focus:ring-blue-500" >
                    <button type="submit" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition duration-300" > <?=$label_subscription_button;?> </button> 
                </form> 
        </div>
    </section>
    */ ?>
    
    <section class="mt-8 mb-5" id="makemoney"> 
    <?php
    if (!$isLocal && !isset($noAds)) {
        echo $googleadsense_body;
    }
    ?>
    </section>

    <?php
    /* internal linking categories */
    if (!empty($relatedLevel1Categories)): ?>
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
    /* internal linking products */
    if (!empty($relatedKeywords)): ?>
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


    <?php require __DIR__ . '/inc/footer.php'; ?>

    <?php require __DIR__ . '/inc/jsonld.php'; ?>
</body>
</html>
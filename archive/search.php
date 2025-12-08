<?php
/*
Search page
*/

// don't display ads for this page
$noAds = true;

require __DIR__ . '/inc/config.php'; 
require __DIR__ . '/inc/functions.php'; 


$pageTitle = "Search ".$WebsiteName;
$additionnalMetaDesc = "";


$AffiliateSearchLink = tracking_link_builder($_POST['keyword'], $countryCode, null);
?>

<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<meta name="robots" value="noindex,nofollow">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
  
    <div class="py-8 sm:py-12">
        <h1 class="text-2xl sm:text-4xl font-bold tracking-tight mb-5"><?=$label_search_button." : ".$prepared_keyword;?></h1>
        
        <button class="h-full px-4 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 mb-5" onclick="window.open('<?=$AffiliateSearchLink;?>')">
            > Click here to see all results
        </button>
        <br>
        <span class="text-sm text-gray-600">#Sponsored by eBay search</span>
    </div>


</main>



    <?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>
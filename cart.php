<?php
/** cart.php
 * 
 */

// don't display ads for this page
$noAds = true;

require __DIR__ . '/inc/config.php'; 
require __DIR__ . '/inc/functions.php'; 


$pageTitle = $label_cart." - ". $WebsiteName;

?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>
<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

<!-- ZONE "CART EMPTY" -->
<section id="emptycart" class="">
    <div class="mb-5  justify-between">                        
        <div class="mx-auto max-w-2xl bg-white rounded-md px-4 py-2 mt-8">
            <h1 class="text-xl sm:text-2xl font-semibold mb-4"><?= htmlspecialchars($label_cart, ENT_QUOTES, 'UTF-8') ?></h1>            
            <h2 class="font-semibold"><?= htmlspecialchars($label_cart_empty_title, ENT_QUOTES, 'UTF-8') ?></h2>            
            <div class="introtext">
                <p><?=$label_cart_empty_text;?></p>
            </div>            
        </div>
    </div>
</section>


<!-- ZONE "LAST BROWSED PAGE" -->
<section id="lastbrowsed" class="">
    <div class="mb-5  justify-between">                        
        <div class="mx-auto max-w-2xl bg-white rounded-md px-4 py-2">
            <h2 class="font-semibold"><?= htmlspecialchars($label_cart_lastBrowsed_title, ENT_QUOTES, 'UTF-8') ?></h2>          
            <div class="introtext">
                <ul>
                <?php
                /* TODO : améliorer avec des photos du keyword */
                    foreach($_SESSION['visited_pages'] as $page){
                        $value = str_replace("-", " ", basename($page));
                        echo "<li class=''>&middot; <a href='$page'>$value</a></li>";
                    }
                ?>
                </ul>
            </div>

        </div>
    </div>
</section>


<!-- ZONE "SEARCH" -->
<section id="search" class="">
    <div class="mb-5  justify-between">                        
        <div class="mx-auto max-w-2xl bg-white rounded-md px-4 py-2">
            <h2 class="font-semibold"><?= htmlspecialchars($label_cart_search_title, ENT_QUOTES, 'UTF-8') ?></h2>                      
            <form action="<?=$rootDomain.$base;?>search.php" method="post">
                <input type="text" name="keyword"  placeholder="ex: ipad..." class="border border-gray-300 px-4 py-2 rounded-r-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button class="md:w-auto px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition duration-300">
                    <?=$label_search_button;?>
                </button>
            </form>
        </div>
    </div>
</section>

            
<!-- ZONE "ALERT ME" -->
<section id="alertme" class="">
    <div class="mb-5  justify-between">                        
        <div class="mx-auto max-w-2xl bg-white rounded-md px-4 py-2">
            <h2 class="font-semibold"><?= htmlspecialchars($label_contact, ENT_QUOTES, 'UTF-8') ?></h2>                      
            <form action="<?=$rootDomain.$base;?>subscribe.php" method="post" class="w-full md:w-auto flex flex-col md:flex-row md:items-center"> 
                <input type="text" name="website" autocomplete="off" style="display:none">
                <input type="hidden" name="alert_keyword" value=""> 
                <input type="email" name="email" required placeholder="<?=$label_subscription_email;?>" class="border border-gray-300 px-4 py-2 rounded-md mb-2 md:mb-0 md:mr-2 focus:outline-none focus:ring-2 focus:ring-blue-500" >
                <button type="submit" class="md:w-auto px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 transition duration-300" > <?=$label_subscription_button;?> </button> 
            </form> 

        </div>
    </div>
</section>
            
</main>


<?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>

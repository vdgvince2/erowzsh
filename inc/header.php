<?php 
if (!$isLocal && !isset($noAds)) echo $googleadsense_topBody;
inline_css_for_page();
?>
<!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="<?= $rootDomain.$base;?>" class="text-xl font-bold text-blue-600"><?=$WebsiteName;?></a>

                    <div class="relative flex-grow hidden md:block">
                        <form action="<?=$rootDomain.$base;?>search.php" method="post">
                        <input type="text" name="keyword"  placeholder="ex: ipad..." class="border border-gray-300 px-4 py-2 w-full rounded-r-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button class="absolute right-0 top-0 h-full px-4 bg-blue-600 text-white rounded-r-md hover:bg-blue-700">
                            <?=$label_search_button;?>
                        </button>
                        </form>
                    </div>
                </div>
                <div class="flex items-center space-x-4">                    
                    <a href="<?= $rootDomain.$base;?>s/myaccount" class="text-sm hover:text-blue-600"><?=$Header_Link_myaccount;?></a>
                    <a href="<?= $rootDomain.$base;?>s/cart" class="text-sm hover:text-blue-600"><?=$Header_Link_cart;?></a>
                    <a href="<?= $rootDomain.$base;?>s/contact" class="text-sm hover:text-blue-600"><?=$Header_Link_Help;?></a>

                </div>
            </div>
            <div class="md:hidden mt-3">
                <div class="relative">
                    <form action="<?=$rootDomain.$base;?>search.php" method="post">
                    <input type="text" name="keyword" placeholder="<?=$Header_Search;?>" class="border border-gray-300 px-4 py-2 w-full rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button class="absolute right-0 top-0 h-full px-4 bg-blue-600 text-white rounded-r-md hover:bg-blue-700">
                        <?=$label_search_button;?>
                    </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
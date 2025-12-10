<?php

/***
 * SIDEBAR
 * DISPLAY THE BASIC LINKS : USED, NEW, CHEAPEST, etc.
 * TODO : re-instate the real filters : color, size, etc. 
 * 
 * */ 


// We prepare the same content for mobile & desktop
$ret_toDisplay = "";

// PREPARE THE FILTERS FOR DESKTOP AND MOBILE
$filtersToDisplayDesktop = "";
$filtersToDisplayMobile = "";
$i = 0;
foreach($array_advices as $advice => $filterKey){
    // RULES FOR THE NEW/USED BUTTONS
    if($filterKey == "conditionNew" or $filterKey == "conditionUsed"){
            $condition = $filterKey;
            $filterKey = null;
    }
    
    $AffiliateSearchLink = tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition);
    $AffiliateSearchLink = str_replace("customid=".$countryCode."_", "customid=".$countryCode."_FILTERDESKTOP_", $AffiliateSearchLink);
    
    // obfuscation
    $AffiliateSearchLink = base64_encode($AffiliateSearchLink);    

    $filtersToDisplayDesktop .= "<li><a href='#' target=_blank data-url='$AffiliateSearchLink' class='clickable-product cursor-pointer rounded-xl border px-4 mb-2'>".ucfirst($advice)."</a></li>";
    $filtersToDisplayMobile .= "<li><a href='#' target=_blank data-url='$AffiliateSearchLink' class='clickable-product cursor-pointer rounded-xl border px-4 mb-2'>".ucfirst($advice)."</a></li>";
    // Specific mobile on 2 lines
    $i++;
    if($i=="3") $filtersToDisplayMobile .= "</ul><ul class='flex flex-wrap gap-x-2 gap-y-2 mt-3'>";
}

?>
<!-- DESKTOP FILTERS -->
<div class="w-full md:w-1/4 lg:w-1/5 pr-4 mb-6 sidebar">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="justify-between items-center mb-4">
            <h4 class="font-bold text-lg mb-4"><?=$label_filters;?></h4>
            <div class="justify-between items-center mb-4">
            <ul>
                <?=$filtersToDisplayDesktop;?>
            </ul>
            </div>
        </div>                 
    </div>
</div>

<!-- MOBILE STICKY FILTERS (visible < md) -->
<div id="mobile-filters" class="md:hidden sticky top-[env(safe-area-inset-top)] z-40 bg-white/95 backdrop-blur border-b">
  <div class="max-w-screen-xl mx-auto py-2">
    <h4 class="font-bold text-lg mb-4"><?=$label_filters;?></h4>
    <ul class="flex flex-wrap gap-x-2 gap-y-2">
        <?=$filtersToDisplayMobile;?>
    </ul>
  </div>
</div>

<?php
/* filters are not working for now because we don't have more ads to show 
<div class="w-full md:w-1/4 lg:w-1/5 pr-4 mb-6 sidebar">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-bold text-lg"><?=$label_filters;?></h2>
                        <button id="toggle-filters" class="md:hidden text-blue-600">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>                 
                    
                    <div id="filters-container">
                        <!-- Price Range -->
                        <div class="filter-section mb-6">
                            <div class="filter-header flex justify-between items-center cursor-pointer" >
                                <h4 class="font-medium"><?=$label_price;?></h3>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                            <div class="filter-content">
                                <div class="flex items-center space-x-2 mb-2 mt-3">
                                    <span class="text-gray-500"><?=$currency;?></span>
                                    <input type="text" placeholder="Min" class="border border-gray-300 px-2 py-1 w-20 rounded text-sm">
                                    <span class="text-gray-500">-</span>
                                    <input type="text" placeholder="Max" class="border border-gray-300 px-2 py-1 w-20 rounded text-sm">
                                </div>
                                <input type="range" min="0" max="200" value="50" class="price-range-slider my-2">
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span><?=$currency;?>0</span>
                                    <span><?=$currency;?>200+</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Condition -->
                        <div class="filter-section mb-6">
                            <div class="filter-header flex justify-between items-center cursor-pointer" >
                                <h4 class="font-medium"><?=$label_condition;?></h3>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                            <div class="filter-content">
                                <div class="space-y-2 mt-3">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox appearance-none w-4 h-4 border border-gray-300 rounded checked:bg-blue-600 relative">
                                        <span><?=$label_new;?></span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox appearance-none w-4 h-4 border border-gray-300 rounded checked:bg-blue-600 relative">
                                        <span><?=$label_used;?></span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox appearance-none w-4 h-4 border border-gray-300 rounded checked:bg-blue-600 relative">
                                        <span><?=$label_refurb;?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    
                        
                        <!-- Delivery Options -->
                        <div class="filter-section mb-6">
                            <div class="filter-header flex justify-between items-center cursor-pointer" >
                                <h4 class="font-medium"><?=$label_delivery;?></h3>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                            <div class="filter-content">
                                <div class="space-y-2 mt-3">
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox appearance-none w-4 h-4 border border-gray-300 rounded checked:bg-blue-600 relative">
                                        <span><?=$label_postage;?></span>
                                    </label>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" class="custom-checkbox appearance-none w-4 h-4 border border-gray-300 rounded checked:bg-blue-600 relative">
                                        <span><?=$label_collection;?></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button class="w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700"><?=$label_filters;?></button>
                    </div>
                </div>
            </div>

*/
?>
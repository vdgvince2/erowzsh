<?php

/***
 * SIDEBAR
 * DISPLAY THE BASIC LINKS : USED, NEW, CHEAPEST, etc.
 * 
 * */ 

// Condition init
$condition = null;
?>

<!-- DESKTOP FILTERS -->
<div class="w-full md:w-1/4 lg:w-1/5 pr-4 mb-6 sidebar">
    <div class="sticky top-4 bg-white rounded-2xl p-4 shadow-sm space-y-6 border border-gray-200 border-l-4 border-l-blue-500">
        <div class="justify-between items-center mb-4">           
            <div class="justify-between items-center mb-4">

            <?php foreach($array_advices as $adviceBlock => $advice_key){ ?>
                <div class="space-y-2 mb-4">
                    <h3 class="text-s font-semibold text-blue-500"><?=ucfirst($translate_advices_labels[$adviceBlock]);?></h3>
                    <div class="flex flex-wrap gap-2" data-group="condition">
                    <?php 
                    /* for the array with a lot of choice, ie : sort by. */
                    if(count($advice_key)>3){
                        echo '<select
                                id="sortSelect"
                                class="clickable-product w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800
                                        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                >';
                    } 
                    /* loop the options of the array */
                    foreach($advice_key as $advice => $filterKey){  
                        // Overwrite the keys for the "condition" filter only.                        
                        if($adviceBlock == "condition"){                              
                            if($filterKey == "conditionNew" or $filterKey == "conditionUsed"){
                                $condition = $filterKey;
                                $filterKey = null;
                            }
                        }
                        $AffiliateSearchLink = tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition);
                        $AffiliateSearchLink = str_replace("customid=".$countryCode."_", "customid=".$countryCode."_FILTERDESKTOP_", $AffiliateSearchLink);
                        $AffiliateSearchLink = base64_encode($AffiliateSearchLink); 

                        /* if many items, we use the dropdown. */
                        if(count($advice_key)>3){
                            echo '<option data-url="'.$AffiliateSearchLink.'">
                                        '.$advice.'
                                    </option>';
                        }else{
                        /* otherwise, we use standard buttons */
                            echo '
                            <button type="button"
                                    class="clickable-product cursor-pointer filter-pill px-2 py-2 rounded-md border text-sm hover:bg-gray-100"
                                    data-url="'.$AffiliateSearchLink.'">
                                '.$advice.'
                            </button>          ';
                        }
                    }

                    if(count($advice_key)>3){
                        echo '</select>';
                    }
                    ?>
                    </div>
                </div>
            <?php } ?>
               
            </div>
        </div>
  </div>
</div>

<!-- MOBILE STICKY FILTERS (visible < md) -->
<div id="mobile-filters" class="md:hidden sticky top-[env(safe-area-inset-top)] z-40 bg-white/95 backdrop-blur">
  <div class="flex flex-wrap items-center gap-1 mt-2 bg-gray-50 rounded-lg p-3 border-b-2 border-gray-300">

  <?php foreach($array_advices as $adviceBlock => $advice_key){ ?>
        <div class="space-y-2 mb-4">            
            <div class="" data-group="condition">
            <select
            id="sortSelect_<?=$adviceBlock;?>"
            class="clickable-product rounded-lg border border-gray-300 bg-white px-1 py-2 text-sm text-gray-800
               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
            <option><?=ucfirst($translate_advices_labels[$adviceBlock]);?></option>
            
            <?php
            /* loop the options of the array */
            foreach($advice_key as $advice => $filterKey){  
                // Overwrite the keys for the "condition" filter only.                        
                if($adviceBlock == "condition"){                              
                    if($filterKey == "conditionNew" or $filterKey == "conditionUsed"){
                        $condition = $filterKey;
                        $filterKey = null;
                    }
                }
                $AffiliateSearchLink = tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition);
                $AffiliateSearchLink = str_replace("customid=".$countryCode."_", "customid=".$countryCode."_FILTERMOBILE_", $AffiliateSearchLink);
                $AffiliateSearchLink = base64_encode($AffiliateSearchLink); 
                
                echo '<option data-url="'.$AffiliateSearchLink.'">'.$advice.'</option>';
                
            }                    
            
            
            ?>
            </select>
            </div>
        </div>
    <?php } ?>
  
  </div>
</div>
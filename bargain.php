<?php
/***************************  

    BARGAIN PAGE
    COPY OF BAYCRAZY

***************************/

/* LOCAL HARDCODED  
$countryCode = 'US';
$ebay_marketplace = 'EBAY_US';
$priceCurrencySchema = 'USD';
*/

// Buffer
ob_start();

// don't display ads for this page
$noAds = true;

require __DIR__ . '/inc/config.php'; 
require __DIR__ . '/inc/functions.php'; 
require __DIR__ . '/scripts/crawler/ebay_browse_crawler.php'; 
require __DIR__ . '/inc/functions-bargain.php'; 

$pageTitle = "Bargain BayCrazy";

// Marketplace 
$EBAY_MARKETPLACE_ID = $ebay_marketplace;
$EBAY_BROWSE_TOKEN = get_access_token();
$EBAY_BROWSE_ENDPOINT = 'https://api.ebay.com/buy/browse/v1/item_summary/search';



// -----------------------------------------------------------------------------
// Lecture des paramètres UI
// -----------------------------------------------------------------------------

// On utilise POST en priorité (AJAX), sinon GET (load initial)
$src = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

$mode = $src['mode'] ?? 'local'; // local | misspelled | lastminute
if (!in_array($mode, ['local', 'misspelled', 'lastminute'], true)) $mode = 'local';

$postcode   = trim($src['postcode'] ?? '');
$searchTerm = trim($src['q'] ?? '');

// Filtres avancés
$filtersInput = [
    'min_price'    => $src['min_price']    ?? '',
    'max_price'    => $src['max_price']    ?? '',
    'min_bids'     => $src['min_bids']     ?? '',
    'max_bids'     => $src['max_bids']     ?? '',
    'category_id'  => $src['category_id']  ?? '',
    'max_distance' => $src['max_distance'] ?? '',
    'delivery_min' => $src['delivery_min'] ?? '',
    'delivery_max' => $src['delivery_max'] ?? '',
    'pickup_only'  => !empty($src['pickup_only']) ? 1 : 0,
];

// Tri
$sortUi = $src['sort'] ?? 'best';
$sort   = null;
switch ($sortUi) {
    case 'price_asc':
        $sort = 'price';
        break;
    case 'price_desc':
        $sort = '-price';
        break;
    case 'ending_soon':
        $sort = 'endingSoonest';
        break;
    case 'newly_listed':
        $sort = 'newlyListed';
        break;
    case 'distance':
        $sort = 'distance';
        break;
    default:
        $sort = null; // Best Match
}

// -----------------------------------------------------------------------------
// Appel Browse + construction de $products
// -----------------------------------------------------------------------------

$products = [];
$errorMsg = null;


$queryParams = [
    'q'          => $searchTerm,
    'limit'      => 50,
    'offset'     => 0,
    'postcode'   => $postcode, // uniquement pour ton header ctx, pas pour l’API
];

// Category dans query
if ($filtersInput['category_id'] !== '') $queryParams['category_ids'] = $filtersInput['category_id'];

$filterString = build_ebay_filter_string($filtersInput, $mode, $postcode, $countryCode, $priceCurrencySchema);

$autoCorrect = null;
if ($mode === 'misspelled') $autoCorrect = "KEYWORD";

$browseData = ebay_browse_search($queryParams, $filterString, $autoCorrect, $sort);

if ($browseData === null and !empty($src['category_id'])) {
    $errorMsg = 'Unable to contact eBay Browse API or invalid response.';
} else {
    if(!empty($browseData))   $products = map_browse_to_products($browseData, null);
}


// -----------------------------------------------------------------------------
// Mode AJAX : on renvoie juste le HTML des résultats en JSON
// -----------------------------------------------------------------------------
$isAjax = ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ajax']));

if ($isAjax) {
    // On ne veut AUCUN HTML avant le JSON
    ob_clean(); // supprime tout ce qui a été envoyé avant (echo, print_r, warnings html, etc.)

    // Génération du HTML des résultats
    ob_start();
    render_bargain_results($postcode, $searchTerm, $errorMsg, $products, $currency, $rootDomain, $base, $label_viewdetails, $mode);
    $html = ob_get_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html' => $html,
    ]);
    exit;
}


// -----------------------------------------------------------------------------
// Affichage HTML (layout proche bargaintime.co). Les cartes produits
// seront rendues par template.php qui lit $products.
// -----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<?php require __DIR__ . '/inc/head-scripts.php'; ?>
<body>
<?php require __DIR__ . '/inc/header.php'; ?>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Header / Mode selector -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold mb-2">
                <?php 
                    if ($mode === 'local'): echo $label_bargain_local; 
                    elseif ($mode === 'misspelled'): echo $label_bargain_misspelled; 
                    else: $label_bargain_lastminute;
                    endif;
                ?>
            </h1>
            <p class="text-gray-600"><?=$label_bargain_tagline;?></p>
        </div>

        <div class="mt-4 lg:mt-0 flex gap-3 mb-4">
            <a href="?mode=local" class="flex flex-col items-center px-4 py-2 rounded-xl border <?php echo $mode === 'local' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'; ?>">
                <span class="text-xl">📍</span>
                <span class="text-sm font-semibold"><?=$label_bargain_local;?></span>
            </a>
            <a href="?mode=misspelled" class="flex flex-col items-center px-4 py-2 rounded-xl border <?php echo $mode === 'misspelled' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'; ?>">
                <span class="text-xl">A?</span>
                <span class="text-sm font-semibold"><?=$label_bargain_misspelled;?></span>
            </a>
            <a href="?mode=lastminute" class="flex flex-col items-center px-4 py-2 rounded-xl border <?php echo $mode === 'lastminute' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'; ?>">
                <span class="text-xl">⏱</span>
                <span class="text-sm font-semibold"><?=$label_bargain_lastminute;?></span>
            </a>
        </div>
    </div>

    <!-- Main content: Form + Results -->
    <div class="grid grid-cols-1 lg-grid-cols-2 gap-8">

        <!-- Sidebar: filters -->
        <aside class="bg-white rounded-xl shadow p-6 lg:col-span-1">
            <form id="bargain-form" method="post" action="bargain.php" class="space-y-4">
                <input type="hidden" name="mode" value="<?php echo htmlspecialchars($mode, ENT_QUOTES); ?>">

                <div class="px-4 py-2">
                    <label class="block text-m font-medium mt-2"><?=$label_bargain_postcode;?>
                    <input type="text" 
                        name="postcode" 
                        value="<?php echo htmlspecialchars($postcode, ENT_QUOTES); ?>" 
                        class="w-full border rounded px-3 py-2"
                        <?php echo ($mode === 'local') ? 'required' : ''; ?>
                    /></label>
                </div>

                <div class="px-4 py-2">
                    <label class="block text-m font-medium mb-1"><?=$label_bargain_search;?>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES); ?>" class="w-full border rounded px-3 py-2">
                    </label>
                </div>


                <!-- Bloc refine responsive -->
                <div class="mt-4 border-t pt-4">

                    <!-- Titre + bouton toggle (mobile seulement) -->
                    <button
                        type="button"
                        id="refine-toggle"
                        class="w-full flex items-center justify-between lg:hidden px-4 py-3 text-sm font-semibold text-gray-700"
                    >
                        <span><?=$label_bargain_refine;?></span>
                        <svg
                            id="refine-toggle-icon"
                            class="w-4 h-4 transform transition-transform duration-200"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Titre visible seulement sur desktop -->
                    <p class="hidden lg:block text-xl font-semibold text-gray-700 mb-2 px-4 py-2">
                        <?=$label_bargain_refine;?>
                    </p>

                <!-- Contenu des filtres : caché sur mobile par défaut, visible sur desktop -->
                <div id="refine-panel" class="hidden lg:block space-y-2">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="px-4 py-2">
                            <label class="block text-xs font-medium mb-1"><?=$label_bargain_minprice;?>
                                <input type="number" step="0.01" name="min_price" value="<?php echo htmlspecialchars($filtersInput['min_price'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                            </label>
                        </div>
                        <div class="px-4 py-2">
                            <label class="block text-xs font-medium mb-1"><?=$label_bargain_maxprice;?>
                                <input type="number" step="0.01" name="max_price" value="<?php echo htmlspecialchars($filtersInput['max_price'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="px-4 py-2">
                            <label class="block text-xs font-medium mb-1"><?=$label_bargain_minbids;?>
                                <input type="number" name="min_bids" value="<?php echo htmlspecialchars($filtersInput['min_bids'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                            </label>
                        </div>
                        <div class="px-4 py-2">
                            <label class="block text-xs font-medium mb-1"><?=$label_bargain_maxbids;?>
                                <input type="number" name="max_bids" value="<?php echo htmlspecialchars($filtersInput['max_bids'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                            </label>
                        </div>
                    </div>

                    <div class="px-4 py-2">
                        <label class="block text-xs font-medium mb-1"><?=$label_bargain_category;?>
                            <?php                            
                            renderEbayCategoryDropdown($ebay_marketplace, 'category_id', $filtersInput['category_id']);
                            ?>
                        </label>
                    </div>

                    <div class="px-4 py-2">
                        <label class="block text-xs font-medium mb-1"><?=$label_bargain_maxdist;?> (<?=$label_distance_value;?>)
                            <input type="number" name="max_distance" value="<?php echo htmlspecialchars($filtersInput['max_distance'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="px-4 py-2">
                            <label class="block text-xs font-medium mb-1"><?=$label_bargain_deliverymax;?>
                                <input type="number" step="0.01" name="delivery_max" value="<?php echo htmlspecialchars($filtersInput['delivery_max'], ENT_QUOTES); ?>" class="w-full border rounded px-2 py-1 text-sm">
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center px-4 py-2">
                        <label for="pickup_only" class="text-sm"><input type="checkbox" id="pickup_only" name="pickup_only" value="1" <?php echo $filtersInput['pickup_only'] ? 'checked' : ''; ?> class="mr-2">
                        <?=$label_bargain_pickuponly;?></label>
                    </div>

                    <div class="px-4 py-2">
                        <label class="block text-xs font-medium mb-1"><?=$label_bargain_sortby;?></label>
                        <select name="sort" class="w-full border rounded px-2 py-1 text-sm">
                            <option value="best" <?php echo $sortUi === 'best' ? 'selected' : ''; ?>><?=$label_bargain_bestmatch;?></option>
                            <option value="price_asc" <?php echo $sortUi === 'price_asc' ? 'selected' : ''; ?>><?=$label_bargain_pricelow;?></option>
                            <option value="price_desc" <?php echo $sortUi === 'price_desc' ? 'selected' : ''; ?>><?=$label_bargain_pricehigh;?></option>
                            <option value="ending_soon" <?php echo $sortUi === 'ending_soon' ? 'selected' : ''; ?>><?=$label_bargain_endingsoon;?></option>
                            <option value="newly_listed" <?php echo $sortUi === 'newly_listed' ? 'selected' : ''; ?>><?=$label_bargain_newly;?></option>
                            <option value="distance" <?php echo $sortUi === 'distance' ? 'selected' : ''; ?>><?=$label_bargain_nearest;?></option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full mt-4 bg-blue-600 text-white font-semibold py-2 rounded-lg">
                    <?=$label_bargain_search;?>
                </button>
                </form>
        </aside>

        <!-- Results -->
        <main class="lg:col-span-2">
            <div id="loading" class="hidden mb-4 text-blue-600 font-semibold">
                <?=$label_bargain_loading;?>
            </div>            
            <div id="results">
                <?php render_bargain_results($postcode, $searchTerm, $errorMsg, $products, $currency, $rootDomain, $base, $label_viewdetails, $mode); ?>
            </div>
        </main>
    </div>
</div>

    <?php require __DIR__ . '/inc/footer.php'; ?>


<script src="assets/bargain.js"></script>

</body>
</html>

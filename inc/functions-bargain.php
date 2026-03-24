<?php

/* SPECIFIC FUNCTIONS FOR THE BARGAIN PAGE */

/**
 * Affiche un dropdown des catégories eBay pour un pays donné.
 *
 * @param string      $countryCode  Code pays (ex: 'GB', 'US', 'FR')
 * @param string      $name         Name de l'input (ex: 'category_id')
 * @param string|null $selectedId   ID de catégorie pré-sélectionnée (optionnel)
 * @param string      $assetsDir    Chemin vers le dossier assets (par défaut: __DIR__ . '/assets')
 */
function renderEbayCategoryDropdown(
    string $ebay_marketplace,
    string $name = 'category_id',
    ?string $selectedId = null
){

    global $label_bargain_category_notavailable, $label_bargain_category_errorloading, $label_bargain_category_invalid, $label_bargain_category_allcateg;
 
    $assetsDir = __DIR__ . '/../assets/JSON';

    $marketplaceSuffix = strtoupper($ebay_marketplace);
    $filePath = rtrim($assetsDir, '/\\') . "/category-{$marketplaceSuffix}.min.json";

    if (!file_exists($filePath)) {
        echo '<select name="' . htmlspecialchars($name, ENT_QUOTES) . '" class="w-full border rounded px-2 py-1 text-sm" disabled>'
           . '<option value="">'.$label_bargain_category_notavailable.'</option>'
           . '</select>';
        return;
    }

    $json = file_get_contents($filePath);

    if ($json === false) {
        echo '<select name="' . htmlspecialchars($name, ENT_QUOTES) . '" class="w-full border rounded px-2 py-1 text-sm" disabled>'
           . '<option value="">'.$label_bargain_category_errorloading.'</option>'
           . '</select>';
        return;
    }

    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['children']) || !is_array($data['children'])) {
        echo '<select name="' . htmlspecialchars($name, ENT_QUOTES) . '" class="w-full border rounded px-2 py-1 text-sm" disabled>'
           . '<option value="">'.$label_bargain_category_invalid.'</option>'
           . '</select>';
        return;
    }

    echo '<select name="' . htmlspecialchars($name, ENT_QUOTES) . '" class="w-full border rounded px-2 py-1 text-sm">' . PHP_EOL;
    echo '  <option value="">'.$label_bargain_category_allcateg.'</option>' . PHP_EOL;

    foreach ($data['children'] as $cat) {
        if (!isset($cat['id'], $cat['name'])) {
            continue;
        }
        $id   = (string)$cat['id'];
        $nameLabel = (string)$cat['name'];

        $selectedAttr = ($selectedId !== null && $selectedId === $id) ? ' selected' : '';

        echo '  <option value="' . htmlspecialchars($id, ENT_QUOTES) . '"' . $selectedAttr . '>'
           . htmlspecialchars($nameLabel, ENT_QUOTES)
           . '</option>' . PHP_EOL;
    }

    echo '</select>' . PHP_EOL;
}



/**
 * Appel simple de l’API Browse (reprend l’esprit d’un crawler classique).
 */
function ebay_browse_search(array $params, string $filter = null, ?string $autoCorrect = null, ?string $sort = null): ?array
{
    global $EBAY_BROWSE_TOKEN, $EBAY_MARKETPLACE_ID, $EBAY_BROWSE_ENDPOINT, $countryCode, $priceCurrencySchema;

    $queryParts = [];

    // get more data from the response
    $params['fieldgroups'] = 'EXTENDED,MATCHING_ITEMS';  
    
    // 👉 auto-correct : param officiel = auto_correct
    if ($autoCorrect) {
        $params['auto_correct'] = "KEYWORD"; 
    }    

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            continue;
        }
        $queryParts[] = rawurlencode($key) . '=' . rawurlencode($value);
    }

    if ($filter) {
        $queryParts[] = 'filter=' . rawurlencode($filter);
    }

    if ($sort) {
        $queryParts[] = 'sort=' . rawurlencode($sort);
    }

    $url = $EBAY_BROWSE_ENDPOINT . '?' . implode('&', $queryParts);
        

    $headers = [
        'Authorization: Bearer ' . $EBAY_BROWSE_TOKEN,
        'X-EBAY-C-MARKETPLACE-ID: ' . $EBAY_MARKETPLACE_ID,
    ];

    // Si tu gères l’affiliation/enduserctx, ajoute ton header existant ici
    /*
    if (!empty($params['postcode'])) {
        $ctx = 'contextualLocation=country='.$countryCode.',zip=' . $params['postcode'];
        $headers[] = 'X-EBAY-C-ENDUSERCTX: ' . $ctx;
    }*/

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    $raw = curl_exec($ch);    
    
    if ($raw === false) {
        //log_local_write(" CURL ERROR ($curlErrno): $curlError");
        curl_close($ch);
        return null;
    }

    // DEBUG the EBAY CALL
    /*log_local_write(sprintf(
    "[%s] URL: %s\nHTTP: %s\ncurl_errno: %s\ncurl_error: %s\nraw_length: %s\nraw_preview: %s\n\n",
    date('Y-m-d H:i:s'),
    $url,
    $httpCode,
    $curlErrno,
    $curlError ?: 'OK',
    $rawLength,
    $raw !== false ? substr($raw, 0, 500) : 'FALSE'));*/

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }

    // 🔍 LOG DEBUG
    log_local_write(" URL: " . $url . " | filter: " . $filter, 'ebay_browse_debug.log');
    if (!empty($data['warnings'])) log_local_write(print_r($data['warnings'], true), 'ebay_browse_debug.log');

    return $data;
}






/**
 * Construit la chaîne "filter=..." à partir des filtres UI.
 */
function build_ebay_filter_string(array $input, string $mode, string $postcode, string $countryCode = 'US', string $priceCurrencySchema = 'USD'): string
{

    global $label_distance_value;

    $parts = [];

    // ---- PRICE ----
    $minPrice = trim($input['min_price'] ?? '');
    $maxPrice = trim($input['max_price'] ?? '');

    $hasPriceFilter = false;
    if ($minPrice !== '' && $maxPrice !== '') {
        $parts[] = "price:[{$minPrice}..{$maxPrice}]";
        $hasPriceFilter = true;
    } elseif ($minPrice !== '' && $maxPrice === '') {
        $parts[] = "price:[{$minPrice}..999999]";
        $hasPriceFilter = true;
    } elseif ($minPrice === '' && $maxPrice !== '') {
        $parts[] = "price:[0..{$maxPrice}]";
        $hasPriceFilter = true;
    }

    if ($hasPriceFilter) {
        // 👇 obligatoire si tu filtres par prix
        $parts[] = "priceCurrency:{$priceCurrencySchema}";
    }

    // ---- BID COUNT ----
    $minBids = trim($input['min_bids'] ?? '');
    $maxBids = trim($input['max_bids'] ?? '');

    if ($minBids !== '' || $maxBids !== '') {
        // Normaliser en int
        $min = ($minBids !== '') ? (int)$minBids : 0;
        $max = ($maxBids !== '') ? (int)$maxBids : 999999;

        $parts[] = "bidCount:[{$min}..{$max}]";

        // 🔥 Facultatif mais logique : si on filtre sur les bids, on force les enchères
        // pour éviter d'avoir du FIXED_PRICE sans bid.
        $parts[] = "buyingOptions:{AUCTION}";
    }

    // Catégorie eBay (ID numérique)
    $categoryId = trim($input['category_id'] ?? '');
    if ($categoryId !== '') {
        // la partie categoryIds est dans les params de requête, mais on peut aussi filtrer ici si besoin
        // on se contente de passer category_ids dans la query, donc rien à ajouter en filter pour ça.
    }

    // Distance / pickup
    // ---- LOCAL PICKUP (mode local deals) ----
    $usePickupFilters = false;
    $maxDistance = trim($input['max_distance'] ?? '');

    if ($mode === 'local') {
        $usePickupFilters = true;
        $radius = $maxDistance !== '' ? (int)$maxDistance : 3000;

        $parts[] = "deliveryOptions:{SELLER_ARRANGED_LOCAL_PICKUP}";
        $parts[] = "pickupCountry:{$countryCode}";
        if ($postcode !== '') {
            $parts[] = "pickupPostalCode:{$postcode}";
        }
        $parts[] = "pickupRadius:{$radius}";
        $parts[] = "pickupRadiusUnit:{$label_distance_value}";
    } else {
        // Autres modes: option Pickup only
        if ($input['pickup_only']==1 && $postcode !== '') {
            $radius = $maxDistance !== '' ? (int)$maxDistance : 3000;
            $parts[] = "deliveryOptions:{SELLER_ARRANGED_LOCAL_PICKUP}";
            $parts[] = "pickupCountry:{$countryCode}";
            $parts[] = "pickupPostalCode:{$postcode}";
            $parts[] = "pickupRadius:{$radius}";
            $parts[] = "pickupRadiusUnit:{$label_distance_value}";
        } elseif ($postcode !== '') {
            // Simplement filtrer sur la livraison vers ce code postal
            $parts[] = "deliveryPostalCode:{$postcode}";
        }
    }

    // Coût de livraison max (Browse ne supporte que maxDeliveryCost, pas un min)
    //$deliveryMin = trim($input['delivery_min'] ?? '');
    $deliveryMax = trim($input['delivery_max'] ?? '');
    if ($deliveryMax !== '') {
        $parts[] = "maxDeliveryCost:{$deliveryMax}";
        $parts[] = "maxDeliveryCostCurrency:{$priceCurrencySchema}";
    }
    // deliveryMin n’est pas supporté → ignoré volontairement

    // Last-minute = enchères qui se terminent bientôt
    if ($mode === 'lastminute') {
        // auctions uniquement
        $parts[] = "buyingOptions:{AUCTION}";
        // enchères qui se terminent dans l’heure
        $now   = new DateTime('now', new DateTimeZone('UTC'));
        $end   = (clone $now)->modify('+3 hour');
        $endIso = $end->format('Y-m-d\TH:i:s\Z');
        $parts[] = "itemEndDate:[..{$endIso}]";
    }

    // 👉 IMPORTANT : pas de deliveryCountry si on utilise les filtres pickup
    if (!$usePickupFilters && $countryCode !== '') {
        $parts[] = "deliveryCountry:{$countryCode}";
    }

    return implode(',', $parts);
}







/**
 * Mappe les résultats Browse vers ton tableau $products.
 */
function map_browse_to_products(array $data, ?int $keywordId = null): array
{
    $products = [];

    if (empty($data['itemSummaries']) || !is_array($data['itemSummaries'])) {
        return $products;
    }

    foreach ($data['itemSummaries'] as $item) {
        $title       = $item['title'] ?? '';
        //$shortDesc   = $item['shortDescription'] ?? '';
        $imageUrl    = $item['image']['imageUrl'] ?? ($item['thumbnailImages'][0]['imageUrl'] ?? '');        
        $itemUrl     = $item['itemWebUrl'] ?? $item['itemAffiliateWebUrl'] ?? '';

        // buying options
        $buyingOptions = $item['buyingOptions'] ?? [];
        $isAuction     = in_array('AUCTION', $buyingOptions, true);

        // condition
        $condition   = $item['condition'] ?? '';

        // prix "de base" (fixe)
        $priceValue = $item['price']['value'] ?? 0;
        // prix enchère courant (si dispo)
        $currentBid = $item['currentBidPrice']['value'] ?? null;        

        // prix à afficher :
        $displayPrice = $priceValue;
        if ($isAuction && $currentBid !== null && $currentBid > 0) {
            $displayPrice = $currentBid;
        }

        // vendeur
        $sellerName      = $item['seller']['username'] ?? '';
        $sellerScore     = $item['seller']['feedbackScore'] ?? null;
        $sellerPercent   = isset($item['seller']['feedbackPercentage'])
            ? (float)$item['seller']['feedbackPercentage']
            : null;

        // distance from pickup
        $distanceValue = $item['distanceFromPickupLocation']['value'] ?? null;
        $distanceUnit  = $item['distanceFromPickupLocation']['unitOfMeasure'] ?? null;

        // itemLocation string
        $locParts = [];
        if (!empty($item['itemLocation']['city'])) {
            $locParts[] = $item['itemLocation']['city'];
        }
        if (!empty($item['itemLocation']['postalCode'])) {
            $locParts[] = $item['itemLocation']['postalCode'];
        }
        if (!empty($item['itemLocation']['country'])) {
            $locParts[] = $item['itemLocation']['country'];
        }
        $itemLocationStr = implode(', ', $locParts);

        /*
        // description_itemspecs : petit résumé simple
        $descSpecs = $shortDesc;
        if ($descSpecs === '') {
            $pieces = [];
            if ($condition) {
                $pieces[] = $condition;
            }
            if ($itemLocationStr) {
                $pieces[] = $itemLocationStr;
            }
            $descSpecs = implode(' • ', $pieces);
        }*/

        // ⏱ fin de l’enchère
        $endTime = $item['itemEndDate'] ?? null; // ISO style "2025-12-05T10:23:00.000Z"
        // Version lisible en Europe/Brussels
        $endTimeLocal = null;
        if ($endTime) {
            try {
                $dt = new DateTime($endTime, new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('Europe/Brussels'));
                $endTimeLocal = $dt->format('d/m/Y H:i'); // ex: 03/12/2025 11:57
            } catch (Exception $e) {
                // ignore si problème de parsing
            }
        }

        $products[] = [
            'id'                     => null,
            'keyword_id'             => $keywordId,
            'title_original'         => $title,
            //'description_itemspecs'  => $descSpecs,
            'photo'                  => $imageUrl,
            'price'                  => $displayPrice,   
            'price_original'         => $priceValue,       
            'current_bid'            => $currentBid,        
            'is_auction'             => $isAuction ? 1 : 0,   
            'end_time'               => $endTime,        
            'url'                    => $itemUrl,

            // 👇 nouveaux champs
            'condition'              => $condition,
            'seller_username'        => $sellerName,
            'seller_feedback_score'  => $sellerScore,
            'seller_feedback_pct'    => $sellerPercent,
            'distance_value'         => $distanceValue,
            'distance_unit'          => $distanceUnit,
            'item_location'          => $itemLocationStr,
        ];
    }

    return $products;
}


/**
 * Calcule un score "bonne affaire" de 0 à 100.
 * Basé sur : fiabilité vendeur, prix, dynamique enchère, proximité.
 */
function computeBargainScore(array $prod): int
{
    $score = 0;

    // Fiabilité vendeur (0–30 pts)
    $pct = (float)($prod['seller_feedback_pct'] ?? 0);
    if      ($pct >= 99.5) $score += 30;
    elseif  ($pct >= 99)   $score += 20;
    elseif  ($pct >= 97)   $score += 10;

    // Volume vendeur (0–10 pts)
    $fbScore = (int)($prod['seller_feedback_score'] ?? 0);
    if      ($fbScore >= 10000) $score += 10;
    elseif  ($fbScore >= 1000)  $score += 7;
    elseif  ($fbScore >= 100)   $score += 4;

    // Niveau de prix (0–25 pts) — absolu, sans données historiques
    $price = (float)($prod['price'] ?? 0);
    if ($price > 0) {
        if      ($price <= 5)   $score += 25;
        elseif  ($price <= 15)  $score += 20;
        elseif  ($price <= 30)  $score += 15;
        elseif  ($price <= 75)  $score += 10;
        elseif  ($price <= 150) $score += 5;
    }

    // Dynamique enchère (0–25 pts)
    if (!empty($prod['is_auction'])) {
        $score += 10;
        if (!empty($prod['current_bid'])) $score += 5;
        if (!empty($prod['end_time'])) {
            $endTs     = strtotime((string)$prod['end_time']);
            $hoursLeft = $endTs ? ($endTs - time()) / 3600 : PHP_INT_MAX;
            if      ($hoursLeft <= 1)  $score += 15;
            elseif  ($hoursLeft <= 6)  $score += 10;
            elseif  ($hoursLeft <= 24) $score += 5;
        }
    }

    // Proximité (0–10 pts)
    $dist = isset($prod['distance_value']) ? (float)$prod['distance_value'] : null;
    if ($dist !== null && $dist < 20)     $score += 10;
    elseif ($dist !== null && $dist < 50) $score += 6;
    elseif (!empty($prod['item_location'])) $score += 3;

    return min(100, max(0, $score));
}


/* DISPLAY THE BARGAIN FROM EBAY */
function render_bargain_results($postcode, $searchTerm, $errorMsg, $products, $currency, $rootDomain, $base, $label_viewdetails, $mode) {

global $label_bargain_distance, $label_bargain_seller, $label_bargain_endsin, $label_bargain_calculating, $label_bargain_endson, $label_bargain_standard, $label_deals_below_market, $label_deals_top_deal, $countryCode;

    ?>
    <?php if ($errorMsg): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errorMsg, ENT_QUOTES); ?>
        </div>
    <?php elseif (empty($products) && $searchTerm !== ''): ?>
        <div class="text-center py-16 text-gray-400">
            <p class="text-4xl mb-3">🔍</p>
            <p class="text-lg font-medium">No results for &ldquo;<?= htmlspecialchars($searchTerm, ENT_QUOTES) ?>&rdquo;</p>
            <p class="text-sm mt-1">Try a different keyword or adjust the filters.</p>
        </div>
    <?php elseif (empty($products)): ?>
        <div class="text-center py-16 text-gray-400">
            <p class="text-lg">Enter a keyword to find deals.</p>
        </div>
    <?php else: ?>
        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($products as $prod) :
                $isTopDeal   = !empty($prod['is_top_deal']);
                $belowPct    = isset($prod['below_market_pct']) ? (int)$prod['below_market_pct'] : 0;
                $condNorm    = htmlspecialchars($prod['condition'] ?? '', ENT_QUOTES);
                $cardRing    = $isTopDeal ? ' ring-2 ring-emerald-400' : '';
            ?>
                <div class="bg-white rounded-lg shadow overflow-hidden product-card transition duration-300<?= $cardRing ?>"
                     data-cond="<?= $condNorm ?>">
                    <?php if ($isTopDeal && !empty($label_deals_top_deal)): ?>
                    <div class="bg-emerald-500 text-white text-xs font-semibold text-center py-1 px-3 tracking-wide">
                        ⭐ <?= htmlspecialchars($label_deals_top_deal, ENT_QUOTES) ?>
                    </div>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars(tracking_link_builder($searchTerm, $countryCode, $prod['url']), ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="flex">
                        <?php if (!empty($prod['photo'])): ?>
                            <div class="flex-shrink-0 w-24 h-24 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img src="<?= htmlspecialchars($prod['photo'], ENT_QUOTES); ?>"
                                alt="<?= htmlspecialchars($prod['title_original'], ENT_QUOTES); ?>"
                                class="max-w-full max-h-full object-contain">
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 flex flex-col gap-2">
                            <?php
                            $score = computeBargainScore($prod);
                            global $label_deals_score_tooltip;
                            $scoreTooltip = str_replace('{score}', $score, $label_deals_score_tooltip ?? '');
                            ?>
                            <div class="flex items-center justify-between flex-wrap gap-1">
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-800 cursor-help"
                                      title="<?= htmlspecialchars($scoreTooltip, ENT_QUOTES); ?>">
                                    🔥 Score <?= $score ?>
                                </span>
                                <?php if ($belowPct >= 5 && !empty($label_deals_below_market)): ?>
                                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-full">
                                        <?= htmlspecialchars(str_replace('{pct}', $belowPct, $label_deals_below_market), ENT_QUOTES) ?>
                                    </span>
                                <?php elseif (!empty($prod['is_auction'])): ?>
                                    <span class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Auction</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($prod['is_auction']) && $belowPct >= 5): ?>
                                <span class="text-xs font-semibold text-orange-600 uppercase tracking-wide">Auction</span>
                            <?php endif; ?>
                            <h2 class="text-sm font-semibold line-clamp-2 h-42">
                                <?= htmlspecialchars($prod['title_original'], ENT_QUOTES); ?>
                            </h2>

                            <?php if (!empty($prod['condition'])): ?>
                                <span class="inline-block text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 condition">
                                    <?= htmlspecialchars($prod['condition'], ENT_QUOTES); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($prod['item_location'])): ?>
                                <p class="text-xs text-gray-600 location">
                                    <?= htmlspecialchars($prod['item_location'], ENT_QUOTES); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($prod['distance_value'])): ?>
                                <p class="text-xs text-gray-600 distance">
                                    <?=$label_bargain_distance;?>: 
                                    <?= htmlspecialchars($prod['distance_value'], ENT_QUOTES); ?>
                                    <?= htmlspecialchars($prod['distance_unit'] ?? '', ENT_QUOTES); ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($prod['seller_username'])): ?>
                                <p class="text-xs text-gray-600 sellerDetail">
                                    <?=$label_bargain_seller;?>: <?= htmlspecialchars($prod['seller_username'], ENT_QUOTES); ?>
                                    <?php if ($prod['seller_feedback_pct'] !== null): ?>
                                        – <?= htmlspecialchars(number_format($prod['seller_feedback_pct'], 1), ENT_QUOTES); ?>%
                                    <?php endif; ?>
                                    <?php if (!empty($prod['seller_feedback_score'])): ?>
                                        (<?= (int)$prod['seller_feedback_score']; ?>)
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($prod['is_auction']) && !empty($prod['end_time'])): ?>
                                <div class="mt-1 text-xs text-orange-600 font-semibold leading-tight">
                                    <div>
                                        <?=$label_bargain_endsin;?>:
                                        <span
                                            class="auction-countdown"
                                            data-endtime="<?= htmlspecialchars($prod['end_time'], ENT_QUOTES); ?>"
                                            data-endtime-local="<?= htmlspecialchars($prod['end_time_local'] ?? '', ENT_QUOTES); ?>"
                                        >
                                            <!-- texte provisoire avant que JS ne calcule -->
                                            <?=$label_bargain_calculating;?>
                                        </span>
                                    </div>

                                    <?php if (!empty($prod['end_time_local'])): ?>
                                        <div class="text-[10px] text-orange-500 font-normal mt-0.5">
                                            (<?=$label_bargain_endson;?> <?= htmlspecialchars($prod['end_time_local'], ENT_QUOTES); ?>)
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                                                    

                            <?php if (!empty($prod['description_itemspecs'])): ?>
                                <p class="text-xs text-gray-500 line-clamp-2 itemSpecs">
                                    <?= htmlspecialchars($prod['description_itemspecs'], ENT_QUOTES); ?>
                                </p>
                            <?php endif; ?>

                            <div class="items-center justify-between price">
                                <span class="text-lg font-bold"><?= htmlspecialchars($currency, ENT_QUOTES); ?> <?= htmlspecialchars(number_format($prod['price'], 2), ENT_QUOTES); ?></span>
        
                            </div>

                            <div class="mt-2 calltoaction">
                                <button class="bg-blue-500 text-white text-sm px-4 py-1.5 rounded-md mt-3"><?= htmlspecialchars($label_viewdetails, ENT_QUOTES); ?></button>
                            </div>
                        </div>
                    </a>
                </div>
                
            <?php endforeach; ?>
        </div>
    <?php endif;
}


/**
 * Renders a "Best deals over €X" widget for a given category page.
 * Shows the first N keywords from the matching catalog entry.
 *
 * @param string $categorySlug  The current category URL slug (from $matched['url'])
 * @param string $rootDomain
 * @param string $base
 * @param string $currency      e.g. "€"
 */
function render_deals_widget(string $categorySlug, string $rootDomain, string $base, string $currency): void
{
    global $countryCode;

    $catalogFile = __DIR__ . '/../assets/JSON/deals_catalog.json';
    if (!file_exists($catalogFile)) return;

    $catalog = json_decode(file_get_contents($catalogFile), true) ?? [];

    // Find the first catalog category whose trigger_slugs (for this country) include this page's slug
    $match = null;
    $catKey = '';
    foreach ($catalog as $key => $cat) {
        $slugsForCountry = $cat['countries'][$countryCode]['trigger_slugs'] ?? [];
        if (!empty($slugsForCountry) && in_array($categorySlug, $slugsForCountry, true)) {
            $match  = $cat;
            $catKey = $key;
            break;
        }
    }
    if (!$match) return;

    global $label_deals_widget_title, $label_deals_widget_desc;

    $minPrice = (int)($match['min_price'] ?? 0);
    $keywords = array_slice($match['countries'][$countryCode]['keywords'] ?? [], 0, 6);

    $widgetVars  = ['currency' => $currency, 'min_price' => (string)$minPrice, 'label' => $match['label']];
    $widgetTitle = str_replace(array_map(fn($k) => '{' . $k . '}', array_keys($widgetVars)), array_values($widgetVars), $label_deals_widget_title ?? '');
    $widgetDesc  = str_replace(array_map(fn($k) => '{' . $k . '}', array_keys($widgetVars)), array_values($widgetVars), $label_deals_widget_desc ?? '');
    ?>
    <div class="my-6 bg-yellow-50 border border-yellow-200 rounded-xl p-5">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-xl">🔥</span>
            <h3 class="font-bold text-gray-800 text-base">
                <?= htmlspecialchars($widgetTitle, ENT_QUOTES); ?> <?= htmlspecialchars($currency, ENT_QUOTES); ?><?= $minPrice; ?>
                — <?= htmlspecialchars($match['label'], ENT_QUOTES); ?>
            </h3>
        </div>
        <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($widgetDesc, ENT_QUOTES); ?></p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($keywords as $kw): ?>
            <a href="<?= $rootDomain . $base; ?>deals/<?= rawurlencode($catKey); ?>/<?= rawurlencode($kw['slug']); ?>"
               class="text-sm px-3 py-1 rounded-full border border-yellow-400 bg-white text-gray-700 hover:bg-yellow-100 hover:border-yellow-600 transition">
                <?= htmlspecialchars($kw['label'], ENT_QUOTES); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}


/**
 * Renders a lightweight "Best deals" strip using already-loaded DB products.
 * Zero extra API calls — reads from $products already fetched by product-category.php.
 *
 * Shows top 3 cheapest products + a CTA link to the eBay bargain search.
 *
 * @param array  $products            Already loaded from DB (ads table)
 * @param string $keyword             Display keyword / search term
 * @param string $currency            e.g. "€"
 * @param string $rootDomain
 * @param string $base
 * @param string $rootDomainForAssets For image proxy URL
 * @param string $countryCode         e.g. "IE"
 * @param string $labelTitle          Template string with {keyword} placeholder
 * @param string $labelCta            "See all listings on eBay"
 */
function render_best_deals_strip(
    array  $products,
    string $keyword,
    string $currency,
    string $rootDomain,
    string $base,
    string $rootDomainForAssets,
    string $countryCode,
    string $labelTitle,
    string $labelCta,
    string $labelIntro  = '',
    string $labelLowest = 'Lowest price'
): void {
    $valid = array_values(array_filter($products, fn($p) => !empty($p['photo']) && (float)($p['price'] ?? 0) > 0));
    if (empty($valid)) return;

    $top   = array_slice($valid, 0, 3);
    $count = count($valid);

    $kwSafe     = htmlspecialchars($keyword, ENT_QUOTES);
    $title      = str_replace('{keyword}', $kwSafe, $labelTitle);
    $intro      = str_replace(['{count}', '{keyword}'], [(string)$count, $kwSafe], $labelIntro);
    $searchLink = base64_encode(tracking_link_builder($keyword, $countryCode, null, null, null));
    ?>
    <section class="mt-8 border border-gray-200 rounded-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-800">🔥 <?= $title ?></h2>
            <a href="<?= $rootDomain . $base ?>s/bargain?q=<?= rawurlencode($keyword) ?>"
               class="text-xs text-blue-600 hover:underline font-medium">
                <?= htmlspecialchars($labelCta, ENT_QUOTES) ?> →
            </a>
        </div>

        <?php if ($intro): ?>
        <!-- Intro — texte à trou -->
        <p class="px-4 pt-3 pb-1 text-xs text-gray-500 italic"><?= $intro ?></p>
        <?php endif; ?>

        <!-- Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 bg-white">
        <?php foreach ($top as $i => $prod):
            $isLowest  = $i === 0;
            $directUrl = !empty($prod['url']) ? base64_encode(tracking_link_builder($keyword, $countryCode, $prod['url'], null, null)) : $searchLink;
            $specs     = trim($prod['description_itemspecs'] ?? '');
        ?>
            <div class="flex flex-col clickable-product cursor-pointer hover:bg-gray-50 transition"
                 data-url="<?= $directUrl ?>">
                <!-- Image -->
                <div class="relative w-full bg-gray-50 flex items-center justify-center overflow-hidden"
                     style="min-height:180px;">
                    <img src="<?= $rootDomainForAssets ?>image.php?url=<?= base64_encode($prod['photo']) ?>"
                         alt="<?= htmlspecialchars($prod['title_original'] ?? '', ENT_QUOTES) ?>"
                         class="w-full h-44 object-contain p-2" loading="lazy" width="200" height="176">
                    <?php if ($isLowest): ?>
                    <span class="absolute top-2 left-2 bg-green-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                        <?= htmlspecialchars($labelLowest, ENT_QUOTES) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="p-3 flex flex-col gap-1 flex-1">
                    <p class="text-xs text-gray-700 line-clamp-2 leading-snug font-medium">
                        <?= htmlspecialchars($prod['title_original'] ?? '', ENT_QUOTES) ?>
                    </p>
                    <?php if ($specs): ?>
                    <p class="text-[11px] text-gray-400 line-clamp-1"><?= htmlspecialchars($specs, ENT_QUOTES) ?></p>
                    <?php endif; ?>
                    <p class="text-base font-bold text-gray-900 mt-auto pt-1">
                        <?= $currency ?><?= number_format((float)$prod['price'], 0) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </section>
    <?php
}
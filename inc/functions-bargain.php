<?php

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
        log_local_write(" CURL ERROR ($curlErrno): $curlError");
        curl_close($ch);
        return null;
    }

    /* DEBUG the EBAY CALL
    log_local_write(sprintf(
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
    log_local_write(" URL: " . $url . " | filter: " . $filter);
    if (!empty($data['warnings'])) log_local_write(print_r($data['warnings'], true));

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


/* DISPLAY THE BARGAIN FROM EBAY */
function render_bargain_results($postcode, $searchTerm, $errorMsg, $products, $currency, $rootDomain, $base, $label_viewdetails, $mode) {

global $label_bargain_distance, $label_bargain_seller, $label_bargain_endsin, $label_bargain_calculating, $label_bargain_endson;

    ?>
    <?php if ($errorMsg): ?>
        <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($errorMsg, ENT_QUOTES); ?>
        </div>
    <?php else: ?>
        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($products as $prod) : ?>
            <!-- Product Card 1 -->                            
                <div class="bg-white rounded-lg shadow overflow-hidden product-card transition duration-300">
                    <a href="<?= htmlspecialchars(tracking_link_builder($searchTerm, $countryCode, $prod['url']), ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer" class="flex p-4 gap-4">
                        <?php if (!empty($prod['photo'])): ?>
                            <div class="flex-shrink-0 w-24 h-24 bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img src="<?= htmlspecialchars($prod['photo'], ENT_QUOTES); ?>"
                                alt="<?= htmlspecialchars($prod['title_original'], ENT_QUOTES); ?>"
                                class="max-w-full max-h-full object-contain">
                            </div>
                        <?php endif; ?>

                        <div class="flex-1 flex flex-col gap-2">
                            <h2 class="text-sm font-semibold line-clamp-2 h-42">
                                <?= htmlspecialchars($prod['title_original'], ENT_QUOTES); ?>
                            </h2>

                            <?php if (!empty($prod['condition'])): ?>
                                <span class="inline-block text-xs px-2 py-1 rounded-full bg-green-100 text-green-800 condition">
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

                            <div class="mt-2 flex items-center justify-between calltoaction">                                
                                <button class="w-full bg-bluecustom text-white py-2 rounded-md mt-3"><?= htmlspecialchars($label_viewdetails, ENT_QUOTES); ?>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
                
            <?php endforeach; ?>   
        </div>
    <?php endif; 

}
<?php
/*
 * 
 * EBAY ACCOUNT : vdgvincemdo
 * App ID (Client ID) : 

* eBay Browse API – Minimal keyword crawler (PHP)
 * - Auth: Client Credentials (application token)
 * - Caches access token on disk to avoid re-auth
 * - Search endpoint: /buy/browse/v1/item_summary/search
 *
 * Usage (CLI):
 *   php ebay_browse_crawler.php "montre seiko" --market=EBAY_FR --limit=20 --offset=0
 *
 * Usage (HTTP):
 *   /ebay_browse_crawler.php?q=montre%20seiko&market=EBAY_FR&limit=20&offset=0
 */

// ================== CONFIG ==================
/* PRODUCTION */
const EBAY_API_BASE      = 'https://api.ebay.com';
const TOKEN_CACHE_FILE   = __DIR__ . '/.ebay_oauth_token.json';
// Choose the scopes for Browse API (application token)
const EBAY_OAUTH_SCOPES  = [
    'https://api.ebay.com/oauth/api_scope'
];
// ============================================

function cli_or_http_param(string $name, $default = null) {

    // GET/POST first
    if (isset($_GET[$name]))  return $_GET[$name];
    if (isset($_POST[$name])) return $_POST[$name];

    // CLI flags: --name=value
    global $argv;
    if (!empty($argv)) {
        foreach ($argv as $arg) {
            if (preg_match('/^--'.preg_quote($name,'/').'=(.*)$/', $arg, $m)) {
                return $m[1];
            }
        }
        // first non-flag is q
        if ($name === 'q') {
            foreach ($argv as $arg) {
                if (substr($arg, 0, 2) !== '--' && basename($arg) !== basename(__FILE__)) {
                    return $arg;
                }
            }
        }
    }
    return $default;
}

function get_access_token(): string {
    // Try cache
    if (file_exists(TOKEN_CACHE_FILE)) {
        $data = json_decode(file_get_contents(TOKEN_CACHE_FILE), true);
        if (isset($data['access_token'], $data['expires_at']) && time() < $data['expires_at'] - 60) {
            return $data['access_token'];
        }
    }

    $url  = EBAY_API_BASE . '/identity/v1/oauth2/token';
    $auth = base64_encode(EBAY_CLIENT_ID . ':' . EBAY_CLIENT_SECRET);

    $postFields = http_build_query([
        'grant_type' => 'client_credentials',
        'scope'      => implode(' ', EBAY_OAUTH_SCOPES),
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $http >= 400) {
        http_response_code(500);
        die(json_encode([
            'error' => 'oauth_failed',
            'http'  => $http,
            'curl'  => $err,
            'body'  => $resp,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $json = json_decode($resp, true);
    if (!isset($json['access_token'], $json['expires_in'])) {
        http_response_code(500);
        die(json_encode(['error' => 'oauth_invalid_response', 'body' => $json], JSON_UNESCAPED_UNICODE));
    }

    $json['expires_at'] = time() + (int)$json['expires_in'];
    @file_put_contents(TOKEN_CACHE_FILE, json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $json['access_token'];
}

function browse_search(string $q, string $market = 'EBAY_FR', int $limit = 50, int $offset = 0, array $opts = []): array {

    global $isLocal;
    $token = get_access_token();

    $params = [
        'q'      => $q,
        'limit'  => max(1, min($limit, 200)), // eBay caps at 200
        'offset' => max(0, $offset),
        'fieldgroups' => 'EXTENDED,MATCHING_ITEMS',
    ];

    // Optional filters
    // Examples:
    //   $opts['category_ids'] = '31387,9355';
    //   $opts['sort']         = '-price'; // price, -price, price + shipping, etc.
    //   $opts['filter']       = 'price:[10..100],conditions:{NEW|USED},buyingOptions:{FIXED_PRICE}'
    foreach (['category_ids','filter','sort'] as $k) {
        if (!empty($opts[$k])) $params[$k] = $opts[$k];
    }



    $url = EBAY_API_BASE . '/buy/browse/v1/item_summary/search?' . http_build_query($params);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-EBAY-C-MARKETPLACE-ID: ' . $market, // e.g., EBAY_FR, EBAY_GB, EBAY_US, EBAY_DE, EBAY_IT
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false || $http >= 400) {
        return [
            'error' => 'browse_failed',
            'http'  => $http,
            'curl'  => $err,
            'url'   => $url,
            'body'  => $resp,
        ];
    }

    $json = json_decode($resp, true);
    if (!is_array($json)) {
        return ['error' => 'invalid_json', 'body_raw' => $resp];
    }

    // write in the log file for debug purpose
    if ($isLocal) {
        $jsonString = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        log_local_write($jsonString);

    }

    return $json;
}




/* Fonction that works for NotFound keywords (=> add keyword) and for Update of the Ads 
    Example: updateAds($pdo, $ebay_marketplace, $_EBAY_MAX_ADS, $_GET['keyword_id'], $countryCode, "update")
*/
function updateAds($pdo, $ebay_marketplace, $maxAds, $countryCode, $nfId = null, $keywordId = 0, $actionType = "update", $subDomain = false){

    // MANAGE THE SUBDOMAIN TABLES
    if($subDomain === true){
        $TABLE_keywords = "subdomain_keywords";
        $TABLE_ads = "subdomain_ads";
    }else{
        $TABLE_keywords = "keywords";
        $TABLE_ads = "ads";        
    }

    // Retrieve the keyword name
    $stmt = $pdo->prepare("SELECT keyword_name FROM $TABLE_keywords WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $keywordId]);
    $keywordArray = $stmt->fetch(PDO::FETCH_ASSOC);

    if(empty($keywordArray)) { echo "No 404 keyword found"; exit(); }
    echo "KwId: $keywordId | (Sub: $subDomain) -- Country: $countryCode | ". $keywordArray['keyword_name'].PHP_EOL;

    // Get the JSON.
    $result = browse_search($keywordArray['keyword_name'], $ebay_marketplace, $maxAds, 0, ['fieldgroups' => 'PRODUCT']);
    $rawJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    // Retrieve eBay data
    $data = json_decode($rawJson, true);
    if (!is_array($data)) {
        http_response_code(400);
        exit("Invalid JSON.\n");
    }

    // Parse ebay data
    $items = $data['itemSummaries'] ?? [];
 

    if (count($items) <= 5) {
        // Rien à faire si pas assez de produits
        echo "Skip: <= 5 products in JSON.\n";

        // remove the keyword if no ads only for the notFound
        if($actionType == "notfound"){
            $del = $pdo->prepare("DELETE FROM $TABLE_keywords WHERE id = :kid");
            $del->execute([':kid' => $keywordId]);

            // remove from notfound : TODO : mark it as not to process anymore
            $upd = $pdo->prepare("UPDATE notfound SET noads = true WHERE id = :nfId");
            $upd->execute([':nfId' => $nfId]);

            // quitter la fonction
            return;
        }
    }

    // Check for NotFound Only -- Détermination de la catégorie via MATCH AGAINST à partir du 1er produit 
    if($actionType == "notfound"){
        // --- ---
        /**
         * On prend la meilleure étiquette de catégorie du premier item :
         * - priorité au 1er élément de "categories"[].categoryName
         * - fallback sur "leafCategoryIds" (peu utile pour un MATCH textuel)
         */
        $firstItem = $items[0];
        $sourceCategoryName = null;
        if (!empty($firstItem['categories']) && is_array($firstItem['categories'])) {
            // on prend la première catégorie fournie
            $sourceCategoryName = $firstItem['categories'][0]['categoryName'] ?? null;
        }
        if (!$sourceCategoryName && !empty($firstItem['title'])) {
            // fallback minimal : utiliser le titre (peu idéal, mais mieux que rien)
            $sourceCategoryName = $firstItem['title'];
        }

        $matchedCategoryId = null;
        $matchedCategoryName = null;

        if ($sourceCategoryName) {
            $sqlCat = "SELECT id, name 
                    FROM categories 
                    WHERE MATCH(name) AGAINST(:q IN NATURAL LANGUAGE MODE) 
                    ORDER BY id ASC 
                    LIMIT 1";
            $stmtCat = $pdo->prepare($sqlCat);

            echo "Categorie from ebay: $sourceCategoryName".PHP_EOL;
            
            $stmtCat->execute([':q' => $sourceCategoryName]);
            if ($row = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
                $matchedCategoryId = (int)$row['id'];
                $matchedCategoryName = $row['name'];
            }
            echo "Categorie match : $matchedCategoryName ($matchedCategoryId)".PHP_EOL;
            // force "eBay" category as default.
            if($matchedCategoryId == ""){
                $matchedCategoryId = 1;
                $matchedCategoryName = "eBay";
            } 

            // Mettre à jour la table keywords.main_category si on a trouvé une catégorie
            if ($matchedCategoryId !== null) {
                $upd = $pdo->prepare("UPDATE $TABLE_keywords SET main_category = :cid WHERE id = :kid");
                $upd->execute([
                    ':cid' => $matchedCategoryId,
                    ':kid' => $keywordId
                ]);
            }        
        }
    }

    // --- Transaction globale pour insérer les annonces ---
    // On insère d'offices car on a déjà quitté avant si pas assez d'annonces
    $pdo->beginTransaction();

    try {

        /* prépar des categories */
        if($actionType == "update"){
            $sql_categ = "SELECT category_name_path, category_level1, category_level2, category_level3
                            FROM $TABLE_ads
                            WHERE keyword_id = :id
                            LIMIT 1";

            $stmt = $pdo->prepare($sql_categ);
            $stmt->execute(['id' => $keywordId]);
            $dataCateg = $stmt->fetch(PDO::FETCH_ASSOC);

            $category_name_path = $dataCateg['category_name_path'] ?? 'eBay';
            $category_level1     = $dataCateg['category_level1'] ?? $category_name_path;
            $category_level2     = $dataCateg['category_level2'] ?? null;
            $category_level3     = $dataCateg['category_level3'] ?? null;            

        }elseif($actionType == "notfound"){
            $category_name_path  = $matchedCategoryName;
            $category_level1     = $category_name_path;
            $category_level2     = null;
            $category_level3     = null;  
        }

        // Supprimer les annonces existantes pour ce keyword
        
        $del = $pdo->prepare("DELETE FROM $TABLE_ads WHERE keyword_id = :kid");
        $del->execute([':kid' => $keywordId]);        

        // Préparer l'INSERT des nouvelles annonces
        $ins = $pdo->prepare("
            INSERT INTO $TABLE_ads
                (keyword_id, title_original, description_itemspecs, photo, price, url, category_name_path, category_level1, category_level2, category_level3, insert_date)
            VALUES
                (:keyword_id, :title_original, :description_itemspecs, :photo, :price, :url, :category_name_path, :category_level1, :category_level2, :category_level3, now())
        ");

        // Count the ads to avoid the max
        $adsCount = 0;

        foreach ($items as $it) {
            
            // title is mandatory + we cut to 30 to avoid duplicate content.
            if($it['title'] != null){
                $title = truncate_no_cut($it['title'], $limit = 30);
            }else{
                break;
            }

            // Extraire des ngrams depuis la description courte
            $description_itemspecs = "";
            if(isset($it['shortDescription']) && $it['shortDescription'] != null){
                $description_itemspecs = extract_top_ngrams($it['shortDescription'], 1, 3, 3);
                $description_itemspecs = substr($description_itemspecs, 0, 511); // max size for sql.
            }

            // Photo (image principale puis fallback sur thumbnail)
            $photo = $it['image']['imageUrl'] ?? null;
            if (!$photo && !empty($it['thumbnailImages'][0]['imageUrl'])) {
                $photo = $it['thumbnailImages'][0]['imageUrl'];
            }

            // Prix (value -> decimal)
            $price = null;
            if (isset($it['price']['value'])) {
                $price = (float)$it['price']['value'];
            }

            // URL
            $url = $it['itemWebUrl'] ?? ($it['itemHref'] ?? null);


            // Insertion of max xx ads
            if($adsCount <= $maxAds){
                $ins->execute([
                    ':keyword_id'               => $keywordId,
                    ':title_original'           => $title,
                    ':description_itemspecs'    => $description_itemspecs,
                    ':photo'                    => $photo,
                    ':price'                    => $price !== null ? number_format($price, 2, '.', '') : null,
                    ':url'                      => $url,
                    ':category_name_path'       => $category_name_path, 
                    ':category_level1'          => $category_level1,
                    ':category_level2'          => $category_level2,
                    ':category_level3'          => $category_level3,
                ]);
            }

            $adsCount++;
        }

        $pdo->commit();
        $importValid = true;
        echo "Import OK for keyword_id={$keywordId}. Inserted " . count($items) . " ads.\n";

        // update the keyword last_update datetime
        $upd = $pdo->prepare("UPDATE $TABLE_keywords SET last_update = now() WHERE id = :kid");
        $upd->execute([':kid' => $keywordId]);
        
    } catch (Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }

    /* todo : prévoir un return */

}
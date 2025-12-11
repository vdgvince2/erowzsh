<?php

/*
    Model logic for the template page of products & categories
*/

// Rewrite of the title's products
include("titleGenerator.php");
include("recordpagevisited.php");

/*******************
 *  
  CATEGORY REQUEST 

********************/
if(isset($_GET['categ']) && $_GET['categ'] != null){

    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // ex /SH
    $uriPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

    // retire le dir si présent (ex /SH)
    if ($scriptDir !== '' && $scriptDir !== '/' && strpos($uriPath, $scriptDir) === 0) {
        $uriPath = substr($uriPath, strlen($scriptDir));
    }

    $uriPath = trim($uriPath, '/'); // ex: s/sporting-goods/camping-hiking/lamps

    // doit commencer par s/
    if (strpos($uriPath, 's/') !== 0) { http_response_code(400);  exit('Bad request: expected path starting with /s/');  }

    $rest = substr($uriPath, 2); // après "s/"
    $segments = array_values(array_filter(explode('/', $rest), fn($x) => $x !== ''));

    // On limite à 3 segments
    $segments = array_slice($segments, 0, 3);

    // 2) Candidats (priorité profondeur max -> min)
    $candidates = [];
    if (isset($segments[2])) $candidates[] = ['slug' => $segments[2], 'depth' => 3];
    if (isset($segments[1])) $candidates[] = ['slug' => $segments[1], 'depth' => 2];
    if (isset($segments[0])) $candidates[] = ['slug' => $segments[0], 'depth' => 1];

    if (!$candidates) { http_response_code(404);  exit('Catégorie manquante.'); }

    // 3) Trouver la catégorie existante (categories.url)
    
    $findCat = $pdo->prepare("SELECT id, name, url, level, parentid, slug_path FROM categories WHERE url = :slug LIMIT 1");

    $matched = null; // ['id'=>..,'name'=>..,'url'=>..,'level'=>..,'depth'=>3|2|1]
    $catIDforKeyword = 0;
    foreach ($candidates as $cand) {
        $findCat->execute([':slug' => $cand['slug']]);
        $row = $findCat->fetch();
        if ($row) {
            $matched = $row + ['depth' => $cand['depth']];
            $currentCat = $row;            
            $catIDforKeyword = $row['id'];
            break; // on prend le plus profond qui matche
        }
    }

    if (!$matched) { $errorInfo = $label_nocatfound; include('fallback.php'); exit; }

    // 4) Titre de page
    $pageTitle = prettyNameFromSlug($matched['slug_path']).$_TAIL. " | $WebsiteName";
    $ebaySearchKeyword = $matched['name'];
    $additionnalMetaDesc = $label_product_metadesc_generic;

    // 5) Charger les annonces selon profondeur retenue
    $field = match ((int)$matched['depth']) {
        3 => 'category_level3',
        2 => 'category_level2',
        default => 'category_level1',
    };

    // if the category is eBay (hardcoded) then we should force the ads via the keywords
    if(strtolower($matched['name']) == "ebay"){
        $sqlAds = "SELECT a.* FROM ads a
                    INNER JOIN keywords k on k.id = a.keyword_id
                    INNER JOIN categories c on c.id = k.main_category
                   WHERE lower(c.name) = :catname
                   LIMIT ".$_EBAY_MAX_ADS;    
    }else{
    // Standard query for categories
        $sqlAds = "SELECT * FROM ads WHERE {$field} = :catname LIMIT ".$_EBAY_MAX_ADS;
    }
    $stmtAds = $pdo->prepare($sqlAds);
    $stmtAds->execute([':catname' => $matched['name']]);

    $products = $stmtAds->fetchAll(); 
    

    /****
     *   CATEGORY >> Prepare internal linking 
     * 
    */

        // A. Enfants directs (pour level 1 ou 2)
        $stChildren = $pdo->prepare("
            SELECT id, name, url, level, slug_path
            FROM categories
            WHERE parentid = :pid
            ORDER BY name ASC
            LIMIT 10
        ");

        // B. Tous les level 2 sous un même level 1 (pour le cas où on est en level 3)
        $stLevel2OfSameParent = $pdo->prepare("
            SELECT id, name, url, level, slug_path
            FROM categories
            WHERE parentid = :level1_id AND level = 2
            ORDER BY name ASC
            LIMIT 10
        ");

        // C. Récupérer le parent d’un nœud (utile pour remonter de L3 -> L2 -> L1)
        $stGetParent = $pdo->prepare("
            SELECT id, name, url, level, parentid, slug_path
            FROM categories
            WHERE id = :id
            LIMIT 1
        ");

        $relatedCategories = [];          // tableau final de catégories à lier
        $sectionTitle = '';   // titre d’affichage

        switch ((int)$currentCat['level']) {
            case 1:
                // Enfants directs du level 1                
                $stChildren->execute([':pid' => (int)$currentCat['id']]);
                $relatedCategories = $stChildren->fetchAll();
                $sectionTitle = $label_explore_categories . $currentCat['name'];             
                break;                
            case 2:
                // Enfants directs
                $stChildren->execute([':pid' => (int)$currentCat['id']]);
                $relatedCategories = $stChildren->fetchAll();
                $sectionTitle = $label_explore_categories . $currentCat['name'];
                break;

            case 3:
                // On est en L3 -> prendre les "level 2 du même parent (level 1)"
                // 1) Récupérer le parent L2
                $stGetParent->execute([':id' => (int)$currentCat['parentid']]);
                $parentL2 = $stGetParent->fetch();

                if ($parentL2) {
                    // 2) Récupérer le parent L1 (parent du L2)
                    $stGetParent->execute([':id' => (int)$parentL2['parentid']]);
                    $parentL1 = $stGetParent->fetch();

                    if ($parentL1) {
                        // 3) Lister tous les level 2 sous ce level 1
                        $stLevel2OfSameParent->execute([':level1_id' => (int)$parentL1['id']]);
                        $relatedCategories = $stLevel2OfSameParent->fetchAll();

                        // Optionnel: exclure le L2 actuel si tu ne veux pas l’afficher
                        $relatedCategories = array_values(array_filter($relatedCategories, fn($c) => (int)$c['id'] !== (int)$parentL2['id']));

                        $sectionTitle = $label_related_categories . $parentL1['name'];
                    }
                }
                break;
        }


        // Prepare the categories of the level 1 if level 1. 
        $stLevel1 = $pdo->prepare("
            SELECT id, name, url, level, slug_path
            FROM categories
            WHERE level = 1
            AND id > :cid            
            LIMIT 10
        ");

        $stLevel1->execute([':cid' => (int)$currentCat['id']]);
        $relatedLevel1Categories = $stLevel1->fetchAll();
        $sectionLevel1Title = $label_other_categories;

        // Load the keywords of the deep category level
        // Prepare the internal linking.
        $relatedKeywords = [];
        $sqlKw = " SELECT id, keyword_name, keywordURL, 'maindomain' as source FROM keywords where main_category = :catid LIMIT 40";
        $stmt2 = $pdo->prepare($sqlKw);
        $stmt2->execute([':catid' => $catIDforKeyword]);
        $relatedKeywords = $stmt2->fetchAll();

        /* Get the content for the category */
        $ContentArray = get_content($pdo, $currentCat['id'], 'category');
       
    
}else{

    /***************
    
    PRODUCT REQUEST 
    
    **************/

    /* MANAGE THE KEYWORDS FOR SUBDOMAIN AND STANDARD DOMAIN */    
    if ($subDomain !== false) {
       $SQL_keywords = "SELECT id, keyword_name, null as main_category, null as last_visited 
                         FROM subdomain_keywords 
                         WHERE subdomain = :addr 
                         LIMIT 1";
        $URI = $subDomain;
    }else{        
        $SQL_keywords = "SELECT id, keyword_name, main_category, last_visited 
                         FROM keywords 
                         WHERE keywordURL = :addr 
                         LIMIT 1";
    }  
    $stmt = $pdo->prepare($SQL_keywords);                          
    $stmt->execute([':addr' => remove_stopwords($URI, $stopwords, '')]);
    $rowKeyword = $stmt->fetch();

    // 404 if no results
    if (!$rowKeyword){ $errorInfo = $label_nokwfound; include('fallback.php'); exit; }

    // META tag for SEO
    $pageTitle = $rowKeyword['keyword_name'] ?? 'Produits';
    $pageTitle = $pageTitle.$_TAIL. " | $WebsiteName";
    $additionnalMetaDesc = $label_product_metadesc_generic;
    $ebaySearchKeyword = $rowKeyword['keyword_name'];
    
    // Get all the ADS for the keyword
    if ($subDomain !== false) {
        $SQL_ads = "SELECT * FROM subdomain_ads 
                    WHERE keyword_id = :id 
                        AND photo is not null
                    LIMIT ".$_EBAY_MAX_ADS;
    }else{
        $SQL_ads = "SELECT * FROM ads 
                    WHERE keyword_id = :id 
                        AND photo is not null
                    LIMIT ".$_EBAY_MAX_ADS;
    }
    
    $stmt = $pdo->prepare($SQL_ads);
    $stmt->bindValue(':id', (int)$rowKeyword['id'], PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC); 
    
    
    // INTERNAL LINKING
    $relatedKeywords = [];
    if ($subDomain !== false) {
        // subdomain > get all keywords from the same sub-domain
        $SQL_related = "SELECT id, keyword_name, subdomain as keywordURL, 'subdomain' as source FROM subdomain_keywords where id > :kwid LIMIT 40";
        $stmt2 = $pdo->prepare($SQL_related);
        $stmt2->execute([':kwid' => $rowKeyword['id']]);
    }else{
        // main domain > get all keywords from the same category
        $SQL_related = "SELECT id, keyword_name, keywordURL, 'maindomain' as source FROM keywords where main_category = :catid and id > :kwid LIMIT 40";
        $stmt2 = $pdo->prepare($SQL_related);
        $stmt2->execute([':catid' => $rowKeyword['main_category'], ':kwid' => $rowKeyword['id']]);
    }
    $relatedKeywords = $stmt2->fetchAll();

    // BUILD THE BREADCRUMB
    $breadcrumbLink = "";
    if($rowKeyword['main_category'] != null){
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :catid LIMIT 1");
        $stmt->execute([':catid' => $rowKeyword['main_category']]);
        $rowCategory = $stmt->fetch();
        $breadcrumbLink = "<a href='".$rootDomain.$base."s".$rowCategory['slug_path']."'>".$rowCategory['name']."</a>";
    }


    // DEBUG if no products found
    if (!$products) {   http_response_code(404);   echo "Aucune donnée produits : " . htmlspecialchars($URI, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');   exit; }


    // Visited script page
    // Dans la page (server-side), vous connaissez $keywordId (int) et éventuellement $keyword (string)
    if($rowKeyword['last_visited'] != null ) $lastVisit = true; else $lastVisit = false;
    $keywordId = (int)$rowKeyword['id'];
    $payload = base64_encode(json_encode([
    'kid' => $keywordId,
    'ts'  => time()
    ], JSON_UNESCAPED_SLASHES));
    $sig = hash_hmac('sha256', $payload, VISITED_SECRET);    


    /* Get the content for the keyword */
    $ContentArray = get_content($pdo, $keywordId, 'product');
}

/* SUB DOMAIN INTERNAL LINK PREPARATION */
$subDomainInternalLinks = findSubdomainKeywordsByKeyword($ebaySearchKeyword, $pdo, 5);


/* GOOGLE SHOPPING */
$googleadsense_body = '<div id="afscontainer1"></div>
    <div id="relatedsearches1"></div>
    <script type="text/javascript" charset="utf-8">

            var pageOptions = {
                "pubId": "partner-pub-0809996796910370",
                "query": "'.$ebaySearchKeyword.'",
                "styleId": "4384181929",
                "adsafe": "low",
                "resultsPageBaseUrl": "'.$rootDomain.'",
                "resultsPageQueryParam": "query"
            };

            var adblock1 = {
            "container": "afscontainer1"
            };

            var rsblock1 = {
            "container": "relatedsearches1",
            "relatedSearches": 3
            };

            _googCsa(\'ads\', pageOptions, adblock1, rsblock1);

            </script>';

?>
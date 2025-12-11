<?php

/*

    - 100K API call par jour pour 5 sites = 20K par site. 5K de notfound et 15K de refresh.
    CAP à 500/session car on fera 1 session par heure dans crontab

    - pageAccessor : priorité aux mots-clés visités et les plus anciens updatés ou jamais updatés.

    Usage avec country code
    Production : /opt/plesk/php/8.3/bin/php pageAccessor.php IE

    */



require '../../inc/config.php';
require '../../inc/functions.php'; 
require 'ebay_browse_crawler.php';


if($isLocal) $MAX_keyword = 10; else $MAX_keyword = 500;

echo "Crawling of $MAX_keyword starting".PHP_EOL;

// --- Get the keywords
// noads = 1 : means that we can't find ad for that keyword, we should not crawl it anymore.
$sqlFetch = "SELECT *
                FROM subdomain_keywords
                WHERE last_update IS NULL
                OR last_update > NOW() - INTERVAL 3 DAY
                ORDER BY 
                    (last_update IS NOT NULL) DESC
             LIMIT ".$MAX_keyword;
$rows = $pdo->query($sqlFetch)->fetchAll(PDO::FETCH_ASSOC);
$countKeywords = 0;

if (!$rows) {
    exit("Aucune entrée à traiter dans Keywords.\n");
}

echo "*** Start crawling : ".date('Y-m-d H:i:s')." | MAX KW : ".$MAX_keyword. " | SubDomain -- Country : ".$countryCode.PHP_EOL;

// Boucle sur les mots-clés à traiter
foreach ($rows as $r) {
    
    try {
        // Insérer les nouvelles annonces et supprimer les anciennes.
        updateAds($pdo, $ebay_marketplace, $_EBAY_MAX_ADS, $countryCode, null, $r['id'], "update", true);
        $countKeywords++;

    } catch (Throwable $e) {
        //error_log("Erreur  " . $e->getMessage());
        echo "ERREUR:  " . $e->getMessage() . "\n";        
        continue;
    }
}

echo "*** End of crawl for $countryCode (keywords: $countKeywords) | ".date('Y-m-d H:i:s').PHP_EOL;
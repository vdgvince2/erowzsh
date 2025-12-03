<?php

/**
 * Get the keywords from the 404 table and fetch the eBay data. 
 * Production : /opt/plesk/php/8.3/bin/php notfound.php IE
 */

require '../../inc/config.php';
require '../../inc/functions.php'; // doit contenir clean_url($str)
require 'ebay_browse_crawler.php';

if($isLocal) $MAX_keyword = 1; else $MAX_keyword = 500;

// --- Préparations SQL --- CAP to 600 per country => 700x6 countries = 4200 query per day
// noads = 1 : means that we can't find ad for that keyword, we should not crawl it anymore.
$sqlFetch = "SELECT id, keywordname FROM notfound 
             WHERE noads = 0
             ORDER BY id ASC 
             LIMIT ".$MAX_keyword;
$rows = $pdo->query($sqlFetch)->fetchAll(PDO::FETCH_ASSOC);

$countKeywords = 0;

if (!$rows) {
    exit("Aucune entrée à traiter dans notfound.\n");
}

$stmtSelectExisting = $pdo->prepare("SELECT id FROM keywords WHERE keyword_name = :name LIMIT 1");
$stmtInsertKeyword  = $pdo->prepare("
    INSERT INTO keywords (keyword_name, keywordURL, homepage, main_category, last_visited)
    VALUES (:name, :url, 0, 0, NULL)
");
$stmtDeleteNotfound = $pdo->prepare("DELETE FROM notfound WHERE id = :id");

// --- Boucle principale ---
foreach ($rows as $r) {
    $nfId  = (int)$r['id'];
    $kname = trim(clean_url($r['keywordname'], " "));

    if ($kname === '') {
        // on purge les entrées vides
        $stmtDeleteNotfound->execute([':id' => $nfId]);
        continue;
    }
    if (!function_exists('clean_url')) {
        throw new RuntimeException("clean_url() est requis.");
    }
    $slug = clean_url($r['keywordname'],"-");

    try {
        // existe déjà ?
        $stmtSelectExisting->execute([':name' => $kname]);
        $existing = $stmtSelectExisting->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $keywordId = (int)$existing['id'];
        } else {
            $stmtInsertKeyword->execute([':name' => $kname, ':url' => $slug]);
            $keywordId = (int)$pdo->lastInsertId();
            if ($keywordId <= 0) {
                //throw new RuntimeException("lastInsertId invalide pour '{$kname}'.");
            }
        }


        // Insérer les nouvelles annonces et supprimer les anciennes.
        updateAds($pdo, $ebay_marketplace, $_EBAY_MAX_ADS, $countryCode, $nfId, $keywordId, "notfound");
          

        // Suppression de l'entrée notfound
        //if(isset($importValid) && $importValid === true){
        $stmtDeleteNotfound->execute([':id' => $nfId]);
        echo "OK: '{$kname}' ($countKeywords/$MAX_keyword) => keyword_id={$keywordId}; KW not found #{$nfId} supprimé.\n";
        $countKeywords++;
        //}

    } catch (Throwable $e) {
        error_log("Erreur notfound #{$nfId} ({$kname}) : " . $e->getMessage());
        echo "ERREUR: notfound #{$nfId} - " . $e->getMessage() . "\n";
        // On garde l'entrée pour retente ultérieure
        continue;
    }
}

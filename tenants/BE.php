<?php
/*
NE PAS OVERWRITE EN PRODUCTION 
*/

$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 8889,  
    'dbname'   => 'BE',
    'user'     => 'BE',
    'pass'     => 'test',
    'charset'  => 'utf8mb4',
];

$WebsiteName = "Site Annonce Belgique";
$rootDomain = "http://localhost:8888"; // Domain
$base = '/SH/';   // For local env
$ebayRootURL = "https://www.ebay.fr";
$ebay_mkrid = "710-53481-19255-0";
$ebay_campid = "5339107427";
$ebay_siteid = 3;
$defaultEbayUrl = $ebayRootURL."/?mkcid=1&mkrid=".$ebay_mkrid."&siteid=".$ebay_siteid."&campid=".$ebay_campid."&customid=&toolid=10001&mkevt=1";
$currency = "€";
$priceCurrencySchema = "EUR";
$umami_website_id = "eb232b08-8a50-49b2-9151-d26bc4ae1507";
$mainLanguage = "FR";
$ebay_marketplace = 'EBAY_FR';
$label_distance_value = 'km';

/* analytics script */
$analyticsHead = '<script defer src="https://cloud.umami.is/script.js" data-website-id="WEBSITE_ID_TO_REPLACE"></script>';


/* Redirections anciennes URL */
$redirectMap = [
    // EXEMPLES
    '/s/old-page-thatsuck'          => '/',
    '/s/catego/old-page'           => '/',
];

/* Specific outpush. Deactivated for Edge because it prevents user to click on the page before interacting with the box */
$outpush = '<script>window.pushMST_config={"vapidPK":"BAkoFCwnijpHBlyy-f64UTKGxfkaTIbwkLYdE6khz61klyRNhwxqJl1g44iNB0ohBr2No_kpDTtL0jLwrO-QDeI","enableOverlay":true,"swPath":"/sw.js","i18n":{}};
  var pushmasterTag = document.createElement(\'script\');
  pushmasterTag.src = "https://cdn.pushmaster-cdn.xyz/scripts/publishers/618db320ab098700095b5267/SDK.js";
  pushmasterTag.setAttribute(\'defer\',\'\');

  var firstScriptTag = document.getElementsByTagName(\'script\')[0];
  firstScriptTag.parentNode.insertBefore(pushmasterTag, firstScriptTag);
</script>';



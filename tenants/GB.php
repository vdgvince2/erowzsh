<?php
/*
DO NOT OVERWRITE IN PRODUCTION
*/

$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 8889,  
    'dbname'   => 'UK',
    'user'     => 'UK',
    'pass'     => 'test',
    'charset'  => 'utf8mb4',
];

$WebsiteName = "For Sale";
$rootDomain = "http://localhost:8888"; // Domain
$base = '/SH/';   // For local env
$ebayRootURL = "https://www.ebay.co.uk";
$ebay_mkrid = "710-53481-19255-0";
$ebay_campid = "5339107427";
$ebay_siteid = 3;
$defaultEbayUrl = $ebayRootURL."/?mkcid=1&mkrid=".$ebay_mkrid."&siteid=".$ebay_siteid."&campid=".$ebay_campid."&customid=&toolid=10001&mkevt=1";
$currency = "£";
$priceCurrencySchema = "GBP";
$umami_website_id = "";
$mainLanguage = "EN";
$ebay_marketplace = 'EBAY_GB';
$label_distance_value = 'mi';

/* analytics script */
$analyticsHead = '<script defer src="https://cloud.umami.is/script.js" data-website-id="WEBSITE_ID_TO_REPLACE"></script>';


/* Redirections anciennes URL */
$redirectMap = [
    // EXEMPLES
    '/s/old-page-thatsuck'          => '/',
    '/s/catego/old-page'           => '/',
];

$outpush = '<script>window.pushMST_config={"vapidPK":"BHZJZ-RAfowe6J0icUYNp6QcGiIxxE1H3he7Jkd0uqnqLJ0obpCbKIT4joeBxzfoT04eepuDPj3rPclDtbBdECA","enableOverlay":true,"swPath":"/sw.js","i18n":{}};
  var pushmasterTag = document.createElement(\'script\');
  pushmasterTag.src = "https://cdn.pushmaster-cdn.xyz/scripts/publishers/618db320ab098700095b5267/SDK.js";
  pushmasterTag.setAttribute(\'defer\',\'\');

  var firstScriptTag = document.getElementsByTagName(\'script\')[0];
  firstScriptTag.parentNode.insertBefore(pushmasterTag, firstScriptTag);
</script>';



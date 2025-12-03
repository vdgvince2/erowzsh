<?php

$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 8889,  
    'dbname'   => 'IT',
    'user'     => 'IT',
    'pass'     => 'test',
    'charset'  => 'utf8mb4',
];

$WebsiteName = "In vendita";
$rootDomain = "http://localhost:8888"; // Domain
$base = '/SH/';   // For local env
$ebayRootURL = "https://www.ebay.it";
$ebay_mkrid = "710-53481-19255-0";
$ebay_campid = "5339107427";
$ebay_siteid = 3;
$defaultEbayUrl = $ebayRootURL."/?mkcid=1&mkrid=".$ebay_mkrid."&siteid=".$ebay_siteid."&campid=".$ebay_campid."&customid=&toolid=10001&mkevt=1";
$currency = "€";
$priceCurrencySchema = "EUR";
$umami_website_id = "a70376ba-8dbb-459a-a104-3e8214136b01";
$mainLanguage = "IT";
$ebay_marketplace = 'EBAY_IT';
$label_distance_value = 'km';

/* analytics script */
$analyticsHead = '<!-- 100% privacy-first analytics -->
<script data-collect-dnt="true" async src="https://scripts.simpleanalyticscdn.com/latest.js"></script>';


/* Redirections anciennes URL */
$redirectMap = [
    // EXEMPLES
    '/s/old-page-thatsuck'          => '/',
    '/s/catego/old-page'           => '/',
];

$outpush = '<script>window.pushMST_config={"vapidPK":"BBjHzeCJ5gNvrpLNCQuV3fYHhc3lC7YxBSkO3yriHyBU9dsyRTZTmQtEVMPPG44d6ifHmPe8cFo4BIPW1WPUx5g","enableOverlay":true,"swPath":"/sw.js","i18n":{}};
  var pushmasterTag = document.createElement(\'script\');
  pushmasterTag.src = "https://cdn.pushmaster-cdn.xyz/scripts/publishers/618db320ab098700095b5267/SDK.js";
  pushmasterTag.setAttribute(\'defer\',\'\');

  var firstScriptTag = document.getElementsByTagName(\'script\')[0];
  firstScriptTag.parentNode.insertBefore(pushmasterTag, firstScriptTag);
</script>';



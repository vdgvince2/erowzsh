<?php

$dbConfig = [
    'host'     => '127.0.0.1',
    'port'     => 8889,  
    'dbname'   => 'FR',
    'user'     => 'FR',
    'pass'     => 'test',
    'charset'  => 'utf8mb4',
];

$WebsiteName = "Site Annonce France";
$rootDomain = "http://localhost:8888"; // Domain
$base = '/SH/';   // For local env
$ebayRootURL = "https://www.ebay.fr";
$ebay_mkrid = "710-53481-19255-0";
$ebay_campid = "5339107427";
$ebay_siteid = 3;
$defaultEbayUrl = $ebayRootURL."/?mkcid=1&mkrid=".$ebay_mkrid."&siteid=".$ebay_siteid."&campid=".$ebay_campid."&customid=&toolid=10001&mkevt=1";
$currency = "€";
$priceCurrencySchema = "EUR";
$umami_website_id = "";
$mainLanguage = "FR";
$ebay_marketplace = 'EBAY_FR';
$label_distance_value = 'km';

/* lien amazon spécifique FR >> KEYWORD_TO_REPLACE*/
$_AMAZON_AFFILIATE_LINK = "https://www.amazon.fr/s?k=KEYWORD_TO_REPLACE&rh=p_n_condition-type%3A15135267031%257C15135268031&__mk_fr_FR=%C3%85M%C3%85%C5%BD%C3%95%C3%91&crid=2NM7PWJ2ED1HI&sprefix=KEYWORD_TO_REPLACE%2Caps%2C108&linkCode=ll2&tag=siteannonce1e-21&linkId=7dbe64940a53385b8173ee0f7dc896c3&language=fr_FR&ref_=as_li_ss_tl";

/* analytics script */
$analyticsHead = '<script defer src="https://cloud.umami.is/script.js" data-website-id="WEBSITE_ID_TO_REPLACE"></script>';

/* Redirections anciennes URL */
$redirectMap = [
    // EXEMPLES
    '/s/old-page-thatsuck'          => '/',
    '/s/catego/old-page'           => '/',
];


$outpush = '<script>window.pushMST_config={"vapidPK":"BBFt770VhmZxG5qRkeo1C37sdnKw3BQ4uysDNLB3fFy6LeeLG4eT6cYAl2ud4o2sSP9dkmNwIw0MQF4d50GrWXA","enableOverlay":true,"swPath":"/sw.js","i18n":{}};
  var pushmasterTag = document.createElement(\'script\');
  pushmasterTag.src = "https://cdn.pushmaster-cdn.xyz/scripts/publishers/618db320ab098700095b5267/SDK.js";
  pushmasterTag.setAttribute(\'defer\',\'\');

  var firstScriptTag = document.getElementsByTagName(\'script\')[0];
  firstScriptTag.parentNode.insertBefore(pushmasterTag, firstScriptTag);
</script>';


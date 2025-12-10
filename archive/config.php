<?php
/*
declare(strict_types=1);
session_start();

// EBAY
const EBAY_CLIENT_ID     = ''; // add your keys here
const EBAY_CLIENT_SECRET = '';

// check if we are local for web and for CLI.
$isLocal = false;
$hostname = gethostname();
if($isLocal == false && preg_match("#MacBook#", $hostname)) $isLocal = true; else $isLocal = false;


// prepare only if the page is served for the web (not CLI)
if(isset($_SERVER['HTTP_HOST'])){
    // Prepare the full URL
    $SERVER_PageFullURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $SERVER_WebsiteName = $SERVER_PageFullURL."://".$_SERVER['HTTP_HOST'];
    $SERVER_PageFullURL .= "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    // Specific per browser
    $isEdge = (strpos($_SERVER['HTTP_USER_AGENT'], 'Edg/') !== false) || (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge/') !== false);
    $isLocal    = preg_match('/^localhost(:\d+)?$/', $_SERVER['HTTP_HOST']);
}

// Check domain name if not forced by the script (ex: sitemap)
if(!isset($countryCode)){
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = preg_replace('/^www\./', '', $host);
    $parts = explode('.', $host);
    $tld   = end($parts);
    $secondTld = implode('.', array_slice($parts, -2));

    // force the country TLD
    if(isset($argv[1]) && $argv[1] != ""){
        $tld = strtolower($argv[1]);
        $secondTld = strtolower($argv[1]);
        echo "Country passed : $tld".PHP_EOL;
    } 
    // Select the config based on the TLD
    switch (true) {
        case str_ends_with($secondTld, 'co.uk'):
        case $tld === 'uk':
            $countryCode = 'GB';
            break;
        case $tld === 'be':
            $countryCode = 'BE';
            break;        
        case $tld === 'ie':
            $countryCode = 'IE';
            break;
        case $tld === 'fr':
            $countryCode = 'FR';
            break;
        case $tld === 'de':
            $countryCode = 'DE';
            break;
        case $tld === 'it':
            $countryCode = 'IT';
            break;
        case $tld === 'com':
            $countryCode = 'EROWZ';
            break;
        case $tld === 'forsale':
            $countryCode = 'US';
            break;   
        case $tld === 'localhost:8888':
            $countryCode = 'US';
            break;                            
        default:            
            $countryCode = 'EROWZ'; 
            break;
        
    }
}


// Load the local files
require __DIR__ . '/../tenants/'.$countryCode.'.php'; 
require __DIR__ . '/../languages/'.$mainLanguage.'.php'; 

// URLS
if(isset($_SERVER['REQUEST_URI'])){
    $URI = $_SERVER['REQUEST_URI']; 
    if (strpos($URI, $base) === 0) {
        $URI = substr($URI, strlen($base));
    }
    $URI = trim($URI, "/");
}


// Connect to mysql
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    die("❌ Erreur connexion MySQL : " . $e->getMessage());
}

// Secret key for visted page
define('VISITED_SECRET', 'generate a 64 chars key'); // 64+ chars

// nombre pair pour l'affiche cohérent. 
$_EBAY_MAX_ADS = 48;

// Pub

// pour google shopping
$googleadsenseHead = '<script async src="https://www.google.com/adsense/search/ads.js"></script>
<script type="text/javascript" charset="utf-8">
(function(g,o){g[o]=g[o]||function(){(g[o][\'q\']=g[o][\'q\']||[]).push(
arguments)},g[o][\'t\']=1*new Date})(window,\'_googCsa\');
</script>';
    
// Google adense désactivé pour améliorer l'expérience.
$googleadsense_topBody = ''; //'<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0809996796910370" crossorigin="anonymous"></script>'; 


*/
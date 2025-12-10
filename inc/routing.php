<?php
/*
    ROUTING MANAGEMENT
*/

/*****  Check if we are local for web and for CLI.  */
$isLocal = false;
$hostname = gethostname();
if($isLocal == false && preg_match("#MacBook#", $hostname)) $isLocal = true; else $isLocal = false;





/******  Variables for JSON_LD + LOCAL DECTECTION */
if(isset($_SERVER['HTTP_HOST'])){
    // Prepare the full URL
    $SERVER_Protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $SERVER_WebsiteName = $SERVER_Protocol."://".$_SERVER['HTTP_HOST'];
    $SERVER_PageFullURL = $SERVER_Protocol."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    // Specific per browser
    $isEdge = (strpos($_SERVER['HTTP_USER_AGENT'], 'Edg/') !== false) || (strpos($_SERVER['HTTP_USER_AGENT'], 'Edge/') !== false);
    $isLocal    = preg_match('/^localhost(:\d+)?$/', $_SERVER['HTTP_HOST']);
}

// Script Name behind rewriting
$scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');



/*****  Check domain name if not forced by the script (ex: sitemap)*/
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
        default:            
            $countryCode = 'IE'; 
            break;
        
    }
}

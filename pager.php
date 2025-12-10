<?php
/**
 * DOES THE ROUTING INSTEAD OF THE .htaccess
 * THIS HELPS TO BETTER HANDLE THE COMPLEX CASES (ex: sub domains)
 */


require __DIR__ . '/inc/config.php'; 
require __DIR__ . '/inc/functions.php'; 


/*****  TEST IF SUBDOMAIN   */
$isSub = false;
$subDomain = get_subdomain_prefix();
if ($subDomain !== false) {
    $noAds = true;
    $isSub = true;
    require 'template.php';
    exit;
}


// On ne se base que sur ?path, sinon homepage
$path = isset($_GET['path']) ? $_GET['path'] : '';
$path = trim($path, '/'); // normalisation


// --- Exemple homepage ---
if ($path === '') {
    require 'homepage.php';
    exit;
}


// 4) Redirection /r/Details/.* -> homepage (cas Irlande)
if (preg_match('#^r/Details/#i', $path)) {
    header("Location: /", true, 301);
    exit;
}

// 5) Redirections search
if ($path === 's/search' || $path === 'search.php') {
    require 'bargain.php';
    exit;
}

// 6) Pages statiques type /s/cookies, /s/help, etc.
if (preg_match('#^s/(.+)$#i', $path, $m)) {
    $slug = rtrim($m[1], '/');

    $map = [
        'cookies'                  => 'cookies',
        'help'                     => 'help',
        'contact'                  => null,  // route spécifique
        'cart'                     => null,
        'bargain'                  => null,        
        'privacy'                  => 'privacy',
        'press'                    => 'press',
        'about'                    => 'about',        
        'home/categoriesindex'     => 'home/categoriesindex',
        'registration'             => 'registration',
        'money-back'               => 'money-back-guarantee',
        'money-back-guarantee'     => 'money-back-guarantee',
        'bidding-and-buying-help'  => 'bidding-and-buying-help',
        'stores'                   => 'stores',
        'start-selling'            => 'start-selling',
        'learn-to-sell'            => 'learn-to-sell',
        'business-sellers'         => 'business-sellers',
        'seller-centre'            => 'seller-centre',
        'developers'               => 'developers',
        'security-centre'          => 'security-centre',
        'site-map'                 => 'site-map',
        'official-time'            => 'official-time',
        'myaccount'                => 'myaccount',
        'watchlist'                => 'watchlist',
    ];

    if (array_key_exists($slug, $map)) {
        if ($slug === 'contact') {
            require 'contact.php';
            exit;
        }
        if ($slug === 'cart') {
            require 'cart.php';
            exit;
        }
        if ($slug === 'bargain') {
            require 'bargain.php';
            exit;
        }        

        $pageSlug = $map[$slug];
        require 'page.php';
        exit;
    }

    // Si ça commence par s/ mais pas dans la map, on continue plus bas
}

// 7) Catégories : /s/level1[/level2[/level3]]
if (preg_match('#^s/([^/]+)$#', $path, $m)) {
    $_GET['categ']  = 1;
    $_GET['level1'] = $m[1];
    require 'template.php';
    exit;
}
if (preg_match('#^s/([^/]+)/([^/]+)$#', $path, $m)) {
    $_GET['categ']  = 1;
    $_GET['level1'] = $m[1];
    $_GET['level2'] = $m[2];
    require 'template.php';
    exit;
}
if (preg_match('#^s/([^/]+)/([^/]+)/([^/]+)$#', $path, $m)) {
    $_GET['categ']  = 1;
    $_GET['level1'] = $m[1];
    $_GET['level2'] = $m[2];
    $_GET['level3'] = $m[3];
    require 'template.php';
    exit;
}

// 8) Mots-clés : /keyword-slug
if (preg_match('#^([a-z0-9\-]+)$#i', $path, $m)) {
    $_GET['keyword'] = $m[1];
    require 'template.php';
    exit;
}

// 9) Fallback global
require 'fallback.php';
exit;

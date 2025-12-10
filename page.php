<?php
// don't display ads for this page
$noAds = true;

// Sécurité de base
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');

// 1) Récup & normalisation du paramètre
$raw = $pageSlug ?? '';
$slug = trim(strtolower($raw), "/ \t\n\r\0\x0B");

// 2) Validation stricte : lettres, chiffres, tirets et éventuellement un seul slash (ex: home/categoriesindex)
if ($slug === '' || !preg_match('#^[a-z0-9\-]+(?:/[a-z0-9\-]+)?$#', $slug)) {
    http_response_code(400);
    echo "<h1>400 – Requête invalide</h1><p>Paramètre manquant ou incorrect.</p>";
    exit;
}

// 3) Whitelist des slugs autorisés → mapping vers fichiers de template
$routes = [
    'cookies'               => 'cookies.php',
    'help'                  => 'help.php',
    'privacy'               => 'privacy.php',
    'press'                 => 'press.php',
    'about'                 => 'about.php',
    'home/categoriesindex'  => 'home-categoriesindex.php',

    // NEW static pages
    'registration'              => 'registration.php',
    'money-back-guarantee'      => 'money-back-guarantee.php',
    'bidding-and-buying-help'   => 'bidding-and-buying-help.php',
    'stores'                    => 'stores.php',
    'start-selling'             => 'start-selling.php',
    'learn-to-sell'             => 'learn-to-sell.php',
    'business-sellers'          => 'business-sellers.php',
    'seller-centre'             => 'seller-centre.php',
    'developers'                => 'developers.php',
    'security-centre'           => 'security-centre.php',
    'site-map'                  => 'site-map.php',
    'official-time'             => 'official-time.php',
    'myaccount'                 => 'myaccount.php',
    'watchlist'                 => 'watchlist.php',
];

$seoTitles = [
    'cookies'               => 'Cookies – Informations',
    'help'                  => 'Aide – Centre d’assistance',
    'privacy'               => 'Confidentialité – Politique de vie privée',
    'press'                 => 'Presse – Dossier & contacts',
    'about'                 => 'À propos – Notre histoire',
    'home/categoriesindex'  => 'Index des catégories',

    // NEW SEO titles (EN)
    'registration'              => 'Registration – Create and manage your account',
    'money-back-guarantee'      => 'Money Back Guarantee – Buyer protection',
    'bidding-and-buying-help'   => 'Bidding & Buying Help – How to shop safely',
    'stores'                    => 'Stores – Find trusted sellers and brands',
    'start-selling'             => 'Start Selling – Open your shop in minutes',
    'learn-to-sell'             => 'Learn to Sell – Guides and best practices',
    'business-sellers'          => 'Business Sellers – Tools for companies',
    'seller-centre'             => 'Seller Centre – Policies, tools & support',
    'developers'                => 'Developers – API, docs & integrations',
    'security-centre'           => 'Security Centre – Safety, privacy & reporting',
    'site-map'                  => 'Site Map – Explore our sections',
    'official-time'             => 'Official Time – Server time & auctions',
    'myaccount'                 => 'My account',
    'watchlist'                 => 'Watchlist',
];

// 4) Résolution de la route
if (!array_key_exists($slug, $routes)) {
    http_response_code(404);
    echo "<h1>404 – Page introuvable</h1><p>La page demandée n’existe pas.</p>";
    exit;
}

// 5) Inclusion sécurisée du contenu (dans un dossier dédié)
$pagesDir   = __DIR__ . '/pages/';            // crée un dossier /pages à côté de page.php
$template   = $pagesDir . $routes[$slug];

// Empêche toute inclusion si le fichier n’existe pas
if (!is_file($template)) {
    // Fallback propre si le fichier n’a pas encore été créé
    http_response_code(501);
    echo "<h1>501 – Contenu non prêt</h1><p>Le template <code>"
         . htmlspecialchars($routes[$slug], ENT_QUOTES, 'UTF-8')
         . "</code> n’a pas encore été déployé.</p>";
    exit;
}

$pageTitle = $seoTitles[$slug] ?? 'Page';
?>
<!doctype html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">

<?php    require __DIR__ . '/inc/head-scripts.php'; ?>

<body>
<?php require __DIR__ . '/inc/header.php'; ?>

<div class="container mx-auto px-4 py-4">
    <h1 class="text-xl mb-10"><?=$pageTitle;?></h1>
<?php
    // 7) Inclusion du contenu
    // Chaque template est responsable de son propre HTML
    include $template;
?>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>

<?php

/****
 * Fallback général 
 * pour les redirections
 * pour les 404
 * 
 */

// don't display ads for this page
$noAds = true;

include_once __DIR__.'/inc/config.php';
include_once __DIR__.'/inc/functions.php';

/**
 * Si ton app est dans un sous-dossier (ex: /SH), précise-le ici pour
 * retirer ce préfixe lors de la comparaison. Laisse vide si racine domaine.
 */
$basePath = rtrim($base, '/'); // '/SH' ou ''

$requestUri = str_replace($base, "", $_SERVER['REQUEST_URI']) ?? '/';

// Retire le préfixe basePath pour matcher les clés telles que stockées dans $redirectMap
$matchKey = $requestUri;
if ($basePath !== '' && strpos($requestUri, $basePath . '/') === 0) {
    $matchKey = substr($requestUri, strlen($basePath));
    if ($matchKey === '') $matchKey = '/';
}
$matchKey = normalize_path($matchKey); // sécurité

// don't save anything else but keyword.
// dismiss : sh-img, /f/, /r/, /.well-known/assetlinks.json, /s/musical-instruments/cluster-250/subcluster-1148
// Save in DB the 404 page.
if (!preg_match('#(?:[^/]*/){2,}|\.#', $requestUri)) {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  $botPattern = '/(?:bot|crawl|spider|slurp|bingbot|bingpreview|duckduckbot|baiduspider|'
            . 'yandex(?:bot|images|news)?|semrush(?:bot)?|ahrefs(?:bot)?|mj12bot|dotbot|'
            . 'seznambot|sogou|exabot|ia_archiver|facebookexternalhit|facebot|twitterbot|'
            . 'pinterest|linkedinbot|telegrambot|discordbot|applebot|petalbot|gptbot|ccbot|'
            . 'bytespider|perplexitybot|claudeweb|copilot|openai-async|'
            . 'HeadlessChrome|PhantomJS|Puppeteer|Playwright|Selenium|'
            . 'node-fetch|okhttp|libwww-perl|python-requests|aiohttp|httpclient|go-http-client|'
            . 'curl|wget|PostmanRuntime|Insomnia)/i';

  if ($ua === '' || preg_match($botPattern, $ua)) {
      http_response_code(429);
      exit('Too many requests');
  }else{
    // prepre the kehword
    $matchKey = str_replace("-", " ", $matchKey);
    $stmt = $pdo->prepare("INSERT INTO notfound (keywordname) VALUES (:keywordname) ON DUPLICATE KEY UPDATE last_detected = now()");
    $stmt->execute([':keywordname' => $matchKey ]);
  }
  
}else{
  echo "regex not matched :".$requestUri;
}

// 1) Si l’URL est dans la map → 301
if (!empty($redirectMap[$matchKey])) {
    $target = $redirectMap[$matchKey];

    // Sécurité: n’autorise que des chemins relatifs internes
    if (strpos($target, '/') !== 0) {
        // si erreur de config, on force root-relative
        $target = '/' . ltrim($target, '/');
    }

    // Conserve la query string si elle existe
    $qs = parse_url($requestUri, PHP_URL_QUERY);
    if ($qs) {
        // N’ajoute la query que si la cible n’en contient pas déjà
        $target .= (strpos($target, '?') === false ? '?' . $qs : '&' . $qs);
    }

    // Reconstruit URL absolue correcte (http/https + host + basePath)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Réapplique basePath si l’app n’est pas à la racine
    $absolute = $scheme . '://' . $host . $basePath . $target;

    header('Cache-Control: no-store');
    header('Location: ' . $absolute, true, 301);
    exit;
}

// 2) Sinon, 404 propre + search
http_response_code(404);
header('X-Robots-Tag: noindex, nofollow');

$pageTitle = $label_search_notfound;

?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">
<head>
  <?php require __DIR__ . '/inc/head-scripts.php'; ?>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin:0; background:#fafafa; }
    .wrap { max-width: 760px; margin: 6rem auto; background:#fff; padding:2rem; border:1px solid #eee; border-radius:16px; }
    h1 { margin:0 0 0.5rem 0; font-size: clamp(1.6rem, 2.5vw, 2.2rem); }
    p { color:#555; }
    form { margin-top:1.25rem; display:flex; gap:.5rem; }
    input[type="text"] { flex:1; padding:.8rem 1rem; border:1px solid #ddd; border-radius:12px; font-size:1rem; outline:none; }
    button { padding:.8rem 1.1rem; border:none; background:#111; color:#fff; border-radius:12px; cursor:pointer; }
    a { color:#0a58ca; text-decoration:none; }
  </style>
</head>
<body>
  <?php require __DIR__ . '/inc/header.php'; ?>
  <div class="wrap">
    <h1><?=$label_search_title;?></h1>
    <p><?=$label_search_somethingwrong;?> <code><?php if(isset($errorInfo)) echo $errorInfo;?></code></p>
    <p><?=$label_search_try;?></p>

    <form action="search.php" method="post">
    <input type="text" name="keyword"  placeholder="ex: ipad..." class="border border-gray-300 px-4 py-2 ">
    <button class="px-4 bg-blue-600 text-white rounded-r-md hover:bg-blue-700">
        <?=$label_search_button;?>
    </button>
    </form>

    <p style="margin-top:1rem;">
      <?=$label_search_goback;?> <a href="<?= $rootDomain.$base; ?>"><?=$label_search_homepage;?></a>.
    </p>
  </div>

  <?php require __DIR__ . '/inc/footer.php'; ?>
</body>
</html>

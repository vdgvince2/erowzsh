<?php

/**
 * PRE CONFIG
 * WITH URL FOR KEYWORD PAGE
 * AND MYSQL CONNECTOR
 */

/* GESTION DES URLS */
if(isset($_SERVER['REQUEST_URI'])){
    $URI = $_SERVER['REQUEST_URI']; 
    if (strpos($URI, $base) === 0) {
        $URI = substr($URI, strlen($base));
    }
    $URI = trim($URI, "/");
}

/*** MYSQL CONNECTOR */
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

/***
 *  START ALL THE FUNCTIONS
 *  
 */

/*****  Manage the subdomains */
function get_subdomain_prefix() {
    if (empty($_SERVER['HTTP_HOST'])) {
        return false;
    }

    // Enlève le port éventuel : "billy.localhost:8888" → "billy.localhost"
    $host = explode(':', $_SERVER['HTTP_HOST'])[0];

    $parts = explode('.', $host);
    $count = count($parts);

    // Cas spécial localhost : billy.localhost, api.localhost, etc.
    if ($parts[$count - 1] === 'localhost') {
        // "localhost" seul → pas de sous-domaine
        if ($count === 1) {
            return false;
        }
        // "billy.localhost" → sous-domaine = "billy"
        return $parts[0];
    }

    // Liste (non exhaustive) de TLD multi-part
    // à adapter si tu as d'autres cas (com.au, co.nz, etc.)
    $multiPartTlds = [
        'co.uk',
        'ac.uk',
        'gov.uk',
        'co.jp',
        'com.au',
        'com.br',
        'co.nz',
    ];

    // Détermine si on est sur un TLD "multi-part"
    $tldSuffix = ($count >= 2)
        ? $parts[$count - 2] . '.' . $parts[$count - 1]
        : '';

    $minDomainParts = in_array($tldSuffix, $multiPartTlds, true) ? 3 : 2;

    // Si on n'a pas plus de parties que le "domaine de base" → pas de sous-domaine
    if ($count <= $minDomainParts) {
        return false;
    }

    // Gestion de "www" comme faux sous-domaine
    // Exemple :
    // - www.for-sale.co.uk  (count=4, minDomainParts=3) → une seule partie avant le domaine = "www" → on ignore → false
    // - www.api.for-sale.co.uk (count=5, minDomainParts=3) → deux parties avant le domaine = "www.api"
    //   → on ignore "www" → sous-domaine = "api"
    $subdomainPartsCount = $count - $minDomainParts;

    if ($parts[0] === 'www') {
        if ($subdomainPartsCount === 1) {
            // Il n’y a que "www" avant le domaine → on considère qu’il n’y a pas de sous-domaine
            return false;
        }
        // Plus d’une partie avant le domaine → on prend la partie après "www"
        return $parts[1];
    }

    // Sinon, on prend simplement le premier préfixe
    return $parts[0];
}




function clean_url($string, $replacer = "-") {

    // 1. Convertir en minuscules
    if($string != null){
    $string = strtolower($string);

    // 2. Supprimer les accents
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

    // 3. Supprimer les caractères spéciaux (tout sauf lettres, chiffres et espaces)
    $string = preg_replace('/[^a-z0-9\s-]/', $replacer, $string);

    // 4. Remplacer les espaces et multiples tirets par un seul tiret
    $string = preg_replace('/[\s-]+/', $replacer, $string);

    // 5. Supprimer les tirets au début et à la fin 
    $string = trim($string, $replacer);    

    return $string;

    }
}

function ebayKeywordPrepare($string) {
    // 1. Convertir en minuscules
    if($string != null){
    $string = strtolower($string);

    // 2. Supprimer les accents
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

    // 3. Supprimer les caractères spéciaux (tout sauf lettres, chiffres et espaces)
    $string = preg_replace('/[^a-z0-9\s-]/', ' ', $string);

    // supprimer double espaces
    $string = preg_replace('/ {2,}/', ' ', $string);

    // 4. Remplacer les espaces et multiples tirets par un seul tiret
    $string = preg_replace('/[\s-]+/', ' ', $string);

    // 5. Supprimer les tirets au début et à la fin
    $string = trim($string, '');

    return $string;

    }
}

// Prepare the Analytics tracker per website
function AnalyticsTracker($analyticsHead, $umami_website_id){

    $ret = str_replace("WEBSITE_ID_TO_REPLACE", $umami_website_id, $analyticsHead);

    return $ret;
}


function randomSticker() {
    global $arraySticker;
    $key = array_rand($arraySticker);
    return $arraySticker[$key];
}


function cleanString(?string $s): ?string {
    if ($s === null) return null;
    $s = trim($s);
    return ($s === '') ? null : $s;
}

// Petite normalisation d’affichage des prix (on garde le format CSV, juste trim)
function displayPrice(?string $s): ?string {
    $s = cleanString($s);
    if ($s === null) return null;
    // On n’impose pas de conversion EU->US ici, c’est l’affichage.
    return $s;
}

// for the fallback page
function normalize_path(string $p): string {
    // garde uniquement le path, enlève query/fragment si jamais
    $p = parse_url($p, PHP_URL_PATH) ?: '/';
    // decode %xx, trim espaces, force lowercase
    $p = urldecode($p);
    $p = trim($p);
    $p = strtolower($p);
    // normalise les slashes: un seul leading slash, pas de trailing (sauf root)
    $p = preg_replace('#/{2,}#', '/', $p);
    if ($p !== '/') $p = rtrim($p, '/');
    if ($p === '') $p = '/';
    return $p;
}

function prettyNameFromSlug(string $slug): string {
    // 1) extraire le path, décoder, nettoyer
    $path = parse_url($slug, PHP_URL_PATH) ?? $slug;
    $path = urldecode($path);
    $path = trim($path, "/ \t\n\r\0\x0B");
    if ($path === '') return '';

    // 2) découper les segments non vides
    $segments = array_values(array_filter(explode('/', $path), fn($s) => $s !== ''));

    // 3) normaliser chaque segment: "-" / "_" → espaces, compactage, Title Case
    $format = function (string $s): string {
        $s = str_replace(['-', '_'], ' ', $s);
        $s = preg_replace('/\s{2,}/', ' ', $s);
        $s = trim($s);
        if ($s === '') return '';
        $s = mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        // préserver quelques acronymes courants
        $s = preg_replace_callback('/\b(uk|usa|eu|usb|ssd|ram|cpu|gpu)\b/i', fn($m) => strtoupper($m[1]), $s);
        return $s;
    };

    $pretty = array_map($format, $segments);
    return implode(' > ', $pretty);
}

// inject CSS in the right page.
function inline_css_for_page(): void {
    global $isLocal;

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');

    // en local, on affiche la version non compilée.
    if(!$isLocal){        
        echo inline_asset('assets/tailwind.css', ['attrs' => ['id' => 'inline-css']])."\n";
    }else{
        echo '<script src="https://cdn.tailwindcss.com"></script>'."\n";
    }

    // show global for prod & local
    echo inline_asset('assets/global.css', ['attrs' => ['id' => 'inline-css']])."\n";
}

/**
 * Inline a local CSS or JS file inside <style> or <script>.
 *
 * @param string $path      File path (absolute ou relatif au docroot).
 * @param array  $options   [
 *   'base_dir'  => $_SERVER['DOCUMENT_ROOT'], // répertoire racine autorisé
 *   'max_bytes' => 524288,                    // 512 KB par défaut
 *   'nonce'     => null,                      // ex: CSP nonce
 *   'attrs'     => ['id' => 'inline-asset']   // attributs HTML additionnels
 * ]
 * @return string HTML tag (ou commentaire HTML en cas d’erreur)
 */
function inline_asset(string $path, array $options = []): string {
    
    $maxBytes = $options['max_bytes'] ?? 524288;
    $nonce    = $options['nonce']     ?? null;
    $attrs    = $options['attrs']     ?? [];

    // Interdit les URLs distantes
    if (preg_match('~^(?:https?:)?//~i', $path)) {
        return "<!-- inline_asset error: remote URLs not allowed -->";
    }

    $fileReal = $path;

    // Extension
    $ext = strtolower(pathinfo($fileReal, PATHINFO_EXTENSION));
    if (!in_array($ext, ['css', 'js'], true)) {
        return "<!-- inline_asset error: unsupported extension -->";
    }

    // Taille
    $size = @filesize($fileReal);
    if ($size === false || $size > $maxBytes) {
        return "<!-- inline_asset error: file too large ({$size} bytes) -->";
    }

    // Lecture
    $content = @file_get_contents($fileReal);
    if ($content === false) {
        return "<!-- inline_asset error: cannot read file -->";
    }

    // Normalisation encodage (optionnel, sûr en pratique)
    if (function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
        $enc = mb_detect_encoding($content, ['UTF-8','ISO-8859-1','WINDOWS-1252'], true);
        if ($enc && strtoupper($enc) !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $enc);
        }
    }

    // Échapper séquences de fermeture pour éviter de casser le HTML
    if ($ext === 'js') {
        $content = str_replace('</script>', '<\/script>', $content);
    } else { // css
        $content = str_replace('</style>', '<\/style>', $content);
    }

    // remove breaklines & tabulations
    $content = str_replace(array("\n","\t"), array(), $content);

    // Attributs HTML (nonce, + extra)
    $attrStr = '';
    if ($nonce !== null) {
        $attrs['nonce'] = $nonce;
    }
    foreach ($attrs as $k => $v) {
        // attributs simples, échappés
        $k = preg_replace('~[^a-zA-Z0-9_\-:]~', '', (string)$k);
        $v = htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attrStr .= " {$k}=\"{$v}\"";
    }

    // Commentaire source (utile au debug)
    $relShown = htmlspecialchars($path, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    if ($ext === 'js') {
        return "<script{$attrStr}>\n/* inline from: {$relShown} */\n{$content}\n</script>";
    } else {
        return "<style{$attrStr}>\n/* inline from: {$relShown} */\n{$content}\n</style>";
    }
}

// Remove stopwords from a query
function remove_stopwords(string $expression, array $stopwords, $replacer = ''): string
{
    if (empty($expression) || empty($stopwords)) {
        return trim(preg_replace('/\s+/u', ' ', $expression));
    }

    // Échapper chaque stopword pour la regex et construire un motif alterné
    $escaped = array_map(static function ($w) {
        // Trim + normalisation basique
        $w = trim($w);
        // Échapper pour PCRE
        return preg_quote($w, '/');
    }, $stopwords);

    // Exemple: (?<![\p{L}\p{N}_])(le|la|les|de|d|l)(?:['’])?(?![\p{L}\p{N}_])
    // - Borne gauche/droite: pas de lettre/numéro autour → évite de matcher à l'intérieur d'un mot
    // - (?:['’])? : retire aussi l'apostrophe juste après (l', d', qu', …)
    $pattern = '/(?<![\p{L}\p{N}_])(' . implode('|', $escaped) . ')(?:[\'’])?(?![\p{L}\p{N}_])/iu';

    // Remplacer chaque stopword par un remplacement.
    $clean = preg_replace($pattern, $replacer, $expression);

    // Nettoyer les espaces multiples et espaces autour de la ponctuation
    $clean = preg_replace('/\s+/u', ' ', $clean);
    $clean = preg_replace('/\s+([:;,.\!\?\)])/', '$1', $clean);
    $clean = preg_replace('/([\(\[])\s+/', '$1', $clean);
    $clean = rtrim($clean, "-–—"); // supprime un tiret (ou dash) final s'il existe
    
    return trim($clean);
}

// retrieve the content to display for a product or a category
function get_content($pdo, $id, $type){

    if($type == 'product'){

        $sqlKw = "SELECT * FROM keywords_content where keyword_id = :theid LIMIT 1";

    }elseif($type = 'category'){
        $sqlKw = "SELECT * FROM category_content where category_id = :theid LIMIT 1";
    }

    $stmt2 = $pdo->prepare($sqlKw);
    $stmt2->execute([':theid' => $id]);
    $contentArray = $stmt2->fetchAll();

    if(!empty($contentArray)){
        return $contentArray[0];
    }
    
}


/**
 * Enregistre une alerte de recherche en base en sécurisant au maximum.
 *
 * @param PDO    $pdo
 * @param string $keyword  mot-clé de l'alerte (ex: "papillon dog")
 * @param string $email    email soumis par l'utilisateur
 * @return array ['success' => bool, 'message' => string]
 */
function create_search_alert(PDO $pdo, string $keyword, string $email): array
{
    // 1) Normalisation des entrées
    $keyword = trim($keyword);
    $email   = trim(mb_strtolower($email));

    // 2) Honeypot simple (ajoute un champ caché dans le formulaire, ex: <input type="text" name="website" style="display:none">)
    $honeypot = isset($_POST['website']) ? trim($_POST['website']) : '';
    if ($honeypot !== '') {
        // Bot probable, on fait comme si tout était OK sans rien enregistrer
        return ['success' => true, 'message' => 'OK'];
    }

    // 3) Validation basique
    if ($keyword === '' || mb_strlen($keyword) > 255) {
        return ['success' => false, 'message' => 'Invalid keyword.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    // 4) Récupération IP + UA
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip !== null && mb_strlen($ip) > 45) {
        $ip = mb_substr($ip, 0, 45);
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
    if ($ua !== null && mb_strlen($ua) > 255) {
        $ua = mb_substr($ua, 0, 255);
    }

    // 5) Rate limiting simple pour limiter le spam (par email + IP sur 5 minutes)
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM search_alerts
            WHERE email = :email
              AND created_at >= (NOW() - INTERVAL 5 MINUTE)
        ");
        $stmt->execute([':email' => $email]);
        $countEmail = (int) $stmt->fetchColumn();

        if ($countEmail > 5) {
            return ['success' => false, 'message' => 'Too many requests. Please try again later.'];
        }

        if ($ip !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS c
                FROM search_alerts
                WHERE ip_address = :ip
                  AND created_at >= (NOW() - INTERVAL 5 MINUTE)
            ");
            $stmt->execute([':ip' => $ip]);
            $countIp = (int) $stmt->fetchColumn();

            if ($countIp > 20) {
                return ['success' => false, 'message' => 'Too many requests from this IP address.'];
            }
        }

        // 6) Insert sécurisé (UPSERT pour ne pas créer de doublon)
        $stmt = $pdo->prepare("
            INSERT INTO search_alerts (keyword, email, ip_address, user_agent)
            VALUES (:keyword, :email, :ip, :ua)
            ON DUPLICATE KEY UPDATE
                updated_at = NOW(),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
        ");

        $stmt->execute([
            ':keyword' => $keyword,
            ':email'   => $email,
            ':ip'      => $ip,
            ':ua'      => $ua,
        ]);

        return ['success' => true, 'message' => 'Subscription saved.'];

    } catch (PDOException $e) {
        // Option : log interne, mais ne jamais exposer le détail en front
        // error_log('search_alert error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred, please try again later.'];
    }
}

function homepageBlog($pdo, int $limit = 5): string{

global $label_readmore, $label_publishedon;

    $html = '';

    try {
        $stmt = $pdo->prepare("
            SELECT title, link, description, pub_date, photo
            FROM mag_feed_articles
            WHERE photo <> ''
            ORDER BY pub_date DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll();
    } catch (PDOException $e) {
        // En prod tu logges, ici on affiche un fallback
        return '<li>Erreur lors du chargement des articles.</li>';
    }

    if (!$articles) {
        return '<li>Aucun article pour le moment.</li>';
    }

    foreach ($articles as $article) {
        $title = htmlspecialchars($article['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $link  = htmlspecialchars($article['link'] ?? '', ENT_QUOTES, 'UTF-8');
        $imgUrl = "<img src='".$article['photo']."' class='w-full h-56 object-cover group-hover:scale-[1.02] transition-transform duration-300' loading='lazy'>";

        // Date formatée (optionnel)
        $dateLabel = '';
        if (!empty($article['pub_date'])) {
            $dt = date_create($article['pub_date']);
            if ($dt) {
                $dateLabel = $dt->format('d/m/Y');
            }
        }

        

        $html .= '
        <li class="mb-8">
        <a href="' . $link . '" class="block group">
            <article class="bg-white rounded-3xl shadow-lg hover:shadow-xl transition-shadow duration-200 overflow-hidden">
            
            <div class="p-6">
                <div class="rounded-2xl overflow-hidden bg-gray-100">
                '.$imgUrl.'
                </div>

                <h3 class="mt-6 text-xl font-serif leading-tight text-gray-900">
                ' . $title . '
                </h3>

                ' . (!empty($dateLabel) ? '
                <p class="mt-3 text-lg text-gray-600">
                    -  '.$label_publishedon. " " . $dateLabel . '
                </p>
                ' : '') . '

                <div class="mt-6 text-lg font-semibold text-blue-500 group-hover:underline underline-offset-4">
                '.$label_readmore.' →
                </div>
            </div>

            </article>
        </a>
        </li>';
    }

    return $html;
}

// Build the tracking link for ebay, amazon etc.
/* 
    * URL : if it's a direct link to a product ebay page, null by default
    * extraFilters = for the sidebar filters , null by default
    * condition = for bargain filters, null by default
*/
function tracking_link_builder($keyword, $countryCode, $url = null, $extrafilters = null, $condition = "conditionUsed"){

    global $ebayRootURL, $ebay_mkrid, $ebay_siteid, $ebay_campid, $_AMAZON_AFFILIATE_LINK;

    // prepare the keword for the query search on ebay.
    $ebay_search_keyword = str_replace(" ", "+", ebayKeywordPrepare($keyword));

    // add the affiliation parameters to the final URL
    $affiliate_tracker = "&mkcid=1&mkrid=".$ebay_mkrid."&siteid=".$ebay_siteid."&campid=".$ebay_campid."&customid=".$countryCode."_".urlencode($keyword)."&toolid=10001&mkevt=1";

    // if no url provided, it's a search.
    if($url == null){

        if($condition =="conditionNew"){
        // EBAY NEW CONDITION = 1000
        $conditionFilter = "&LH_ItemCondition=1000";
        }else{
        // EBAY USED & DERIVATED CONDITION = 1000|1500|2010|2020|2030|3000|7000
        $conditionFilter = "&LH_ItemCondition=1500|2010|2020|2030|3000|7000";
        }

        $AffiliateSearchLink = $ebayRootURL."/sch/i.html?_nkw=".$ebay_search_keyword."&_sacat=0&_from=R40&rt=nc".$conditionFilter.$affiliate_tracker.$extrafilters;

    }else{
    // if there is a url provided, just add the trackers
        $AffiliateSearchLink = $url.$affiliate_tracker.$extrafilters;
    }

    // specific France, replace by amazon
    if(in_array($countryCode, array("FR", "BE"))) $AffiliateSearchLink = str_replace("KEYWORD_TO_REPLACE", $ebay_search_keyword, $_AMAZON_AFFILIATE_LINK);

    return $AffiliateSearchLink;
}




// cut a string but don't split a word in two.
function truncate_no_cut($text, $limit = 30)
{
    // Si texte trop court → rien à faire
    if (strlen($text) <= $limit) {
        return $text;
    }

    // On coupe au limit "brut"
    $cut = substr($text, 0, $limit);

    // Si le caractère suivant existe et est un espace → on n’est pas en milieu de mot
    if (isset($text[$limit]) && $text[$limit] === ' ') {
        $result = rtrim($cut);
    } else {
        // Sinon on recule au dernier espace trouvé
        $lastSpace = strrpos($cut, ' ');
        if ($lastSpace !== false) {
            $result = rtrim(substr($cut, 0, $lastSpace));
        } else {
            // Aucun espace avant → on renvoie quand même les 30 chars (mot trop long)
            $result = rtrim($cut);
        }
    }

    // On enlève une éventuelle ponctuation en fin de chaîne (virgule, point, etc.)
    $result = rtrim($result, ",.;:!?");

    return $result;
}



//Ngram extraction from the crawler
function extract_top_ngrams(string $text, int $minN = 1, int $maxN = 3, int $topK = 3): string
{

    global $Array_language_stopwords;
    
    // 1) Normalisation
    $text = strtolower($text);

    // On garde lettres, chiffres, espaces et points
    $text = preg_replace('/[^a-z0-9\s\.]+/', ' ', $text);
    $text = preg_replace('/((?<!\d)\.)|(\.(?!\d))/', ' ', $text);

    $tokens = preg_split('/\s+/', trim($text));

    // 2) Filtrage des tokens (stopwords + très courts)
    $cleanTokens = [];
    foreach ($tokens as $t) {
        if ($t === '' || strlen($t) <= 2) continue;
        if (in_array($t, $Array_language_stopwords, true)) continue;
        $cleanTokens[] = $t;
    }

    $n = count($cleanTokens);
    if ($n === 0) {
        return "";
    }

    // 3) Génération des n-grams + comptage
    $ngrams = [];
    for ($i = 0; $i < $n; $i++) {
        for ($k = $minN; $k <= $maxN; $k++) {
            if ($i + $k > $n) break;

            $ngTokens = array_slice($cleanTokens, $i, $k);

            // évite "5cms 5cms"
            if (count(array_unique($ngTokens)) < count($ngTokens)) {
                continue;
            }

            $ngram = implode(' ', $ngTokens);

            if (!isset($ngrams[$ngram])) {
                $ngrams[$ngram] = ['count' => 0, 'score' => 0];
            }

            $ngrams[$ngram]['count']++;
            $ngrams[$ngram]['score'] = $ngrams[$ngram]['count'] * pow($k, 2);
        }
    }

    // 4) Tri par score
    uasort($ngrams, function ($a, $b) {
        if ($a['score'] === $b['score']) return 0;
        return ($a['score'] > $b['score']) ? -1 : 1;
    });

    // 5) Diversité : pas de mot réutilisé dans plusieurs n-grams
    $selected = [];
    $usedTokens = [];

    foreach ($ngrams as $ngram => $info) {
        if (count($selected) >= $topK) break;

        $tokens = explode(' ', $ngram);

        $hasOverlap = false;
        foreach ($tokens as $t) {
            if (isset($usedTokens[$t])) {
                $hasOverlap = true;
                break;
            }
        }
        if ($hasOverlap) continue;

        $selected[] = ucfirst($ngram);
        foreach ($tokens as $t) {
            $usedTokens[$t] = true;
        }
    }

    // prepare the output
    $shortNgrams = "";
    if(is_array($selected)){
        $shortNgrams = implode(" &#8226; ", $selected);
    }

    return $shortNgrams;
}




/* debug local log file */
function log_local_write($debugLine, $filename){
    global $isLocal, $countryCode;

    $debugLine = $countryCode." >> ".date('Y-m-d H:i:s') ." ". $debugLine.PHP_EOL;

    if($isLocal){
        $directory = "/Applications/MAMP/htdocs/SH/archive/logs/";
    }else{
        /// ne fonctionne pas, le script ne peut écrire que dans "httpdocs"
        $directory = "/var/www/vhosts/".$_SERVER['HTTP_HOST']."/crawler-log/";
    }
    
    file_put_contents($directory.$filename, $debugLine, FILE_APPEND);
}

// NORMALIZE THE SUB DOMAIN 
function normalizeRootDomain($url, $rootDomain, $SERVER_Protocol, $base) {
    // On découpe l'URL
    $parts = parse_url($rootDomain);

    // Host nu (sans www)
    $host = $parts['host'] ?? $rootDomain;
    $host = preg_replace('#^www\.#i', '', $host);

    // Ajouter le port si présent
    if (isset($parts['port'])) {
        $host .= ':' . $parts['port'];
    }

    $host = $SERVER_Protocol."://".$url.".".$host.$base;

    return $host;
}


/* subdomain internal linking */
function findSubdomainKeywordsByKeyword(string $keyword, $dbo, $maxResults = 5): array
{

    // Sécurité basique
    $keyword = trim($keyword);
    if ($keyword === '') {
        return [];
    }

    // 1) FULLTEXT SEARCH
    $sqlFulltext = "
        SELECT 
            *,
            MATCH(keyword_name) AGAINST (? IN NATURAL LANGUAGE MODE) AS score
        FROM subdomain_keywords
        WHERE MATCH(keyword_name) AGAINST (? IN NATURAL LANGUAGE MODE)
        AND keyword_name <> ?
        AND active=1
        ORDER BY score DESC
        LIMIT ".$maxResults;

    $stmt = $dbo->prepare($sqlFulltext);
    $stmt->execute([$keyword, $keyword, $keyword]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        return $results;
    }

    // 2) Fallback : même première lettre
    $firstLetter = mb_substr($keyword, 0, 1, 'UTF-8');
    if ($firstLetter === false || $firstLetter === '') {
        return [];
    }

    $sqlFallback = "
        SELECT *
        FROM subdomain_keywords
        WHERE keyword_name LIKE :prefix
        AND keyword_name <> :kwname
        ORDER BY keyword_name ASC
        LIMIT ".$maxResults;

    $stmt2 = $dbo->prepare($sqlFallback);
    $stmt2->execute([
        ':prefix' => $firstLetter . '%',
        ':kwname' => $keyword,
    ]);

    $fallbackResults = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    return $fallbackResults ?: [];
    
}



/**
 * Récupère des ads "random" sans ORDER BY RAND() sur 1M+ lignes.
 * Hypothèse: table `ads` a un champ `id` numérique indexé (PK auto-incrément).
 *
 * @param PDO   $dbo
 * @param int   $limit
 * @param array $where  Conditions simples: [ 'status' => 'active', ... ] (optionnel)
 * @param array $fields Champs à retourner (par défaut: id, photo)
 * @return array
 */
function getRandomAdsDistinctKeyword(
    PDO $dbo,
    int $limit = 60,
    array $where = [],
    array $fields = ['id', 'keyword_id', 'photo', 'title_orignal', 'url'],
    int $oversample = 400
): array {
    $limit = max(1, min($limit, 200));
    $oversample = max($limit, min($oversample, 2000)); // garde-fou

    // MAX(id)
    $stmt = $dbo->prepare("SELECT MAX(id) AS max_id FROM ads");
    $stmt->execute();
    $maxId = (int)($stmt->fetch(PDO::FETCH_ASSOC)['max_id'] ?? 0);
    if ($maxId <= 0) return [];

    $start = random_int(1, $maxId);

    // Champs safe
    $safeFields = [];
    foreach ($fields as $f) {
        if (preg_match('/^[a-zA-Z0-9_]+$/', (string)$f)) {
            $safeFields[] = "`$f`";
        }
    }
    if (!$safeFields) $safeFields = ['`id`','`keyword_id`','`photo`','`title_original`', '`url`'];
    $select = implode(", ", $safeFields);

    // 1er batch (>= start)
    $sql1 = "SELECT $select
             FROM ads
             WHERE id >= :start 
             ORDER BY id
             LIMIT $oversample";
    $stmt1 = $dbo->prepare($sql1);
    $stmt1->execute(['start' => $start]);
    $rows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // compléter si pas assez (wrap)
    if (count($rows) < $oversample) {
        $missing = $oversample - count($rows);
        $sql2 = "SELECT $select
                 FROM ads
                 WHERE id < :start 
                 ORDER BY id
                 LIMIT $missing";
        $stmt2 = $dbo->prepare($sql2);
        $stmt2->execute(['start' => $start]);
        $rows = array_merge($rows, $stmt2->fetchAll(PDO::FETCH_ASSOC));
    }

    // shuffle pour casser l'effet "suit id"
    shuffle($rows);

    // dédup keyword_id
    $out = [];
    $seen = [];
    foreach ($rows as $r) {
        /*if (empty($r['photo'])) continue;
        $kid = $r['keyword_id'] ?? null;
        if ($kid === null || $kid === '') continue;
        if (isset($seen[$kid])) continue;

        $seen[$kid] = true;*/
        $out[] = $r;
        if (count($out) >= $limit) break;
    }

    return $rows;
}

function truncateText(string $s, int $max = 30): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s));
    if (mb_strlen($s, 'UTF-8') <= $max) return $s;
    return mb_substr($s, 0, $max, 'UTF-8') . '…';
}

/**
 * Affiche un carrousel d'annonces à partir d'un tableau MySQL
 *
 * @param array $ads
 */
function renderAdsCarousel(array $ads, string $carouselId): void
{
    global $label_checkitnow;

    $ads = array_values(array_filter($ads, fn($a) => !empty($a['photo'])));
    if (!$ads) return;

    $safeId = preg_replace('/[^a-zA-Z0-9\-_]/', '', $carouselId);

    ?>
    <style>
      #<?= htmlspecialchars($safeId) ?> .carousel-track { --gap: 12px; --cols: 2; gap: var(--gap); }
      @media (min-width: 1024px) { #<?= htmlspecialchars($safeId) ?> .carousel-track { --cols: 6; } }
      #<?= htmlspecialchars($safeId) ?> .carousel-item {
        width: min(225px, calc((100% - (var(--cols) - 1) * var(--gap)) / var(--cols)));
      }
    </style>

    <section id="<?= htmlspecialchars($safeId) ?>" class="w-full">
      <div class="relative">

        <button type="button"
                class="carousel-prev hidden sm:flex absolute left-0 top-1/2 -translate-y-1/2 z-10
                       h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow
                       border border-gray-200 hover:bg-white"
                aria-label="Previous">
          <span class="text-xl leading-none">‹</span>
        </button>

        <button type="button"
                class="carousel-next hidden sm:flex absolute right-0 top-1/2 -translate-y-1/2 z-10
                       h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow
                       border border-gray-200 hover:bg-white"
                aria-label="Next">
          <span class="text-xl leading-none">›</span>
        </button>

        <div class="overflow-hidden">
          <div class="carousel-track flex scroll-smooth px-10 sm:px-12"
               data-carousel-track>
            <?php foreach ($ads as $ad): ?>
              <?php
                $title = (string)($ad['title_original'] ?? '');
                $titleShort = truncateText($title, 30);
                $adlink = base64_encode($ad['url']);
              ?>
              <a href="#" data-url="<?=$adlink;?>" target="_blank"
                 class="carousel-item clickable-product flex-shrink-0"
                 data-url="">
                <div class="group w-full">
                  <div class="relative overflow-hidden rounded-xl bg-gray-100 border border-gray-200">
                    <img
                      src="<?= htmlspecialchars($ad['photo'], ENT_QUOTES) ?>"
                      alt="<?= htmlspecialchars($titleShort, ENT_QUOTES) ?>"
                      loading="lazy"
                      class="w-full h-28 sm:h-32 lg:h-32 object-cover transition-transform duration-300 group-hover:scale-105"
                      style="max-width:225px;"
                    >
                  </div>

                  <p class="font-serif mt-2 text-xl text-gray-800 leading-tight min-h-24"
                     title="<?= htmlspecialchars($title, ENT_QUOTES) ?>">
                    <?= htmlspecialchars($titleShort, ENT_QUOTES) ?>                    
                  </p>
                  <div class="mt-6 text-lg font-semibold text-blue-500 group-hover:underline underline-offset-4">
                        <?=$label_checkitnow;?> →
                  </div>                    
                                  
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="carousel-dots mt-3 flex items-center justify-center gap-2"></div>
      </div>
    </section>

    <script>
      (function () {
        const root = document.getElementById(<?= json_encode($safeId) ?>);
        if (!root) return;

        const track = root.querySelector("[data-carousel-track]");
        const prevBtn = root.querySelector(".carousel-prev");
        const nextBtn = root.querySelector(".carousel-next");
        const dotsWrap = root.querySelector(".carousel-dots");
        const items = Array.from(track.querySelectorAll(".carousel-item"));
        if (!track || items.length === 0) return;

        let index = 0;
        let itemsPerView = 2;
        let pageCount = 1;

        function compute() {
          itemsPerView = window.matchMedia("(min-width: 1024px)").matches ? 6 : 2;
          pageCount = Math.max(1, Math.ceil(items.length / itemsPerView));
          index = Math.min(index, pageCount - 1);
          renderDots();
          sync(false);
        }

        function pageStartItem(i) {
          const start = i * itemsPerView;
          return items[Math.min(start, items.length - 1)];
        }

        function renderDots() {
          dotsWrap.innerHTML = "";
          for (let i = 0; i < pageCount; i++) {
            const b = document.createElement("button");
            b.type = "button";
            b.className =
              "h-2.5 w-2.5 rounded-full border border-gray-300 transition " +
              (i === index ? "bg-gray-700" : "bg-gray-200 hover:bg-gray-300");
            b.setAttribute("aria-label", "Go to slide " + (i + 1));
            b.addEventListener("click", () => { index = i; sync(true); });
            dotsWrap.appendChild(b);
          }
        }

        function sync(smooth) {
          const target = pageStartItem(index);
          if (target) {
            target.scrollIntoView({ behavior: smooth ? "smooth" : "auto", inline: "start", block: "nearest" });
          }

          const dots = Array.from(dotsWrap.children);
          dots.forEach((d, i) => {
            d.classList.toggle("bg-gray-700", i === index);
            d.classList.toggle("bg-gray-200", i !== index);
          });

          if (prevBtn) {
            prevBtn.disabled = (index === 0);
            prevBtn.classList.toggle("opacity-40", index === 0);
          }
          if (nextBtn) {
            nextBtn.disabled = (index >= pageCount - 1);
            nextBtn.classList.toggle("opacity-40", index >= pageCount - 1);
          }
        }

        function next() { index = Math.min(pageCount - 1, index + 1); sync(true); }
        function prev() { index = Math.max(0, index - 1); sync(true); }

        if (prevBtn) prevBtn.addEventListener("click", prev);
        if (nextBtn) nextBtn.addEventListener("click", next);

        // Si l'utilisateur scrolle à la main, on approx la page courante
        let raf = null;
        track.addEventListener("scroll", () => {
          if (raf) cancelAnimationFrame(raf);
          raf = requestAnimationFrame(() => {
            const left = track.scrollLeft;
            // trouve l'item le plus proche du bord gauche
            let best = 0, bestDist = Infinity;
            for (let i = 0; i < items.length; i++) {
              const dist = Math.abs(items[i].offsetLeft - left);
              if (dist < bestDist) { bestDist = dist; best = i; }
            }
            const newIndex = Math.min(pageCount - 1, Math.floor(best / itemsPerView));
            if (newIndex !== index) { index = newIndex; sync(false); }
          });
        }, { passive: true });

        window.addEventListener("resize", compute);
        compute();
      })();
    </script>
    <?php
}

// Split the homepage ads
function splitAdsIntoBlocks(array $ads, int $blocks = 3): array
{
    $blocks = max(1, $blocks);
    $perBlock = (int)ceil(count($ads) / $blocks);
    $chunks = array_chunk($ads, $perBlock);

    // garantit exactement $blocks tableaux (même si vides)
    while (count($chunks) < $blocks) $chunks[] = [];

    return array_slice($chunks, 0, $blocks);
}

?>

<?php
/**
 * recover-api.php — API interne pour fetch_commoncrawl.php
 *
 * Le script CLI ne peut pas utiliser PDO (driver absent en PHP CLI sur certains serveurs).
 * Ce fichier tourne côté web (PHP Apache/FPM avec PDO MySQL) et expose les opérations DB.
 *
 * Sécurité : token partagé via header X-Api-Token (valeur = RECOVER_API_TOKEN dans .env)
 * Usage interne uniquement (appelé depuis localhost par le script CLI).
 */

// ── Auth ──────────────────────────────────────────────────────────────────────

$rootDir = dirname(__DIR__);
$_envFile = $rootDir . '/../.env';
if (!file_exists($_envFile)) $_envFile = $rootDir . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line[0] === '#' || !str_contains($_line, '=')) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k); $_v = trim($_v);
        if (!getenv($_k)) putenv("{$_k}={$_v}");
    }
}

$expectedToken = getenv('RECOVER_API_TOKEN') ?: '';
$sentToken     = $_SERVER['HTTP_X_API_TOKEN'] ?? '';

if (!$expectedToken || !hash_equals($expectedToken, $sentToken)) {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

// ── DB ────────────────────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
        . ';port='    . (getenv('DB_PORT') ?: '3306')
        . ';dbname='  . (getenv('DB_NAME') ?: 'CONTENT')
        . ';charset=utf8mb4',
        getenv('DB_USER') ?: '',
        getenv('DB_PASS') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
}

// ── Router ────────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // GET ?action=get_site&site_id=N
    case 'get_site':
        $siteId = (int)($_GET['site_id'] ?? 0);
        if (!$siteId) { http_response_code(400); echo json_encode(['error' => 'site_id required']); break; }
        $stmt = $pdo->prepare('SELECT * FROM recovered_sites WHERE id = ? LIMIT 1');
        $stmt->execute([$siteId]);
        $site = $stmt->fetch();
        echo json_encode($site ?: null);
        break;

    // GET ?action=get_site_by_domain&domain=example.com
    case 'get_site_by_domain':
        $domain = trim($_GET['domain'] ?? '');
        if (!$domain) { http_response_code(400); echo json_encode(['error' => 'domain required']); break; }
        $stmt = $pdo->prepare('SELECT * FROM recovered_sites WHERE domain = ? AND status = "active" LIMIT 1');
        $stmt->execute([$domain]);
        $site = $stmt->fetch();
        echo json_encode($site ?: null);
        break;

    // POST ?action=insert_urls  body: {"site_id":1,"urls":[{"path":"/foo","slug":"foo","title":"Foo"},...]}
    case 'insert_urls':
        $body = json_decode(file_get_contents('php://input'), true);
        $siteId = (int)($body['site_id'] ?? 0);
        $urls   = $body['urls'] ?? [];
        if (!$siteId || !is_array($urls)) { http_response_code(400); echo json_encode(['error' => 'site_id + urls required']); break; }

        $stmt = $pdo->prepare('INSERT IGNORE INTO recovered_pages (site_id, original_path, slug, title, status) VALUES (?, ?, ?, ?, "pending")');
        $inserted = 0; $skipped = 0;
        foreach ($urls as $u) {
            try {
                $stmt->execute([$siteId, $u['path'], $u['slug'], $u['title']]);
                $stmt->rowCount() > 0 ? $inserted++ : $skipped++;
            } catch (PDOException $e) {
                $skipped++;
            }
        }
        echo json_encode(['inserted' => $inserted, 'skipped' => $skipped]);
        break;

    // POST ?action=update_crawled&site_id=N
    case 'update_crawled':
        $siteId = (int)($_GET['site_id'] ?? $_POST['site_id'] ?? 0);
        if (!$siteId) { http_response_code(400); echo json_encode(['error' => 'site_id required']); break; }
        $pdo->prepare('UPDATE recovered_sites SET crawled_at = NOW() WHERE id = ?')->execute([$siteId]);
        echo json_encode(['ok' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}

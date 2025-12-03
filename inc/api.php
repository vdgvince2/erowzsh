<?php
/* 
    API to manage the contact messages and the alerts
    another script reads from the API. 
*/

// CORS
$allowedOrigins = [
    'http://localhost:8888',            // ton environnement de dev
    'https://www.site-annonce.be',
    'https://www.for-sale.ie',
    'https://www.for-sale.co.uk',
    'https://www.used.forsale',
    'https://www.gebraucht-kaufen.de',
    'https://www.in-vendita.it',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}

// Préflight OPTIONS (utile si un jour tu ajoutes des headers custom)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit;
}


require __DIR__ . '/config.php'; 
require __DIR__ . '/functions.php'; 


// ===========================
// CONFIG
// ===========================
const API_TOKEN = 'FJKWrRptk7vOEv4jxuxvWQqJif26RIHN';

// ===========================
// FONCTIONS UTILES
// ===========================
function json_response(int $statusCode, array $payload): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ===========================
// SÉCURITÉ : TOKEN
// ===========================
$token = $_GET['token'] ?? '';

if ($token !== API_TOKEN) {
    json_response(401, [
        'status' => 'error',
        'error'  => 'Unauthorized',
    ]);
}

// ===========================
// ROUTAGE SIMPLE
// ===========================
$resource = $_GET['resource'] ?? 'search_alerts';

// Tu peux ajuster ici les ressources disponibles
$allowedResources = [
    'search_alerts' => [
        'table'    => 'search_alerts',
        'order_by' => 'created_at',
    ],
    'contact_messages' => [
        'table'    => 'contact_messages',
        'order_by' => 'created_at',
    ],
];

if (!isset($allowedResources[$resource])) {
    json_response(404, [
        'status' => 'error',
        'error'  => 'Unknown resource',
    ]);
}

// ===========================
// PARAM LIMIT SIMPLE
// ===========================
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
if ($limit <= 0 || $limit > 1000) {
    $limit = 100;
}

$meta  = $allowedResources[$resource];
$table = $meta['table'];
$order = $meta['order_by'];

// ===========================
// REQUÊTE DB
// ===========================
try {
    // $pdo doit exister (créé dans ton config.php par ex.)
    global $pdo;

    $sql = "SELECT * FROM `$table` ORDER BY `$order` DESC LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response(200, [
        'status'  => 'ok',
        'resource'=> $resource,
        'count'   => count($rows),
        'data'    => $rows,
    ]);

} catch (Throwable $e) {
    json_response(500, [
        'status' => 'error',
        'error'  => 'Server error',
        // Décommente en debug si tu veux le détail :
        // 'details' => $e->getMessage(),
    ]);
}

<?php

/* script that records if a page has been visited

*/

require __DIR__ . '/config.php'; 
require __DIR__ . '/functions.php'; 

// Basic check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "server not allowed";
    http_response_code(405); // Method Not Allowed
    exit;
}

// Anti-abus minimal (en plus de la signature)
if (empty($_POST['p']) || empty($_POST['s'])) {
    echo "abus";
    http_response_code(400);
    exit;
}


$payloadB64 = $_POST['p'];
$sig = $_POST['s'];

// Signature constante pour éviter timing attacks
$calc = hash_hmac('sha256', $payloadB64, VISITED_SECRET);
if (!hash_equals($calc, $sig)) {
    echo "signature error";
    http_response_code(403); // Forbidden
    exit;
}

// Parse payload
$payloadJson = base64_decode($payloadB64, true);
if ($payloadJson === false) {
    echo "payload";
    http_response_code(400);
    exit;
}
$data = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

$kid = isset($data['kid']) ? (int)$data['kid'] : 0;
$ts  = isset($data['ts'])  ? (int)$data['ts']  : 0;

// Mise à jour
try {
    
    //echo "update db";
    $upd = $pdo->prepare("
        UPDATE keywords
        SET last_visited = NOW()
        WHERE id = :id
        LIMIT 1
    ");
    $upd->execute([':id' => $kid]);

    // Réponse minimale
    //echo "minimal answer";
    http_response_code(204); // No Content
    exit;

} catch (Throwable $e) {
    // Pas de bruit en prod
    echo "error payload db";
    http_response_code(204);
    exit;
}
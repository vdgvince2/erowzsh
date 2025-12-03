<?php
// Config
$VERIFICATION_TOKEN = getenv('EBAY_VERIFICATION_TOKEN') ?: 'f9b1a7d6c24e4b5f9832a1d0c7e6b3a49f52d8c6e1a4b7c2d9e0f3a6b5c4d2e1';
$ENDPOINT_URL       = getenv('EBAY_ENDPOINT_URL') ?: 'https://www.erowz.com/ebay_notify.php';

// 1) Challenge (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['challenge_code'])) {
    $challengeCode = $_GET['challenge_code'];
    // SHA-256(challengeCode + verificationToken + endpoint) -> hex
    $hash = hash_init('sha256');
    hash_update($hash, $challengeCode);
    hash_update($hash, $VERIFICATION_TOKEN);
    hash_update($hash, $ENDPOINT_URL);
    $challengeResponse = hash_final($hash);

    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode(['challengeResponse' => $challengeResponse]);
    exit;
}

// 2) Notifications (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (Optionnel) Vérifier la signature X-EBAY-SIGNATURE -> voir SDK PHP eBay
    // https://github.com/eBay/event-notification-php-sdk

    $payload = file_get_contents('php://input');
    $headers = getallheaders();
    // Log minimal
    error_log('[eBay Notify] headers='.json_encode($headers).' body='.$payload);

    // TODO: pousser $payload en queue/job et ack rapidement
    http_response_code(204);
    exit;
}

// Sinon
http_response_code(405);
echo "Method Not Allowed";

<?php
// IP whitelist commune à tous les scripts d'admin
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, ['127.0.0.1', '::1', '80.200.197.242'], true)) {
    http_response_code(403);
    die('Accès refusé.');
}

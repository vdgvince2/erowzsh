<?php

/* purge cloudflare 






curl "https://api.cloudflare.com/client/v4/user/tokens/verify" \
-H "Authorization: Bearer TXF3p9Mj_9TTgb4__4Gd_iSXQ0iiNlRpaikBjVR_"


*/





function cloudflare_purge_files(array $files) {
    $zoneId = 'dd7c1873c76fd2192b5df2a270652e31';
    $apiToken = 'TXF3p9Mj_9TTgb4__4Gd_iSXQ0iiNlRpaikBjVR_';

    $ch = curl_init("https://api.cloudflare.com/client/v4/zones/$zoneId/purge_cache");

    $payload = json_encode(['files' => $files]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => $payload,
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new \Exception('Erreur cURL : ' . curl_error($ch));
    }
    curl_close($ch);

    return json_decode($response, true);
}

// Exemple d’appel :

/* boucle sur les zone ID et sur les fichiers */
cloudflare_purge_files([
    'https://www.tonsite.com/assets/css/style.css',
    'https://www.tonsite.com/assets/js/app.js'
]);

<?php
/**
 * Récupération du Transaction Detail Report (TDR) eBay Partner Network (Sales)
 * Doc : "EPN Transaction Detail Report (TDR) API Documentation"
 */

// =========================
// CONFIG
// =========================
const EPN_ACCOUNT_SID = 'IRmqA92pRPpr2694281zMD6TFQ8QbB5Kz1';
const EPN_AUTH_TOKEN  = 'zcEZB.fiZhCRmW-ssfoX6AiiksbhLh7R';

// Endpoint Sales
// ebay_partner_transaction_detail_registration.json pour les registrations
const EPN_TDR_SALES_ENDPOINT = 'https://%s:%s@api.partner.ebay.com/Mediapartners/%s/Reports/ebay_partner_transaction_detail.json';

// =========================
// HTTP CALL
// =========================

/**
 * Appelle une URL TDR et renvoie le JSON décodé
 *
 * @param string $url
 * @return array
 * @throws Exception
 */
function epnHttpGet(string $url): array
{

    echo $url;
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Erreur cURL : ' . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('HTTP ' . $httpCode . ' : ' . $response);
    }

    $data = json_decode($response, true);

    if ($data === null) {
        throw new Exception('JSON invalide : ' . substr($response, 0, 500));
    }

    return $data;
}

/**
 * Construit l’URL TDR Sales avec les paramètres (première page).
 *
 * @param array $params
 * @return string
 */
function buildEpnTdrSalesUrl(array $params): string
{
    $base = sprintf(
        EPN_TDR_SALES_ENDPOINT,
        rawurlencode(EPN_ACCOUNT_SID),
        rawurlencode(EPN_AUTH_TOKEN),
        rawurlencode(EPN_ACCOUNT_SID)
    );

    // Valeurs par défaut recommandées dans la doc
    $defaults = [
        'DATE_TYPE'       => 'update_date', // ou event_date
        'STATUS'          => 'ALL',         // doc mélange "all" / "ALL", reste cohérent dans ton code
        'VERTICAL_CATEGORY' => 0,
        'CHECKOUT_SITE'   => 0,
        'BUYER_COUNTRY'   => 0,
        'CAMPAIGN_SOLR'   => 0,
        // ATTENTION : START_DATE / END_DATE OBLIGATOIRES → passés dans $params
        'timeRange'       => 'CUSTOM',
        'compareEnabled'  => 'false',
        'PageSize'        => 20000,        // min & par défaut
    ];

    $query = http_build_query(array_merge($defaults, $params));
    return $base . '?' . $query;
}

/**
 * Récupère toutes les transactions Sales sur une plage de dates,
 * en suivant la pagination (@nextpageuri), jusqu’à 10 pages max.
 *
 * @param string $startDate  YYYY-MM-DD
 * @param string $endDate    YYYY-MM-DD
 * @param array  $extraParams éventuels paramètres supplémentaires
 * @return array  liste de toutes les Records
 * @throws Exception
 */
function fetchEpnTdrSales(string $startDate, string $endDate, array $extraParams = []): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        throw new InvalidArgumentException('Format de date invalide, attendu YYYY-MM-DD');
    }

    $params = array_merge($extraParams, [
        'START_DATE' => $startDate,
        'END_DATE'   => $endDate,
    ]);

    $url = buildEpnTdrSalesUrl($params);

    $allRecords = [];
    $pageCount  = 0;

    while ($url && $pageCount < 10) { // la doc dit max 10 pages
        $pageCount++;

        $data = epnHttpGet($url);

        if (!empty($data['Records']) && is_array($data['Records'])) {
            $allRecords = array_merge($allRecords, $data['Records']);
        }

        // La doc indique @nextpageuri pour la pagination
        if (!empty($data['@nextpageuri'])) {
            // @nextpageuri ressemble normalement à un URI complet sans credentials.
            // On réinjecte les credentials via sprintf sur la base.
            $nextUri = $data['@nextpageuri'];

            // On ne fait confiance qu’au path+query de api.partner.ebay.com
            $parts = parse_url($nextUri);
            if (!empty($parts['path'])) {
                $baseWithCreds = sprintf(
                    'https://%s:%s@api.partner.ebay.com',
                    rawurlencode(EPN_ACCOUNT_SID),
                    rawurlencode(EPN_AUTH_TOKEN)
                );
                $url = $baseWithCreds . $parts['path']
                     . (isset($parts['query']) ? '?' . $parts['query'] : '');
            } else {
                // si pour une raison obscure le format change, on sort
                $url = null;
            }
        } else {
            $url = null;
        }
    }

    return $allRecords;
}

// =========================
// EXEMPLE D’UTILISATION
// =========================

try {
    // Exemple : transactions du 2025-11-01 au 2025-11-01
    $startDate = '2025-11-01';
    $endDate   = '2025-11-01';

    // Tu peux passer des paramètres supplémentaires ici (vertical, campagne, etc.)
    $extraParams = [
        // 'VERTICAL_CATEGORY' => 'Fashion',
        // 'CHECKOUT_SITE'     => 'FR',
        // 'BUYER_COUNTRY'     => 'FR',
        // 'CAMPAIGN_SOLR'     => 123456789,
    ];

    $records = fetchEpnTdrSales($startDate, $endDate, $extraParams);

    // Pour debug : on dump le JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}

<?php
/**
 * Configs pays intégrées — fallback quand sites.json ne contient pas les clés de base.
 * Source unique partagée par routing.php et config.php.
 */
return [
    'IE' => ['country'=>'IE','language'=>'EN','currency'=>'€','currency_schema'=>'EUR','country_label'=>'Ireland','ebay_marketplace'=>'EBAY_IE','ebay_mkrid'=>'710-53481-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>205,'ebay_root_url'=>'https://www.ebay.ie'],
    'GB' => ['country'=>'GB','language'=>'EN','currency'=>'£','currency_schema'=>'GBP','country_label'=>'United Kingdom','ebay_marketplace'=>'EBAY_GB','ebay_mkrid'=>'710-53481-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>3,'ebay_root_url'=>'https://www.ebay.co.uk'],
    'US' => ['country'=>'US','language'=>'EN','currency'=>'$','currency_schema'=>'USD','country_label'=>'United States','ebay_marketplace'=>'EBAY_US','ebay_mkrid'=>'711-53200-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>0,'ebay_root_url'=>'https://www.ebay.com'],
    'FR' => ['country'=>'FR','language'=>'FR','currency'=>'€','currency_schema'=>'EUR','country_label'=>'France','ebay_marketplace'=>'EBAY_FR','ebay_mkrid'=>'709-53476-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>71,'ebay_root_url'=>'https://www.ebay.fr'],
    'DE' => ['country'=>'DE','language'=>'DE','currency'=>'€','currency_schema'=>'EUR','country_label'=>'Deutschland','ebay_marketplace'=>'EBAY_DE','ebay_mkrid'=>'707-53477-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>77,'ebay_root_url'=>'https://www.ebay.de'],
    'IT' => ['country'=>'IT','language'=>'IT','currency'=>'€','currency_schema'=>'EUR','country_label'=>'Italia','ebay_marketplace'=>'EBAY_IT','ebay_mkrid'=>'724-53478-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>101,'ebay_root_url'=>'https://www.ebay.it'],
    'BE' => ['country'=>'BE','language'=>'FR','currency'=>'€','currency_schema'=>'EUR','country_label'=>'Belgique','ebay_marketplace'=>'EBAY_BE','ebay_mkrid'=>'710-53481-19255-0','ebay_campid'=>'5339107427','ebay_siteid'=>3,'ebay_root_url'=>'https://www.ebay.be'],
];

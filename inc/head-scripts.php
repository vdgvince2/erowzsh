<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$pageTitle;?></title>
    <meta name="description" content="<?=$pageTitle." "; if(isset($additionnalMetaDesc)) echo $additionnalMetaDesc;?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?=$rootDomain.$base;?>favicon.ico"> 
    <meta name="google-site-verification" content="7Pw3UtAoJp2P_V9zSeV3LhHz7NlX0BQM-OY9337K8M8" />
    
    <?php if (!$isLocal && !isset($noAds)) { ?>
        <script type="text/javascript" src="https://cache.consentframework.com/js/pa/21931/c/3anGX/stub" referrerpolicy="unsafe-url" charset="utf-8" async></script>
        <script type="text/javascript" src="https://choices.consentframework.com/js/pa/21931/c/3anGX/cmp" referrerpolicy="unsafe-url" charset="utf-8" async></script>
        <script type="text/javascript" src="https://a.rltd.net/tags/ezsa.js" async></script>
        <?=$googleadsenseHead;?>   
     
    <?php } 
    
    // Specific hotjar for UK
    if($countryCode == "GB" && !$isLocal){
        /*
        ?>
            <!-- Hotjar Tracking Code for SH -->
            <script>
                (function(h,o,t,j,a,r){
                    h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
                    h._hjSettings={hjid:6568070,hjsv:6};
                    a=o.getElementsByTagName('head')[0];
                    r=o.createElement('script');r.async=1;
                    r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
                    a.appendChild(r);
                })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
            </script>        
        <?php
        */
    }

    // Analytics Tracker : simpleAnalytic or Umami    
    if (!$isLocal) {
        echo AnalyticsTracker($analyticsHead, $umami_website_id);    
    }
    
    ?>
</head>
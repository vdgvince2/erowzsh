   <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 mt-8">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                <h3 class="font-bold mb-4"><?= htmlspecialchars($label_buy) ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= $rootDomain.$base;?>s/privacy" class="hover:text-blue-600"><?= htmlspecialchars($label_privacy) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/money-back" class="hover:text-blue-600"><?= htmlspecialchars($label_money_back) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/bidding-and-buying-help" class="hover:text-blue-600"><?= htmlspecialchars($label_bidding_help) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/stores" class="hover:text-blue-600"><?= htmlspecialchars($label_stores) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/cart" class="text-sm hover:text-blue-600"><?= htmlspecialchars($Header_Link_cart);?></a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-bold mb-4"><?= htmlspecialchars($label_sell) ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= $rootDomain.$base;?>s/start-selling" class="hover:text-blue-600"><?= htmlspecialchars($label_start_selling) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/learn-to-sell" class="hover:text-blue-600"><?= htmlspecialchars($label_learn_to_sell) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/business-sellers" class="hover:text-blue-600"><?= htmlspecialchars($label_business_sellers) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/seller-centre" class="hover:text-blue-600"><?= htmlspecialchars($label_seller_centre) ?></a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-bold mb-4"><?= htmlspecialchars($label_tools_apps) ?></h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="<?= $rootDomain.$base;?>s/developers" class="hover:text-blue-600"><?= htmlspecialchars($label_developers) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/security-centre" class="hover:text-blue-600"><?= htmlspecialchars($label_security_centre) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/site-map" class="hover:text-blue-600"><?= htmlspecialchars($label_site_map) ?></a></li>
                    <li><a href="<?= $rootDomain.$base;?>s/official-time" class="hover:text-blue-600"><?= htmlspecialchars($label_official_time) ?></a></li>
                    <li><?php if($countryCode =="US" or $isLocal){ ?><a href="<?= $rootDomain.$base;?>mag" class="hover:text-blue-600">Blog</a><?php } ?></li>
                </ul>
            </div>

            <div>
                <h3 class="font-bold mb-4"><?= htmlspecialchars($label_stay_connected) ?></h3>
                <div class="flex space-x-4 mb-4">
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?= $rootDomain.$base;?>s/contact" class="hover:text-blue-600"><?=$label_contact;?></a></li>
                        <li><a href="https://www.facebook.com/profile.php?id=61584598651411" class="hover:text-blue-600">Facebook</a></li>
                    </ul>
                </div>
            </div>

                </div>
            </div>
            <div class="border-t border-gray-200 mt-8 pt-8 text-sm text-gray-600 mx-auto max-w-7xl">
                <p> <?= htmlspecialchars($label_copyright) ?> 
                <?= htmlspecialchars($label_accessibility) ?>
                 </p>
                 <p><?= htmlspecialchars($label_affiliate);?></p>
            </div>
        </div>
    </footer>


    
    <script>
        // obfuscation
        document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.clickable-product').forEach(el => {
            el.addEventListener('click', () => {
            const encoded = el.dataset.url;
            try {
                const decoded = atob(encoded);
                if (decoded.startsWith('http')) {
                window.location.href = decoded;
                } else {
                console.warn('Invalid decoded URL:', decoded);
                }
            } catch (err) {
                console.error('Invalid base64 URL:', err);
            }
            });
        });
        });
       
      <?php if(isset($keywordId) && $lastVisit === false){ ?>
        // visit management
        (function () {
        const KID = <?= (int)$keywordId ?>;
        const KEY = 'kwv_' + KID;

        try {
            // 1) évite bots évidents et contextes automation
            if (navigator.webdriver) return;
            if (typeof document.visibilityState !== 'undefined' && document.visibilityState === 'hidden') return;

            // 3) fire & forget
            //if (!('sendBeacon' in navigator)) return;
            const fd = new FormData();
            fd.append('p', '<?= $payload ?>');
            fd.append('s', '<?= $sig ?>');
            console.log("visit recorded");

            // petit jitter pour éviter pattern robotique & laisser le temps au rendu
            const delay = 300 + Math.floor(Math.random() * 700);
            setTimeout(function () {
            navigator.sendBeacon('<?=$rootDomain.$base;?>inc/visited.php', fd);
            }, delay);

        } catch (e) {
           console.log("visit not recorded");
        }
        })();

      <?php } ?> 

    </script>

    <?php
    // Show Outpush script if on production
    
    if (!$isLocal && !$isEdge) {
        echo $outpush;
    }
    ?>
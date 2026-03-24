<?php
/**
 * inc/crawl-loading.php
 * Loading page shown when a keyword exists in DB but has no products yet.
 * Triggers an on-demand eBay crawl via JS fetch(), then reloads on success.
 *
 * Requires: $crawlKeywordId (int), $crawlKeywordName (string)
 * from product-category.php context.
 */
$noAds   = true;
$pageTitle = $label_crawling_title ?? 'Loading listings…';
$crawlUrl  = $rootDomain . $base . 'crawl-now.php?kid=' . (int)$crawlKeywordId;
$reloadUrl = $SERVER_PageFullURL;
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>" class="js">
<head>
  <?php require __DIR__ . '/head-scripts.php'; ?>
  <style>
    .crawl-wrap { max-width: 520px; margin: 6rem auto; background: #fff; padding: 2.5rem 2rem; border: 1px solid #e5e7eb; border-radius: 16px; text-align: center; }
    .spinner { width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top-color: #3b82f6; border-radius: 50%; animation: spin .9s linear infinite; margin: 0 auto 1.5rem; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .crawl-wrap h1 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .5rem; }
    .crawl-wrap p  { color: #6b7280; font-size: .95rem; margin: 0; }
    .crawl-msg-ok  { color: #16a34a; font-weight: 600; }
    .crawl-msg-err { color: #dc2626; font-weight: 600; }
  </style>
</head>
<body>
  <?php require __DIR__ . '/header.php'; ?>

  <div class="crawl-wrap">
    <div class="spinner" id="crawl-spinner"></div>
    <h1 id="crawl-title"><?= htmlspecialchars($label_crawling_title ?? 'Finding listings…', ENT_QUOTES) ?></h1>
    <p id="crawl-desc"><?= htmlspecialchars(
        str_replace('{keyword}', $crawlKeywordName, $label_crawling_desc ?? 'Searching eBay for {keyword}, this takes a few seconds…'),
        ENT_QUOTES
    ) ?></p>
  </div>

  <script>
    (function () {
      const url      = <?= json_encode($crawlUrl) ?>;
      const reload   = <?= json_encode($reloadUrl) ?>;
      const msgOk    = <?= json_encode($label_crawling_success  ?? 'Listings found! Loading…') ?>;
      const msgErr   = <?= json_encode($label_crawling_notfound ?? 'No listings found for this keyword.') ?>;

      fetch(url, { method: 'GET' })
        .then(r => r.json())
        .then(data => {
          document.getElementById('crawl-spinner').style.display = 'none';
          if (data.count && data.count > 0) {
            document.getElementById('crawl-title').textContent = msgOk;
            document.getElementById('crawl-title').className   = 'crawl-msg-ok';
            document.getElementById('crawl-desc').textContent  = '';
            setTimeout(() => { window.location.href = reload; }, 800);
          } else {
            document.getElementById('crawl-title').textContent = msgErr;
            document.getElementById('crawl-title').className   = 'crawl-msg-err';
            document.getElementById('crawl-desc').textContent  = '';
          }
        })
        .catch(() => {
          document.getElementById('crawl-spinner').style.display = 'none';
          document.getElementById('crawl-title').textContent = msgErr;
          document.getElementById('crawl-title').className   = 'crawl-msg-err';
        });
    })();
  </script>

  <?php require __DIR__ . '/footer.php'; ?>
</body>
</html>

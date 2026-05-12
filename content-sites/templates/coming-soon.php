<?php
/**
 * Page "coming soon" — sous-niche sans article publié.
 * Variables : $niche, $subNiche (peut être null), $nicheBaseUrl, $faviconUrl,
 *             $mainLanguage, $countryLabel, $ebayRootURL, $ebay_mkrid, $ebay_campid
 */
$snName        = $subNiche['name'] ?? 'Coming soon';
$ebaySearchUrl = ($ebayRootURL ?? 'https://www.ebay.co.uk')
    . '/sch/i.html?_nkw=' . urlencode($snName)
    . '&mkcid=1&mkrid=' . ($ebay_mkrid ?? '') . '&campid=' . ($ebay_campid ?? '') . '&mkevt=1';
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($snName) ?> — <?= htmlspecialchars($niche['name']) ?></title>
  <meta name="robots" content="noindex">
  <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>body{font-family:'Montserrat',sans-serif}</style>
</head>
<body class="bg-white text-gray-900">

<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-3">
      <nav class="flex items-center gap-1.5 text-[10px] uppercase tracking-widest text-gray-400 font-semibold">
        <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="hover:text-brand transition-colors"><?= htmlspecialchars($niche['name']) ?></a>
        <span>›</span>
        <span class="text-gray-600"><?= htmlspecialchars($snName) ?></span>
      </nav>
      <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="text-center">
        <span class="block tracking-[0.35em] uppercase text-gray-900 font-extrabold text-base leading-none"><?= htmlspecialchars(strtoupper($niche['name'])) ?></span>
        <span class="block text-[9px] tracking-[0.2em] text-gray-400 uppercase mt-0.5">Buying Guides &amp; Best Deals</span>
      </a>
      <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="text-[10px] font-bold tracking-widest uppercase text-brand hover:underline transition-colors">eBay →</a>
    </div>
  </div>
</header>

<main class="max-w-2xl mx-auto px-4 py-24 text-center">
  <span class="inline-block text-[10px] font-bold tracking-widest uppercase text-brand mb-6"><?= htmlspecialchars($niche['name']) ?></span>
  <h1 class="text-3xl font-extrabold uppercase tracking-wide text-gray-900 mb-4"><?= htmlspecialchars($snName) ?></h1>
  <p class="text-sm text-gray-500 leading-relaxed mb-10">
    Our expert buying guide for <strong><?= htmlspecialchars($snName) ?></strong> in <?= htmlspecialchars($countryLabel) ?> is being prepared.<br>
    Check back soon — or browse listings on eBay now.
  </p>
  <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
    <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
       class="bg-gray-900 text-white text-[11px] font-bold tracking-widest uppercase px-8 py-3 hover:bg-brand transition-colors">
      SHOP ON EBAY →
    </a>
    <a href="<?= htmlspecialchars($nicheBaseUrl) ?>"
       class="border border-gray-300 text-gray-600 text-[11px] font-bold tracking-widest uppercase px-8 py-3 hover:border-gray-900 hover:text-gray-900 transition-colors">
      ← ALL GUIDES
    </a>
  </div>
</main>

<footer class="bg-gray-900 text-gray-300 pt-12 pb-6 mt-8">
  <div class="max-w-7xl mx-auto px-4 text-center">
    <p class="text-xs text-gray-500">© <?= date('Y') ?> <?= htmlspecialchars($niche['name']) ?> — <?= htmlspecialchars($countryLabel) ?></p>
  </div>
</footer>
</body>
</html>

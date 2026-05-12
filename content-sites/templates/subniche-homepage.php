<?php
/**
 * Homepage sous-niche — articles de cette sous-niche + nav vers autres sous-niches.
 * Variables : $niche, $subNiche, $snArticles, $subNiches, $nicheBaseUrl, $faviconUrl,
 *             $countryLabel, $mainLanguage, $ebayRootURL, $ebay_mkrid, $ebay_campid
 */

$hero   = $snArticles[0] ?? null;
$second = $snArticles[1] ?? null;
$rest   = array_slice($snArticles, 2);
$noImg  = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%23e5e7eb"/><text x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="14" fill="%239ca3af">Coming soon</text></svg>';

$subNicheHomeUrl = cs_url($subNiche['slug']);
$ebaySearchUrl   = ($ebayRootURL ?? 'https://www.ebay.co.uk')
    . '/sch/i.html?_nkw=' . urlencode($subNiche['name'])
    . '&mkcid=1&mkrid=' . ($ebay_mkrid ?? '') . '&campid=' . ($ebay_campid ?? '') . '&mkevt=1';
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($subNiche['name']) ?> — <?= htmlspecialchars($niche['name']) ?> Buying Guides in <?= htmlspecialchars($countryLabel) ?></title>
  <meta name="description" content="Expert buying guides for <?= htmlspecialchars($subNiche['name']) ?> in <?= htmlspecialchars($countryLabel) ?>. Find the best eBay deals.">
  <link rel="canonical" href="<?= htmlspecialchars($subNicheHomeUrl) ?>">
  <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26','brand-dark':'#c94e1e'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>
    body{font-family:'Montserrat',sans-serif}
    .card-hover{transition:transform .2s,box-shadow .2s}.card-hover:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(0,0,0,.12)}
    .img-zoom{overflow:hidden}.img-zoom img{transition:transform .4s}.img-zoom:hover img{transform:scale(1.05)}
    .divider{display:flex;align-items:center;gap:16px}.divider::before,.divider::after{content:'';flex:1;height:1px;background:#e5e7eb}
    nav::-webkit-scrollbar{display:none}
  </style>
</head>
<body class="bg-white text-gray-900">

<!-- HEADER -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-3">
      <nav class="flex items-center gap-1.5 text-[10px] uppercase tracking-widest text-gray-400 font-semibold">
        <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="hover:text-brand transition-colors"><?= htmlspecialchars($niche['name']) ?></a>
        <span>›</span>
        <span class="text-gray-700"><?= htmlspecialchars($subNiche['name']) ?></span>
      </nav>
      <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="text-center">
        <span class="block tracking-[0.35em] uppercase text-gray-900 font-extrabold text-base leading-none"><?= htmlspecialchars(strtoupper($niche['name'])) ?></span>
        <span class="block text-[9px] tracking-[0.2em] text-gray-400 uppercase mt-0.5">Buying Guides &amp; Best Deals</span>
      </a>
      <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="text-[10px] font-bold tracking-widest uppercase text-brand hover:text-brand-dark transition-colors">eBay →</a>
    </div>
    <!-- Nav : toutes les sous-niches, active surlignée -->
    <nav class="overflow-x-auto border-t border-gray-100">
      <ul class="flex items-center justify-center gap-0 py-0 whitespace-nowrap min-w-max mx-auto">
        <?php foreach ($subNiches as $i => $sn): ?>
        <?php if ($i > 0): ?><li class="text-gray-200">·</li><?php endif; ?>
        <li><a href="<?= htmlspecialchars(cs_url($sn['slug'])) ?>"
               class="block px-3 py-2.5 text-[10px] tracking-widest uppercase font-semibold transition-colors
                      <?= $sn['slug'] === $subNiche['slug'] ? 'text-brand border-b-2 border-brand' : 'text-gray-700 hover:text-brand' ?>">
          <?= htmlspecialchars($sn['name']) ?>
        </a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<!-- MAIN -->
<main>
  <section class="max-w-7xl mx-auto px-4 pt-8 pb-4">
    <div class="divider mb-0">
      <h1 class="text-base font-bold tracking-[0.3em] uppercase text-gray-900 whitespace-nowrap px-4"><?= htmlspecialchars($subNiche['name']) ?></h1>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-4 py-6">
    <?php if ($hero): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <a href="<?= htmlspecialchars(cs_url($subNiche['slug'], $hero['slug'])) ?>"
         class="md:col-span-2 card-hover block group rounded-sm overflow-hidden relative">
        <div class="img-zoom w-full" style="height:380px">
          <img src="<?= htmlspecialchars($hero['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($hero['title']) ?>"
               class="w-full h-full object-cover" loading="eager">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent flex flex-col justify-end p-6">
          <span class="text-[10px] font-bold tracking-widest uppercase text-brand bg-white/90 px-2 py-0.5 rounded-sm inline-block mb-2 w-fit"><?= htmlspecialchars($subNiche['name']) ?></span>
          <h2 class="text-white font-bold text-lg leading-snug tracking-wide uppercase"><?= htmlspecialchars($hero['title']) ?></h2>
          <?php if ($hero['meta_description']): ?>
          <p class="text-white/70 text-xs mt-2 line-clamp-2"><?= htmlspecialchars($hero['meta_description']) ?></p>
          <?php endif; ?>
        </div>
      </a>
      <?php if ($second): ?>
      <a href="<?= htmlspecialchars(cs_url($subNiche['slug'], $second['slug'])) ?>"
         class="card-hover block group rounded-sm overflow-hidden">
        <div class="img-zoom" style="height:240px">
          <img src="<?= htmlspecialchars($second['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($second['title']) ?>"
               class="w-full h-full object-cover" loading="lazy">
        </div>
        <div class="p-4">
          <span class="text-[9px] font-bold tracking-widest uppercase text-brand"><?= htmlspecialchars($subNiche['name']) ?></span>
          <h3 class="mt-1 text-sm font-bold uppercase leading-snug text-gray-900 group-hover:text-brand transition-colors"><?= htmlspecialchars($second['title']) ?></h3>
        </div>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($rest)): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <?php foreach ($rest as $a): ?>
      <a href="<?= htmlspecialchars(cs_url($subNiche['slug'], $a['slug'])) ?>"
         class="card-hover block group rounded-sm overflow-hidden">
        <div class="img-zoom">
          <img src="<?= htmlspecialchars($a['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($a['title']) ?>"
               class="w-full object-cover" style="height:160px" loading="lazy">
        </div>
        <div class="p-3">
          <span class="text-[9px] font-bold tracking-widest uppercase text-brand"><?= htmlspecialchars($subNiche['name']) ?></span>
          <h3 class="mt-1 text-[12px] font-bold uppercase leading-snug text-gray-900 group-hover:text-brand transition-colors"><?= htmlspecialchars($a['title']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($snArticles)): ?>
    <div class="py-20 text-center">
      <p class="text-sm text-gray-500 mb-6">Our buying guides for <strong><?= htmlspecialchars($subNiche['name']) ?></strong> are being prepared.</p>
      <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="inline-block bg-gray-900 text-white text-[11px] font-bold tracking-widest uppercase px-8 py-3 hover:bg-brand transition-colors">
        BROWSE ON EBAY →
      </a>
    </div>
    <?php endif; ?>
  </section>

  <!-- Autres sous-niches -->
  <section class="max-w-7xl mx-auto px-4 pb-12">
    <div class="divider mb-6"><h2 class="text-sm font-bold tracking-[0.3em] uppercase text-gray-900 whitespace-nowrap px-4">Other categories</h2></div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
      <?php foreach ($subNiches as $sn): ?>
      <?php if ($sn['slug'] === $subNiche['slug']) continue; ?>
      <a href="<?= htmlspecialchars(cs_url($sn['slug'])) ?>"
         class="card-hover flex items-center justify-between border border-gray-200 rounded-sm px-4 py-3 hover:border-brand group">
        <span class="text-[11px] font-bold uppercase tracking-wide text-gray-700 group-hover:text-brand transition-colors"><?= htmlspecialchars($sn['name']) ?></span>
        <span class="text-brand text-xs">→</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
</main>

<!-- FOOTER -->
<footer class="bg-gray-900 text-gray-300 pt-12 pb-6 mt-8">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col items-center mb-10">
      <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="mb-3">
        <span class="block text-xs font-extrabold tracking-[0.35em] uppercase text-white"><?= htmlspecialchars(strtoupper($niche['name'])) ?></span>
      </a>
      <p class="text-sm text-gray-400 text-center max-w-xs">Expert buying guides &amp; best deals in <?= htmlspecialchars($countryLabel) ?></p>
    </div>
    <div class="border-t border-gray-700 pt-8 mb-8">
      <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
        <?php foreach ($subNiches as $sn): ?>
        <a href="<?= htmlspecialchars(cs_url($sn['slug'])) ?>"
           class="text-xs <?= $sn['slug'] === $subNiche['slug'] ? 'text-brand' : 'text-gray-400 hover:text-white' ?> transition-colors">
          <?= htmlspecialchars($sn['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="border-t border-gray-700 pt-6 text-center">
      <p class="text-xs text-gray-500">© <?= date('Y') ?> <?= htmlspecialchars($niche['name']) ?> — <?= htmlspecialchars($countryLabel) ?> &nbsp;—&nbsp; Affiliate disclosure: links may earn commission.</p>
    </div>
  </div>
</footer>
</body>
</html>

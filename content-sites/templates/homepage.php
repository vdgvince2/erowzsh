<?php
/**
 * Homepage niche — articles de toutes les sous-niches, liens vers sous-niches.
 * Variables : $niche, $articles, $subNiches, $nicheBaseUrl, $faviconUrl,
 *             $WebsiteName, $countryLabel, $mainLanguage,
 *             $ebayRootURL, $ebay_mkrid, $ebay_campid,
 *             $homepageContent (array|null),
 *             $eeatProfiles    (array),
 *             $galleryImages   (array)
 */

$hero     = $articles[0] ?? null;
$featured = array_slice($articles, 1, 3);
$rest     = array_slice($articles, 4);
$noImg    = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%23e5e7eb"/><text x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="14" fill="%239ca3af">Coming soon</text></svg>';
$ebayUrl  = ($ebayRootURL ?? 'https://www.ebay.co.uk') . '/sch/i.html?_nkw=' . urlencode($niche['name'])
          . '&mkcid=1&mkrid=' . ($ebay_mkrid ?? '') . '&campid=' . ($ebay_campid ?? '') . '&mkevt=1';

// Bio dans la bonne langue pour les EEAT
$bioLang = 'bio_' . strtolower($mainLanguage ?? 'en');
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($niche['name']) ?> Buying Guides — <?= htmlspecialchars($countryLabel) ?></title>
  <meta name="description" content="Expert buying guides for <?= htmlspecialchars($niche['name']) ?> in <?= htmlspecialchars($countryLabel) ?>. Find the best eBay deals.">
  <link rel="canonical" href="<?= htmlspecialchars($nicheBaseUrl) ?>">
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

    /* Editorial zones */
    .zone-content{font-size:.9rem;line-height:1.7;color:#374151}
    .zone-content h2,.zone-content h3{font-weight:700;color:#111;margin:1em 0 .5em;letter-spacing:-.01em}
    .zone-content p{margin-bottom:.85em}
    .zone-content ul,.zone-content ol{padding-left:1.25em;margin-bottom:.85em}
    .zone-content li{margin-bottom:.3em}
    .zone-content a{color:#e85d26;text-decoration:underline}

    /* Pinterest masonry */
    .pinterest-grid{columns:2;column-gap:12px}
    @media(min-width:640px){.pinterest-grid{columns:3}}
    @media(min-width:1024px){.pinterest-grid{columns:5}}
    .pinterest-item{break-inside:avoid;margin-bottom:12px;display:block;overflow:hidden;border-radius:6px;position:relative}
    .pinterest-item img{width:100%;display:block;transition:transform .4s}
    .pinterest-item:hover img{transform:scale(1.04)}
    .pinterest-item .overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.5) 0%,transparent 50%);opacity:0;transition:opacity .3s}
    .pinterest-item:hover .overlay{opacity:1}
    .pinterest-item .overlay-text{position:absolute;bottom:8px;left:8px;right:8px;color:#fff;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:0;transition:opacity .3s}
    .pinterest-item:hover .overlay-text{opacity:1}

    /* EEAT compact cards */
    .eeat-card{transition:border-color .2s,box-shadow .2s}
    .eeat-card:hover{border-color:#e85d26;box-shadow:0 4px 16px rgba(232,93,38,.1)}
  </style>
  <script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$niche['name'].' Buying Guides','url'=>$nicheBaseUrl],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="bg-white text-gray-900">

<!-- HEADER -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-3">
      <span class="text-[10px] tracking-widest uppercase font-semibold text-gray-400"><?= htmlspecialchars($countryLabel) ?></span>
      <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="text-center">
        <span class="block tracking-[0.35em] uppercase text-gray-900 font-extrabold text-lg leading-none"><?= htmlspecialchars(strtoupper($niche['name'])) ?></span>
        <span class="block text-[9px] tracking-[0.2em] text-gray-400 uppercase mt-0.5">Buying Guides &amp; Best Deals</span>
      </a>
      <a href="<?= htmlspecialchars($ebayUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="text-[10px] font-bold tracking-widest uppercase text-brand hover:text-brand-dark transition-colors">eBay →</a>
    </div>
    <nav class="overflow-x-auto border-t border-gray-100">
      <ul class="flex items-center justify-center gap-0 py-0 whitespace-nowrap min-w-max mx-auto">
        <?php foreach ($subNiches as $i => $sn): ?>
        <?php if ($i > 0): ?><li class="text-gray-200">·</li><?php endif; ?>
        <li><a href="<?= htmlspecialchars(cs_url($sn['slug'])) ?>"
               class="block px-3 py-2.5 text-[10px] tracking-widest uppercase text-gray-700 hover:text-brand transition-colors font-semibold">
          <?= htmlspecialchars($sn['name']) ?>
        </a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>

<!-- MAIN -->
<main>

  <!-- ── Articles grid ──────────────────────────────────────────────────────── -->
  <section class="max-w-7xl mx-auto px-4 py-8">
    <?php if ($hero): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <a href="<?= htmlspecialchars(cs_url($hero['sub_niche_slug'], $hero['slug'])) ?>"
         class="md:col-span-2 md:row-span-2 card-hover block group rounded-sm overflow-hidden relative">
        <div class="img-zoom w-full h-full min-h-[400px]">
          <img src="<?= htmlspecialchars($hero['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($hero['title']) ?>"
               class="w-full h-full object-cover" style="min-height:400px" loading="eager">
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent flex flex-col justify-end p-5">
          <span class="text-[10px] font-bold tracking-widest uppercase text-brand bg-white/90 px-2 py-0.5 rounded-sm inline-block mb-2 w-fit"><?= htmlspecialchars($hero['sub_niche_name']) ?></span>
          <h2 class="text-white font-bold text-base md:text-lg leading-snug tracking-wide uppercase"><?= htmlspecialchars($hero['title']) ?></h2>
        </div>
      </a>
      <?php foreach ($featured as $a): ?>
      <a href="<?= htmlspecialchars(cs_url($a['sub_niche_slug'], $a['slug'])) ?>"
         class="card-hover block group rounded-sm overflow-hidden">
        <div class="img-zoom">
          <img src="<?= htmlspecialchars($a['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($a['title']) ?>"
               class="w-full object-cover" style="height:190px" loading="lazy">
        </div>
        <div class="p-3">
          <span class="text-[9px] font-bold tracking-widest uppercase text-brand"><?= htmlspecialchars($a['sub_niche_name']) ?></span>
          <h3 class="mt-1 text-[12px] font-bold uppercase leading-snug tracking-wide text-gray-900 group-hover:text-brand transition-colors"><?= htmlspecialchars($a['title']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($rest)): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-4">
      <?php foreach ($rest as $a): ?>
      <a href="<?= htmlspecialchars(cs_url($a['sub_niche_slug'], $a['slug'])) ?>"
         class="card-hover block group rounded-sm overflow-hidden">
        <div class="img-zoom">
          <img src="<?= htmlspecialchars($a['cover_image_url'] ?: $noImg) ?>" alt="<?= htmlspecialchars($a['title']) ?>"
               class="w-full object-cover" style="height:150px" loading="lazy">
        </div>
        <div class="p-2">
          <span class="text-[9px] font-bold tracking-widest uppercase text-brand"><?= htmlspecialchars($a['sub_niche_name']) ?></span>
          <h3 class="mt-1 text-[11px] font-bold uppercase leading-snug text-gray-900 group-hover:text-brand transition-colors"><?= htmlspecialchars($a['title']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
    <div class="py-24 text-center">
      <h1 class="text-2xl font-extrabold uppercase tracking-wide text-gray-900 mb-4">Guides coming soon</h1>
      <p class="text-sm text-gray-500">Our expert buying guides are being prepared. Check back soon.</p>
    </div>
    <?php endif; ?>
  </section>

  <!-- ── Zones éditoriales (si contenu configuré) ───────────────────────────── -->
  <?php
  $hasContent = $homepageContent && (
    !empty($homepageContent['zone1_html']) ||
    !empty($homepageContent['zone2_html']) ||
    !empty($homepageContent['zone3_html'])
  );
  ?>
  <?php if ($hasContent): ?>
  <section class="bg-gray-50 border-t border-gray-100 py-14">
    <div class="max-w-7xl mx-auto px-4 space-y-16">
      <?php foreach ([1, 2, 3] as $z):
        $html    = $homepageContent["zone{$z}_html"]          ?? '';
        $title   = $homepageContent["zone{$z}_title"]         ?? '';
        $imgUrl  = $homepageContent["zone{$z}_pexels_url"]    ?? '';
        if (!trim(strip_tags($html))) continue;
        $reverse = ($z % 2 === 0); // zones paires : image à gauche
      ?>
      <div class="flex flex-col <?= $reverse ? 'md:flex-row-reverse' : 'md:flex-row' ?> gap-10 items-center">
        <!-- Texte -->
        <div class="flex-1 min-w-0">
          <?php if ($title): ?>
          <h2 class="text-xl font-extrabold uppercase tracking-wide text-gray-900 mb-5 leading-tight">
            <?= htmlspecialchars($title) ?>
          </h2>
          <?php endif; ?>
          <div class="zone-content"><?= $html ?></div>
        </div>
        <!-- Image Pexels -->
        <?php if ($imgUrl): ?>
        <div class="flex-shrink-0 w-full md:w-80 lg:w-96">
          <img src="<?= htmlspecialchars($imgUrl) ?>"
               alt="<?= htmlspecialchars($title ?: $niche['name']) ?>"
               class="w-full rounded-lg object-cover shadow-md"
               style="max-height:340px;object-position:center"
               loading="lazy">
        </div>
        <?php endif; ?>
      </div>
      <?php if ($z < 3): ?>
      <hr class="border-gray-200">
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Galerie Pinterest ──────────────────────────────────────────────────── -->
  <?php if (!empty($galleryImages)): ?>
  <section class="max-w-7xl mx-auto px-4 py-12">
    <div class="divider mb-8">
      <h2 class="text-sm font-bold tracking-[0.3em] uppercase text-gray-900 whitespace-nowrap px-4">Discover our selection</h2>
    </div>
    <div class="pinterest-grid">
      <?php foreach ($galleryImages as $img): ?>
      <a href="<?= htmlspecialchars(cs_url($img['sub_niche_slug'])) ?>"
         class="pinterest-item card-hover" title="<?= htmlspecialchars($img['sub_niche_name']) ?>">
        <img src="<?= htmlspecialchars($img['image_url']) ?>"
             alt="<?= htmlspecialchars($img['sub_niche_name']) ?>"
             loading="lazy"
             onerror="this.closest('.pinterest-item').style.display='none'">
        <div class="overlay"></div>
        <div class="overlay-text"><?= htmlspecialchars($img['sub_niche_name']) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── EEAT experts par sous-niche ───────────────────────────────────────── -->
  <?php if (!empty($eeatProfiles)): ?>
  <section class="bg-gray-50 border-t border-gray-100 py-12">
    <div class="max-w-7xl mx-auto px-4">
      <div class="divider mb-8">
        <h2 class="text-sm font-bold tracking-[0.3em] uppercase text-gray-900 whitespace-nowrap px-4">Our experts</h2>
      </div>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <?php foreach ($eeatProfiles as $ep):
          $initial = mb_strtoupper(mb_substr($ep['expert_name'], 0, 1));
          $bio     = $ep[$bioLang] ?? $ep['bio_en'] ?? $ep['bio_fr'] ?? '';
        ?>
        <a href="<?= htmlspecialchars(cs_url($ep['sub_niche_slug'])) ?>"
           class="eeat-card flex flex-col items-center text-center bg-white border border-gray-200 rounded-lg p-4 hover:no-underline">
          <div class="w-12 h-12 rounded-full bg-brand text-white font-extrabold text-lg flex items-center justify-center mb-3 flex-shrink-0">
            <?= $initial ?>
          </div>
          <p class="text-[10px] font-bold tracking-widest uppercase text-brand mb-1 leading-tight">
            <?= htmlspecialchars($ep['sub_niche_name']) ?>
          </p>
          <p class="text-xs font-semibold text-gray-800 leading-snug">
            <?= htmlspecialchars($ep['expert_name']) ?>
          </p>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Explore by category ───────────────────────────────────────────────── -->
  <section class="max-w-7xl mx-auto px-4 pb-12 pt-10">
    <div class="divider mb-8"><h2 class="text-sm font-bold tracking-[0.3em] uppercase text-gray-900 whitespace-nowrap px-4">Explore by category</h2></div>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 mb-10">
      <?php foreach ($subNiches as $sn): ?>
      <a href="<?= htmlspecialchars(cs_url($sn['slug'])) ?>"
         class="card-hover flex items-center justify-between border border-gray-200 rounded-sm px-4 py-3 hover:border-brand group">
        <span class="text-[11px] font-bold uppercase tracking-wide text-gray-700 group-hover:text-brand transition-colors"><?= htmlspecialchars($sn['name']) ?></span>
        <span class="text-brand text-xs">→</span>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="bg-gray-900 text-white px-8 py-8 flex flex-col md:flex-row items-center justify-between gap-4 rounded-sm">
      <div>
        <h3 class="text-sm font-bold tracking-[0.25em] uppercase mb-2"><?= htmlspecialchars($niche['name']) ?> — Expert Guides</h3>
        <p class="text-gray-300 text-sm leading-relaxed max-w-xl">In-depth buying guides to help you find the best <?= htmlspecialchars(strtolower($niche['name'])) ?> deals in <?= htmlspecialchars($countryLabel) ?>. All products sourced from eBay.</p>
      </div>
      <a href="<?= htmlspecialchars($ebayUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="flex-shrink-0 border border-white text-white text-[11px] font-bold tracking-widest uppercase px-6 py-3 hover:bg-white hover:text-gray-900 transition-colors">SHOP ON EBAY</a>
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
           class="text-xs text-gray-400 hover:text-white transition-colors"><?= htmlspecialchars($sn['name']) ?></a>
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

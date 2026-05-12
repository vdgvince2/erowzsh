<?php
/**
 * Template article — guide d'achat avec produits eBay intégrés.
 * Variables : $article, $products, $relatedArticles, $niche, $subNiche,
 *             $nicheBaseUrl, $faviconUrl, $pageUrl,
 *             $countryLabel, $currency, $mainLanguage,
 *             $ebayRootURL, $ebay_mkrid, $ebay_campid
 */

$bodyHtml = cs_inject_product_blocks($article['content_html'], $products, $currency);

// EEAT — load expert profile for this sub-niche
$eeatProfile = eeat_load($pdo, (int)$subNiche['id'], $mainLanguage);

$publishedIso     = $article['published_at']
    ? (new DateTime($article['published_at']))->format('c') : date('c');
$publishedDisplay = $article['published_at']
    ? (new DateTime($article['published_at']))->format('d M Y') : date('d M Y');

$ebaySearchUrl = ($ebayRootURL ?? 'https://www.ebay.co.uk')
    . '/sch/i.html?_nkw=' . urlencode($subNiche['name'])
    . '&mkcid=1&mkrid=' . ($ebay_mkrid ?? '') . '&campid=' . ($ebay_campid ?? '') . '&mkevt=1';

$coverImg        = !empty($products[0]['image_url']) ? $products[0]['image_url'] : null;
$subNicheHomeUrl = cs_url($subNiche['slug']);
$noImg           = 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="%23e5e7eb"/></svg>';
?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($article['title']) ?></title>
  <meta name="description" content="<?= htmlspecialchars($article['meta_description']) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($pageUrl) ?>">
  <link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>">
  <?php if ($coverImg): ?><meta property="og:image" content="<?= htmlspecialchars($coverImg) ?>"><?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>assets/cs-article.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26','brand-dark':'#c94e1e'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>
    body{font-family:'Montserrat',sans-serif}
    nav::-webkit-scrollbar{display:none}
    .article-body h1{font-size:1.75rem;font-weight:800;line-height:1.25;margin-bottom:1.25rem;text-transform:uppercase;letter-spacing:.03em}
    .article-body h2{font-size:1.15rem;font-weight:700;margin:2rem 0 .75rem;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #e85d26;padding-bottom:.3rem;display:inline-block}
    .article-body p{margin-bottom:1rem;line-height:1.75;font-size:.93rem;color:#374151}
    .article-body ul,.article-body ol{padding-left:1.5rem;margin-bottom:1rem}
    .article-body li{margin-bottom:.4rem;font-size:.93rem;color:#374151;line-height:1.65}
    .article-body details{border:1px solid #e5e7eb;border-radius:6px;padding:.75rem 1rem;margin-bottom:.5rem}
    .article-body summary{font-weight:700;cursor:pointer;font-size:.9rem;text-transform:uppercase;letter-spacing:.04em}
    .article-body details[open] summary{margin-bottom:.5rem;color:#e85d26}
    .article-body .article-intro{background:#f8f9fa;border-left:3px solid #e85d26;padding:1rem 1.25rem;border-radius:0 6px 6px 0;margin-bottom:1.5rem}
    .article-body .eeat-author{background:#fff8f0;border:1px solid #ffe0b0;border-radius:8px;padding:1rem 1.25rem;margin-bottom:1.5rem;font-size:.88rem}
    .article-body .eeat-trust{background:#f0faf0;border:1px solid #b0ddb0;border-radius:8px;padding:1rem 1.25rem;margin-top:2rem;font-size:.85rem;color:#444}
    .article-body .buying-criteria{list-style:none;padding:0}
    .article-body .buying-criteria li{padding:.5rem 0;border-bottom:1px solid #f0f0f0;font-size:.9rem}
    .article-body .article-conclusion{background:#f5f5ff;border-radius:8px;padding:1.25rem;margin-top:2rem}
    .cs-product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin:1.5rem 0}
    .cs-product-card{border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;transition:box-shadow .15s}
    .cs-product-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.1)}
    .cs-product-link{display:flex;flex-direction:column;color:inherit;text-decoration:none;height:100%}
    .cs-product-img-wrap{aspect-ratio:1;overflow:hidden;background:#f5f5f5}
    .cs-product-img-wrap img{width:100%;height:100%;object-fit:contain;padding:.5rem}
    .cs-product-info{padding:.65rem;display:flex;flex-direction:column;gap:.25rem;flex:1}
    .cs-product-title{font-size:.75rem;color:#374151;line-height:1.35;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .cs-product-price{font-size:.95rem;font-weight:700;color:#111827}
    .cs-product-cta{font-size:.75rem;color:#e85d26;font-weight:600;margin-top:auto}
  </style>
  <?php
  $articleAuthor = $eeatProfile
    ? ['@type' => 'Person', 'name' => $eeatProfile['expert_name'], 'sameAs' => [$eeatProfile['social_link']]]
    : ['@type' => 'Organization', 'name' => $WebsiteName];
  ?>
  <script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Article','headline'=>$article['title'],'description'=>$article['meta_description'],'url'=>$pageUrl,'datePublished'=>$publishedIso,'dateModified'=>$publishedIso,'inLanguage'=>strtolower($mainLanguage),'image'=>$coverImg,'author'=>$articleAuthor,'publisher'=>['@type'=>'Organization','name'=>$WebsiteName]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>
  <script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>$niche['name'],'item'=>$nicheBaseUrl],['@type'=>'ListItem','position'=>2,'name'=>$subNiche['name'],'item'=>$subNicheHomeUrl],['@type'=>'ListItem','position'=>3,'name'=>$article['title'],'item'=>$pageUrl]]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>
  <?php if ($eeatProfile): echo eeat_jsonld_person($eeatProfile); endif; ?>
</head>
<body class="bg-white text-gray-900">

<!-- HEADER -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between py-3">
      <nav class="flex items-center gap-1.5 text-[10px] uppercase tracking-widest text-gray-400 font-semibold" aria-label="breadcrumb">
        <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="hover:text-brand transition-colors"><?= htmlspecialchars($niche['name']) ?></a>
        <span>›</span>
        <a href="<?= htmlspecialchars($subNicheHomeUrl) ?>" class="hover:text-brand transition-colors"><?= htmlspecialchars($subNiche['name']) ?></a>
      </nav>
      <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="text-center">
        <span class="block tracking-[0.35em] uppercase text-gray-900 font-extrabold text-base leading-none"><?= htmlspecialchars(strtoupper($niche['name'])) ?></span>
        <span class="block text-[9px] tracking-[0.2em] text-gray-400 uppercase mt-0.5">Buying Guides &amp; Best Deals</span>
      </a>
      <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
         class="text-[10px] font-bold tracking-widest uppercase text-brand hover:text-brand-dark transition-colors">eBay →</a>
    </div>
  </div>
</header>

<!-- HERO IMAGE -->
<?php if ($coverImg): ?>
<div class="w-full bg-gray-100" style="max-height:420px;overflow:hidden">
  <img src="<?= htmlspecialchars($coverImg) ?>" alt="<?= htmlspecialchars($article['title']) ?>"
       class="w-full object-cover" style="max-height:420px;object-position:center">
</div>
<?php endif; ?>

<!-- MAIN -->
<main class="max-w-7xl mx-auto px-4 py-10">
  <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-10 items-start">

    <!-- Article -->
    <article itemscope itemtype="https://schema.org/Article">
      <div class="flex items-center gap-3 mb-6 text-[10px] uppercase tracking-widest text-gray-400 font-semibold">
        <time datetime="<?= htmlspecialchars($publishedIso) ?>"><?= htmlspecialchars($publishedDisplay) ?></time>
        <span>·</span><span><?= htmlspecialchars($countryLabel) ?></span>
        <span>·</span>
        <a href="<?= htmlspecialchars($subNicheHomeUrl) ?>" class="hover:text-brand transition-colors"><?= htmlspecialchars($subNiche['name']) ?></a>
      </div>
      <?php if ($eeatProfile): ?>
      <?= eeat_render_box($eeatProfile, $mainLanguage) ?>
      <?php endif; ?>
      <div class="article-body" itemprop="articleBody"><?= $bodyHtml ?></div>
    </article>

    <!-- Sidebar -->
    <aside class="lg:sticky lg:top-24 space-y-5">

      <!-- Market Pulse -->
      <?php if (!empty($marketPulse)): ?>
      <div class="border border-gray-200 rounded-sm overflow-hidden">
        <div class="bg-gray-900 px-4 py-2.5 flex items-center gap-2">
          <span class="text-sm">📊</span>
          <p class="text-[10px] font-bold tracking-widest uppercase text-white">Market pulse</p>
        </div>
        <div class="p-4 space-y-3">

          <!-- KPIs -->
          <div class="grid grid-cols-2 gap-2">
            <?php if ($marketPulse['watch_total'] > 0): ?>
            <div class="bg-blue-50 rounded p-2.5 text-center">
              <p class="text-lg font-bold text-blue-700 leading-none"><?= number_format($marketPulse['watch_total']) ?></p>
              <p class="text-[10px] text-blue-400 mt-1 leading-none">👁 Watchers</p>
            </div>
            <?php endif; ?>
            <?php if ($marketPulse['bid_total'] > 0): ?>
            <div class="bg-orange-50 rounded p-2.5 text-center">
              <p class="text-lg font-bold text-orange-600 leading-none"><?= number_format($marketPulse['bid_total']) ?></p>
              <p class="text-[10px] text-orange-400 mt-1 leading-none">⚡ Bids</p>
            </div>
            <?php endif; ?>
            <div class="bg-green-50 rounded p-2.5 text-center">
              <p class="text-lg font-bold text-green-700 leading-none"><?= $marketPulse['free_ship_pct'] ?>%</p>
              <p class="text-[10px] text-green-500 mt-1 leading-none">🚚 Free ship.</p>
            </div>
            <?php if ($marketPulse['top_rated_count'] > 0): ?>
            <div class="bg-amber-50 rounded p-2.5 text-center">
              <p class="text-lg font-bold text-amber-700 leading-none"><?= $marketPulse['top_rated_count'] ?></p>
              <p class="text-[10px] text-amber-500 mt-1 leading-none">🏅 Top Rated</p>
            </div>
            <?php endif; ?>
            <?php if ($marketPulse['avg_discount_pct'] > 0): ?>
            <div class="bg-red-50 rounded p-2.5 text-center col-span-2">
              <p class="text-lg font-bold text-red-600 leading-none">-<?= $marketPulse['avg_discount_pct'] ?>%</p>
              <p class="text-[10px] text-red-400 mt-1 leading-none">🏷 Avg. seller discount</p>
            </div>
            <?php endif; ?>
          </div>

          <!-- Condition breakdown -->
          <?php if (count($marketPulse['condition_breakdown']) > 1): ?>
          <div>
            <p class="text-[9px] font-bold tracking-widest uppercase text-gray-400 mb-2">Condition</p>
            <div class="space-y-1.5">
              <?php
              $condColors = ['New' => 'bg-emerald-500', 'Used' => 'bg-blue-400', 'Refurbished' => 'bg-purple-400', 'For parts or not working' => 'bg-gray-300'];
              foreach ($marketPulse['condition_breakdown'] as $cond => $pct):
                $barColor = $condColors[$cond] ?? 'bg-gray-400';
              ?>
              <div class="flex items-center gap-1.5 text-[10px]">
                <span class="w-20 text-gray-500 truncate flex-shrink-0"><?= htmlspecialchars($cond) ?></span>
                <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                  <div class="<?= $barColor ?> h-1.5 rounded-full" style="width:<?= $pct ?>%"></div>
                </div>
                <span class="text-gray-400 w-7 text-right flex-shrink-0"><?= $pct ?>%</span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <p class="text-[9px] text-gray-300 leading-snug">Based on <?= $marketPulse['listings_count'] ?> live eBay listings · updated hourly</p>
        </div>
      </div>
      <?php endif; ?>

      <!-- CTA eBay -->
      <div class="border border-gray-200 rounded-sm p-5">
        <p class="text-[10px] font-bold tracking-widest uppercase text-gray-400 mb-3">Shop on eBay</p>
        <p class="text-xs text-gray-500 leading-relaxed mb-4">Browse <?= htmlspecialchars($subNiche['name']) ?> listings on eBay <?= htmlspecialchars($countryLabel) ?>.</p>
        <a href="<?= htmlspecialchars($ebaySearchUrl) ?>" target="_blank" rel="nofollow noopener sponsored"
           class="block text-center bg-gray-900 text-white text-[11px] font-bold tracking-widest uppercase px-4 py-3 hover:bg-brand transition-colors">
          SEARCH EBAY →
        </a>
      </div>

      <!-- Autres articles de la même sous-niche -->
      <?php if (!empty($relatedArticles)): ?>
      <div class="border border-gray-200 rounded-sm p-5">
        <p class="text-[10px] font-bold tracking-widest uppercase text-gray-400 mb-4">More <?= htmlspecialchars($subNiche['name']) ?> guides</p>
        <div class="space-y-4">
          <?php foreach ($relatedArticles as $rel): ?>
          <a href="<?= htmlspecialchars(cs_url($subNiche['slug'], $rel['slug'])) ?>" class="flex gap-3 group">
            <?php if ($rel['cover_image_url']): ?>
            <img src="<?= htmlspecialchars($rel['cover_image_url']) ?>" alt="" class="w-16 h-16 object-cover rounded-sm flex-shrink-0">
            <?php endif; ?>
            <p class="text-[11px] font-bold uppercase leading-snug text-gray-700 group-hover:text-brand transition-colors"><?= htmlspecialchars($rel['title']) ?></p>
          </a>
          <?php endforeach; ?>
        </div>
        <a href="<?= htmlspecialchars($subNicheHomeUrl) ?>"
           class="block mt-4 text-[10px] font-bold tracking-widest uppercase text-brand hover:underline">
          All <?= htmlspecialchars($subNiche['name']) ?> guides →
        </a>
      </div>
      <?php endif; ?>

      <!-- Retour niche -->
      <div class="border border-gray-200 rounded-sm p-5">
        <p class="text-[10px] font-bold tracking-widest uppercase text-gray-400 mb-3">Explore niche</p>
        <a href="<?= htmlspecialchars($nicheBaseUrl) ?>" class="text-xs text-brand font-semibold hover:underline">
          ← All <?= htmlspecialchars($niche['name']) ?> guides
        </a>
      </div>

    </aside>
  </div>
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
    <div class="border-t border-gray-700 pt-6 text-center">
      <p class="text-xs text-gray-500">© <?= date('Y') ?> <?= htmlspecialchars($niche['name']) ?> — <?= htmlspecialchars($countryLabel) ?> &nbsp;—&nbsp; Affiliate disclosure: product links may earn commission at no extra cost to you.</p>
    </div>
  </div>
</footer>
</body>
</html>

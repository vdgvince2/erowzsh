<?php
/**
 * Template — Homepage d'un site récupéré
 *
 * Variables :
 *   $recoveredSite      array
 *   $recTopPages        array  (slug, title) — 10 premières pages générées
 *   $recNicheArticles   array  (title, slug, sub_niche_slug) — articles content-sites liés
 *   $recBaseUrl         string
 *   $recLang            string
 *   $isLocal            bool
 *   $base               string
 *   $SERVER_Protocol    string
 *   $portStr            string
 */

$siteTitle = ucwords(str_replace(['-', '_', '.'], [' ', ' ', ' '], explode('.', $recoveredSite['domain'])[0]));
?>
<!DOCTYPE html>
<html lang="<?= strtolower($recLang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($siteTitle) ?></title>
  <meta name="description" content="Discover guides and articles on <?= htmlspecialchars($siteTitle) ?>.">
  <link rel="canonical" href="<?= htmlspecialchars($recBaseUrl) ?>">
  <link rel="sitemap" type="application/xml" href="<?= htmlspecialchars(rec_sitemap_xml_url($recoveredSite, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26','brand-dark':'#c94e1e'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>body{font-family:'Montserrat',sans-serif}</style>
</head>
<body class="bg-gray-50 text-gray-900">

<!-- Header -->
<header class="bg-white border-b border-gray-200">
  <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="text-xl font-800 text-brand uppercase tracking-wide">
      <?= htmlspecialchars($recoveredSite['domain']) ?>
    </a>
    <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
       class="text-xs text-gray-500 hover:text-brand transition-colors">Sitemap</a>
  </div>
</header>

<!-- Hero -->
<section class="bg-white border-b border-gray-100 py-12">
  <div class="max-w-4xl mx-auto px-4 text-center">
    <h1 class="text-3xl font-800 uppercase tracking-tight text-gray-900 mb-3">
      <?= htmlspecialchars($siteTitle) ?>
    </h1>
    <p class="text-gray-500 text-sm max-w-xl mx-auto">
      Guides, tips and resources — <?= htmlspecialchars($recoveredSite['domain']) ?>
    </p>
  </div>
</section>

<!-- Top 10 pages CommonCrawl -->
<?php if (!empty($recTopPages)): ?>
<section class="max-w-4xl mx-auto px-4 py-10">
  <h2 class="text-xs font-700 uppercase tracking-widest text-gray-400 mb-6">Latest articles</h2>
  <div class="grid gap-4">
    <?php foreach ($recTopPages as $page): ?>
      <a href="<?= htmlspecialchars(rec_page_url($recoveredSite, $page['slug'], $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
         class="group flex items-center gap-4 bg-white border border-gray-200 rounded-xl p-4 hover:border-brand hover:shadow-sm transition-all">
        <span class="flex-shrink-0 w-8 h-8 bg-orange-50 rounded-lg flex items-center justify-center">
          <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </span>
        <span class="text-sm font-600 text-gray-800 group-hover:text-brand transition-colors">
          <?= htmlspecialchars($page['title']) ?>
        </span>
        <svg class="w-4 h-4 text-gray-300 group-hover:text-brand ml-auto flex-shrink-0 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="mt-6 text-center">
    <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
       class="inline-block text-sm text-brand font-600 hover:underline">View all pages →</a>
  </div>
</section>
<?php endif; ?>

<!-- Articles content-sites liés (nouvelle section) -->
<?php if (!empty($recNicheArticles)): ?>
<section class="max-w-4xl mx-auto px-4 pb-12">
  <h2 class="text-xs font-700 uppercase tracking-widest text-gray-400 mb-6">Related guides</h2>
  <div class="grid sm:grid-cols-2 gap-4">
    <?php foreach ($recNicheArticles as $na): ?>
      <a href="<?= htmlspecialchars(cs_url($na['sub_niche_slug'], $na['slug'])) ?>"
         class="group block bg-white border border-gray-200 rounded-xl p-4 hover:border-brand hover:shadow-sm transition-all">
        <p class="text-sm font-600 text-gray-800 group-hover:text-brand transition-colors line-clamp-2">
          <?= htmlspecialchars($na['title']) ?>
        </p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer class="border-t border-gray-100 py-8 text-center text-xs text-gray-400">
  <div class="max-w-4xl mx-auto px-4">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="hover:text-brand"><?= htmlspecialchars($recoveredSite['domain']) ?></a>
    <span class="mx-2">·</span>
    <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>" class="hover:text-brand">Sitemap</a>
  </div>
</footer>

</body>
</html>

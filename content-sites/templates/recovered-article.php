<?php
/**
 * Template — Article d'un site récupéré
 *
 * Variables disponibles :
 *   $recoveredSite   array  (domain, language, niche_id, ...)
 *   $recPage         array  (slug, title, content_html, ...)
 *   $recRelatedOld   array  pages du même site récupéré (slug, title)
 *   $recRelatedNew   array  articles content-sites liés (title, slug, sub_niche_slug)
 *   $recBaseUrl      string URL racine du domaine récupéré
 *   $recPageUrl      string URL complète de cette page
 *   $recLang         string EN | FR | DE | IT
 *   $isLocal         bool
 *   $base            string
 *   $SERVER_Protocol string
 *   $portStr         string
 */
?>
<!DOCTYPE html>
<html lang="<?= strtolower($recLang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($recPage['title']) ?></title>
  <meta name="description" content="<?= htmlspecialchars(mb_substr(strip_tags($recPage['content_html'] ?? ''), 0, 155)) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($recPageUrl) ?>">
  <link rel="sitemap" type="application/xml" href="<?= htmlspecialchars(rec_sitemap_xml_url($recoveredSite, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26','brand-dark':'#c94e1e'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>
    body{font-family:'Montserrat',sans-serif}
    .article-body h1{font-size:1.75rem;font-weight:800;line-height:1.25;margin-bottom:1.25rem;text-transform:uppercase;letter-spacing:.03em}
    .article-body h2{font-size:1.15rem;font-weight:700;margin:2rem 0 .75rem;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid #e85d26;padding-bottom:.3rem;display:inline-block}
    .article-body p{margin-bottom:1rem;line-height:1.75;font-size:.93rem;color:#374151}
    .article-body ul,.article-body ol{padding-left:1.5rem;margin-bottom:1rem}
    .article-body li{margin-bottom:.4rem;font-size:.93rem;color:#374151;line-height:1.65}
    .article-body details{border:1px solid #e5e7eb;border-radius:6px;padding:.75rem 1rem;margin-bottom:.5rem}
    .article-body summary{font-weight:700;cursor:pointer;font-size:.9rem;text-transform:uppercase;letter-spacing:.04em}
    .article-body details[open] summary{margin-bottom:.5rem;color:#e85d26}
  </style>
  <script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type'    => 'Article',
    'headline' => $recPage['title'],
    'url'      => $recPageUrl,
    'inLanguage' => strtolower($recLang),
    'publisher'  => ['@type' => 'Organization', 'name' => $recoveredSite['domain']],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="bg-white text-gray-900">

<!-- Header -->
<header class="bg-white border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="text-lg font-800 text-brand uppercase tracking-wide">
      <?= htmlspecialchars($recoveredSite['domain']) ?>
    </a>
    <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
       class="text-xs text-gray-500 hover:text-brand transition-colors">Sitemap</a>
  </div>
</header>

<!-- Breadcrumb -->
<div class="max-w-4xl mx-auto px-4 py-2">
  <nav class="text-xs text-gray-400" aria-label="breadcrumb">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="hover:text-brand">Home</a>
    <span class="mx-1">/</span>
    <span class="text-gray-600"><?= htmlspecialchars($recPage['title']) ?></span>
  </nav>
</div>

<!-- Main content -->
<main class="max-w-4xl mx-auto px-4 py-8">
  <div class="article-body">
    <?= $recPage['content_html'] ?>
  </div>

  <!-- Maillage : autres pages de l'ancien site -->
  <?php if (!empty($recRelatedOld)): ?>
  <aside class="mt-10 pt-8 border-t border-gray-200">
    <h2 class="text-sm font-700 uppercase tracking-widest text-gray-500 mb-4">More from <?= htmlspecialchars($recoveredSite['domain']) ?></h2>
    <div class="grid gap-3">
      <?php foreach ($recRelatedOld as $oldLink): ?>
        <a href="<?= htmlspecialchars(rec_page_url($recoveredSite, $oldLink['slug'], $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
           class="block p-3 border border-gray-200 rounded-lg hover:border-brand hover:bg-orange-50 transition-colors text-sm font-500 text-gray-800">
          <?= htmlspecialchars($oldLink['title']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
  <?php endif; ?>

  <!-- Maillage : articles content-sites liés -->
  <?php if (!empty($recRelatedNew)): ?>
  <aside class="mt-8 pt-8 border-t border-gray-200">
    <h2 class="text-sm font-700 uppercase tracking-widest text-gray-500 mb-4">Related guides</h2>
    <div class="grid gap-3">
      <?php foreach ($recRelatedNew as $newLink): ?>
        <a href="<?= htmlspecialchars(cs_url($newLink['sub_niche_slug'], $newLink['slug'])) ?>"
           class="block p-3 border border-gray-200 rounded-lg hover:border-brand hover:bg-orange-50 transition-colors text-sm font-500 text-gray-800">
          <?= htmlspecialchars($newLink['title']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
  <?php endif; ?>
</main>

<!-- Footer -->
<footer class="border-t border-gray-100 mt-16 py-8 text-center text-xs text-gray-400">
  <div class="max-w-4xl mx-auto px-4">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="hover:text-brand"><?= htmlspecialchars($recoveredSite['domain']) ?></a>
    <span class="mx-2">·</span>
    <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>" class="hover:text-brand">Sitemap</a>
  </div>
</footer>

</body>
</html>

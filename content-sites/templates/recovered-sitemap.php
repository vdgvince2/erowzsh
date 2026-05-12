<?php
/**
 * Template — Sitemap HTML paginé (50 URLs/page)
 *
 * Variables :
 *   $recoveredSite      array
 *   $sitemapItems       array  (slug, title)
 *   $sitemapPage        int    numéro de page actuel (1+)
 *   $totalPages         int
 *   $total              int    total d'URLs générées
 *   $recBaseUrl         string
 *   $recLang            string
 *   $isLocal            bool
 *   $base               string
 *   $SERVER_Protocol    string
 *   $portStr            string
 */
?>
<!DOCTYPE html>
<html lang="<?= strtolower($recLang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sitemap — <?= htmlspecialchars($recoveredSite['domain']) ?><?= $sitemapPage > 1 ? ' (page ' . $sitemapPage . ')' : '' ?></title>
  <meta name="robots" content="noindex,follow">
  <link rel="canonical" href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, $sitemapPage, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26'},fontFamily:{sans:['Montserrat','sans-serif']}}}}</script>
  <style>body{font-family:'Montserrat',sans-serif}</style>
</head>
<body class="bg-gray-50 text-gray-900">

<header class="bg-white border-b border-gray-200">
  <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="text-base font-700 text-brand uppercase tracking-wide">
      <?= htmlspecialchars($recoveredSite['domain']) ?>
    </a>
    <span class="text-xs text-gray-400">Sitemap HTML</span>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-8">
  <div class="mb-6 flex items-baseline justify-between">
    <h1 class="text-lg font-700 uppercase tracking-wide text-gray-800">
      Site Map
    </h1>
    <span class="text-xs text-gray-400"><?= number_format($total) ?> pages · page <?= $sitemapPage ?>/<?= $totalPages ?></span>
  </div>

  <!-- URL list -->
  <ul class="divide-y divide-gray-100 bg-white border border-gray-200 rounded-xl overflow-hidden">
    <?php foreach ($sitemapItems as $item): ?>
      <li>
        <a href="<?= htmlspecialchars(rec_page_url($recoveredSite, $item['slug'], $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
           class="flex items-center gap-3 px-4 py-3 hover:bg-orange-50 group transition-colors">
          <svg class="w-3.5 h-3.5 text-gray-300 flex-shrink-0 group-hover:text-brand transition-colors" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
          </svg>
          <span class="text-sm text-gray-700 group-hover:text-brand transition-colors truncate">
            <?= htmlspecialchars($item['title']) ?>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <nav class="mt-8 flex items-center justify-between" aria-label="Pagination">
    <?php if ($sitemapPage > 1): ?>
      <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, $sitemapPage - 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
         class="inline-flex items-center gap-1 px-4 py-2 text-sm font-500 text-gray-700 bg-white border border-gray-200 rounded-lg hover:border-brand hover:text-brand transition-colors">
        ← Previous
      </a>
    <?php else: ?>
      <span></span>
    <?php endif; ?>

    <div class="flex items-center gap-1">
      <?php
      $start = max(1, $sitemapPage - 2);
      $end   = min($totalPages, $sitemapPage + 2);
      if ($start > 1): ?>
        <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
           class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-600 hover:border-brand hover:text-brand transition-colors">1</a>
        <?php if ($start > 2): ?><span class="text-gray-400 text-xs">…</span><?php endif; ?>
      <?php endif; ?>
      <?php for ($p = $start; $p <= $end; $p++): ?>
        <?php if ($p === $sitemapPage): ?>
          <span class="px-3 py-1.5 text-xs rounded-lg bg-brand text-white font-700"><?= $p ?></span>
        <?php else: ?>
          <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, $p, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
             class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-600 hover:border-brand hover:text-brand transition-colors"><?= $p ?></a>
        <?php endif; ?>
      <?php endfor; ?>
      <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1): ?><span class="text-gray-400 text-xs">…</span><?php endif; ?>
        <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, $totalPages, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
           class="px-3 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-600 hover:border-brand hover:text-brand transition-colors"><?= $totalPages ?></a>
      <?php endif; ?>
    </div>

    <?php if ($sitemapPage < $totalPages): ?>
      <a href="<?= htmlspecialchars(rec_sitemap_url($recoveredSite, $sitemapPage + 1, $SERVER_Protocol, $portStr, $isLocal, $base)) ?>"
         class="inline-flex items-center gap-1 px-4 py-2 text-sm font-500 text-gray-700 bg-white border border-gray-200 rounded-lg hover:border-brand hover:text-brand transition-colors">
        Next →
      </a>
    <?php else: ?>
      <span></span>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</main>

<!-- Footer -->
<footer class="border-t border-gray-100 mt-12 py-6 text-center text-xs text-gray-400">
  <a href="<?= htmlspecialchars($recBaseUrl) ?>" class="hover:text-brand"><?= htmlspecialchars($recoveredSite['domain']) ?></a>
</footer>

</body>
</html>

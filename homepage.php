<?php
/* ── Homepage data preparation ───────────────────────── */
$pageTitle           = $WebsiteName;
$additionnalMetaDesc = $label_hero_title;
$noAds               = true;

/* Categories for the interactive grid (homepage=1, max 12 for UX) */
$stmt = $pdo->prepare("SELECT id, name, url FROM categories WHERE level = 1 AND homepage = 1 ORDER BY name ASC LIMIT 12");
$stmt->execute();
$categories = $stmt->fetchAll();

/* All categories for the SEO section */
$stmt2 = $pdo->prepare("SELECT id, name, url FROM categories WHERE level = 1 AND homepage = 1 ORDER BY name ASC LIMIT 40");
$stmt2->execute();
$allCategories = $stmt2->fetchAll();

/* SVG icon helper — returns an inline SVG matching the category name */
function cat_icon(string $name): string {
    $n = strtolower($name);

    // Map: pipe-separated keywords => single SVG path (Heroicons outline, 24×24)
    $icons = [
        'electron|laptop|comput|tablet|tech|pc'
            => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 15V5.25A2.25 2.25 0 013.75 3h16.5A2.25 2.25 0 0122.5 5.25z',
        'phone|mobile'
            => 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3',
        'camera|photo|image'
            => 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316zM16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z',
        'fashion|cloth|apparel|wear|shoe|dress|textile'
            => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
        'home|garden|furniture|kitchen|decor|interior'
            => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'motor|car|vehicle|auto|automobile'
            => 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'bike|cycle|cycl|velo|vélo'
            => 'M12 13.5a3 3 0 100-6 3 3 0 000 6z M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z',
        'sport|gym|fitness|outdoor|camp|hunt|foot|golf|tennis'
            => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'toy|game|gaming|child|kid|lego|puzzle'
            => 'M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-9 5.25-9-5.25v-2.25m18 0l-9-5.25M3 14.25l9 5.25',
        'book|comic|magazine|read|literature'
            => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'music|instrument|vinyl|record|dvd|cd|audio|hifi'
            => 'M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z',
        'jewel|watch|accessory|luxury|bijou'
            => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'art|craft|paint|draw|creat|design'
            => 'M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42',
        'baby|infant|toddler|pram|stroller|enfant|bebe|bébé'
            => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
        'tool|diy|hardware|bricolag|handycraft|fix|repair'
            => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z',
        'collect|antique|vintage|retro|rare|coin|stamp|memorab'
            => 'M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0',
        'health|beauty|care|pharma|medical|wellness'
            => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z',
        'pet|animal|dog|cat|chien|chat'
            => 'M6.633 10.5c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 012.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 00.322-1.672V3a.75.75 0 01.75-.75A2.25 2.25 0 0116.5 4.5c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 01-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 00-1.423-.23H5.904M14.25 9h2.25M5.904 18.729c.098.087.22.141.35.151a48.4 48.4 0 005.494.5c1.495 0 2.973-.155 4.398-.477',
    ];

    // Tag icon (fallback)
    $fallback = 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3zM6 6h.008v.008H6V6z';

    foreach ($icons as $keywords => $d) {
        foreach (explode('|', $keywords) as $kw) {
            if (str_contains($n, $kw)) {
                return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $d . '"/></svg>';
            }
        }
    }

    return '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' . $fallback . '"/></svg>';
}

?>
<!DOCTYPE html>
<html lang="<?= strtolower($mainLanguage) ?>" class="js">

<?php require __DIR__ . '/inc/head-scripts.php'; ?>

<body class="bg-gray-100">

<?php require __DIR__ . '/inc/header.php'; ?>

<!-- PHP → JS: API endpoint URL -->
<script>window.CAT_DEALS_URL = '<?= htmlspecialchars($rootDomain . $base, ENT_QUOTES) ?>category-deals.php';</script>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

  <!-- ══ HERO ═══════════════════════════════════════════════════════════ -->
  <section class="py-8 sm:py-10">
    <div class="rounded-2xl bg-gray-50 p-6 sm:p-10 shadow-sm">
      <h1 class="font-serif text-2xl sm:text-4xl font-bold tracking-tight">
        <?= htmlspecialchars($label_hero_what_hunting, ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <p class="mt-3 text-gray-600 max-w-2xl">
        <?= htmlspecialchars($label_hero_pick_category, ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>
  </section>

  <!-- ══ CATEGORY GRID ══════════════════════════════════════════════════ -->
  <section class="py-2">
    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-3">
      <?php foreach ($categories as $cat): ?>
        <a href="<?= htmlspecialchars($rootDomain . $base . 's/' . $cat['url'], ENT_QUOTES) ?>"
           data-slug="<?= htmlspecialchars($cat['url'], ENT_QUOTES) ?>"
           data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
           class="category-card rounded-xl border border-gray-200 bg-white px-2 py-4 hover:shadow-sm hover:border-blue-300 transition-all flex flex-col items-center gap-2 text-center cursor-pointer">
          <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-100 transition-colors">
            <?= cat_icon($cat['name']) ?>
          </div>
          <span class="text-xs font-medium text-gray-700 leading-tight line-clamp-2">
            <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ══ LIVE DEAL STRIP ════════════════════════════════════════════════ -->
  <section id="deal-strip-wrapper" class="py-6 sm:py-8 rounded-2xl bg-gray-50 mt-6 scroll-mt-20">
    <div class="p-4 sm:p-10 shadow-sm">

      <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="font-serif text-xl sm:text-2xl font-semibold">
            <?= htmlspecialchars($label_live_deals, ENT_QUOTES) ?>
            <span id="deal-strip-label" class="text-blue-500"></span>
          </h2>
          <!-- Filter pills -->
          <div class="mt-2 flex items-center gap-2 flex-wrap">
            <button data-filter="all"
                    class="deal-filter-pill rounded-full px-3 py-1 text-xs font-medium border border-blue-500 bg-blue-500 text-white transition-colors">
              <?= htmlspecialchars($label_filter_all, ENT_QUOTES) ?>
            </button>
            <button data-filter="nobids"
                    class="deal-filter-pill rounded-full px-3 py-1 text-xs font-medium border border-gray-300 bg-white text-gray-600 hover:border-gray-400 transition-colors">
              ⚡ <?= htmlspecialchars($label_filter_nobids, ENT_QUOTES) ?>
            </button>
          </div>
        </div>
        <a id="deal-strip-seeall" href="#"
           class="text-sm text-blue-500 hover:text-blue-700 font-medium transition-colors shrink-0">
          <?= htmlspecialchars($label_see_all_deals, ENT_QUOTES) ?> →
        </a>
      </div>

      <!-- Product grid — filled by homepage.js -->
      <div id="deal-strip"
           class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <!-- Skeleton placeholders -->
        <?php for ($i = 0; $i < 10; $i++): ?>
          <div class="rounded-xl border border-gray-200 bg-white overflow-hidden animate-pulse flex flex-col">
            <div class="aspect-square bg-gray-200"></div>
            <div class="p-2 flex flex-col gap-2">
              <div class="h-2 bg-gray-200 rounded w-2/3"></div>
              <div class="h-2 bg-gray-200 rounded w-full"></div>
              <div class="h-2 bg-gray-200 rounded w-3/4"></div>
              <div class="h-3 bg-gray-200 rounded w-1/2 mt-1"></div>
            </div>
          </div>
        <?php endfor; ?>
      </div>

    </div>
  </section>

  <!-- ══ YOUR EDGE — 3 features ════════════════════════════════════════ -->
  <section class="py-6 sm:py-8 rounded-2xl bg-gray-50 mt-6">
    <div class="p-4 sm:p-10 shadow-sm">
      <h2 class="font-serif text-xl sm:text-2xl font-semibold mb-6">
        <?= htmlspecialchars($label_your_edge_title, ENT_QUOTES) ?>
      </h2>
      <div class="grid sm:grid-cols-3 gap-4">

        <a href="<?= htmlspecialchars($rootDomain . $base, ENT_QUOTES) ?>s/bargain?mode=misspelled"
           class="group rounded-xl border border-gray-200 bg-white px-5 py-5 hover:shadow-sm hover:border-blue-300 transition-all flex flex-col gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0015.803 15.803zM10.5 7.5v6m3-3h-6"/>
            </svg>
          </div>
          <div>
            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($label_feature_misspelled_title, ENT_QUOTES) ?></p>
            <p class="mt-1 text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($label_feature_misspelled_desc, ENT_QUOTES) ?></p>
          </div>
          <span class="mt-auto text-xs text-blue-500 group-hover:text-blue-700 font-medium"><?= htmlspecialchars($label_feature_try, ENT_QUOTES) ?> →</span>
        </a>

        <a href="<?= htmlspecialchars($rootDomain . $base, ENT_QUOTES) ?>s/bargain?mode=lastminute"
           class="group rounded-xl border border-gray-200 bg-white px-5 py-5 hover:shadow-sm hover:border-orange-300 transition-all flex flex-col gap-3">
          <div class="w-10 h-10 rounded-full bg-orange-50 flex items-center justify-center text-orange-500">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($label_feature_ending_title, ENT_QUOTES) ?></p>
            <p class="mt-1 text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($label_feature_ending_desc, ENT_QUOTES) ?></p>
          </div>
          <span class="mt-auto text-xs text-orange-500 group-hover:text-orange-700 font-medium"><?= htmlspecialchars($label_feature_try, ENT_QUOTES) ?> →</span>
        </a>

        <a href="<?= htmlspecialchars($rootDomain . $base, ENT_QUOTES) ?>s/bargain"
           class="group rounded-xl border border-gray-200 bg-white px-5 py-5 hover:shadow-sm hover:border-green-300 transition-all flex flex-col gap-3">
          <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div>
            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($label_feature_score_title, ENT_QUOTES) ?></p>
            <p class="mt-1 text-xs text-gray-500 leading-relaxed"><?= htmlspecialchars($label_feature_score_desc, ENT_QUOTES) ?></p>
          </div>
          <span class="mt-auto text-xs text-green-600 group-hover:text-green-800 font-medium"><?= htmlspecialchars($label_feature_try, ENT_QUOTES) ?> →</span>
        </a>

      </div>
    </div>
  </section>

  <!-- ══ REVIEWS ════════════════════════════════════════════════════════ -->
  <section class="py-6 sm:py-8 rounded-2xl bg-gray-50 mt-6">
    <div class="p-4 sm:p-10 shadow-sm">
      <div class="mb-6">
        <h2 class="font-serif text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_reviews_title, ENT_QUOTES) ?></h2>
        <p class="mt-1 text-sm text-gray-500"><?= htmlspecialchars($label_reviews_subtitle, ENT_QUOTES) ?></p>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach (array_slice($label_reviews, 0, 3) as $review): ?>
        <article class="rounded-xl border border-gray-200 bg-white p-5 flex flex-col gap-3">
          <div class="flex items-center gap-1 text-yellow-400">
            <?php for ($i = 0; $i < 5; $i++): ?>
              <svg class="w-4 h-4 <?= $i < $review['stars'] ? 'fill-current' : 'fill-gray-200' ?>" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            <?php endfor; ?>
          </div>
          <p class="text-sm text-gray-700 leading-relaxed">"<?= htmlspecialchars($review['text'], ENT_QUOTES) ?>"</p>
          <div class="mt-auto pt-2 border-t border-gray-100 flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($review['name'], ENT_QUOTES) ?></span>
            <span class="text-xs text-gray-400"><?= htmlspecialchars($review['location'], ENT_QUOTES) ?></span>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══ SEO: All categories ════════════════════════════════════════════ -->
  <section class="py-6 sm:py-2 rounded-2xl bg-gray-50 mt-6">
    <div class="p-4 sm:p-10 shadow-sm">
      <h2 class="font-serif text-xl sm:text-2xl font-semibold mb-5"><?= htmlspecialchars($label_popular_categories, ENT_QUOTES) ?></h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <?php foreach ($allCategories as $cat): ?>
          <a href="<?= htmlspecialchars($rootDomain . $base . 's/' . $cat['url'], ENT_QUOTES) ?>"
             class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm hover:shadow-sm text-gray-700 hover:text-gray-900 transition-colors">
            <?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ══ SEO: International network ════════════════════════════════════ -->
  <section class="py-6 sm:py-2 mt-6">
    <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200 shadow-sm">
      <h2 class="font-serif text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_international_title, ENT_QUOTES) ?></h2>
      <p class="mt-2 text-sm text-gray-500"><?= htmlspecialchars($label_international_tagline, ENT_QUOTES) ?></p>
      <div class="mt-5 flex flex-wrap gap-2">
        <a href="https://www.site-annonce.fr"     class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇫🇷 <?= htmlspecialchars($label_country_france,  ENT_QUOTES) ?></a>
        <a href="https://www.in-vendita.it"        class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇮🇹 <?= htmlspecialchars($label_country_italy,   ENT_QUOTES) ?></a>
        <a href="https://www.gebraucht-kaufen.de"  class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇩🇪 <?= htmlspecialchars($label_country_germany, ENT_QUOTES) ?></a>
        <a href="https://www.for-sale.co.uk"       class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇬🇧 <?= htmlspecialchars($label_country_uk,      ENT_QUOTES) ?></a>
        <a href="https://www.used.forsale"         class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇺🇸 <?= htmlspecialchars($label_country_usa,     ENT_QUOTES) ?></a>
        <a href="https://www.for-sale.ie"          class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇮🇪 <?= htmlspecialchars($label_country_ireland, ENT_QUOTES) ?></a>
        <a href="https://www.site-annonce.be"      class="rounded-full bg-gray-100 px-3 py-1 text-sm hover:bg-gray-200 transition-colors">🇧🇪 <?= htmlspecialchars($label_country_belgium, ENT_QUOTES) ?></a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

<script src="<?= htmlspecialchars($rootDomainForAssets, ENT_QUOTES) ?>assets/homepage.js?v=<?= date('Ymd') ?>"></script>

</body>
</html>

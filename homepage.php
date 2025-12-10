<?php

$pageTitle = $WebsiteName;
$additionnalMetaDesc = $label_hero_title;
$noAds = true;

// Sélectionner les mots-clés liés à des mots-clés "homepage=1"
$sql = <<<SQL
SELECT *
FROM keywords
WHERE homepage = 1
LIMIT 20
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();



/* 1️⃣ — Requête SQL pour récupérer les catégories principales */
$sql = <<<SQL
SELECT id, name, url, level, parentid
FROM categories
WHERE level = 1 and homepage = 1
ORDER BY name ASC
LIMIT 40
SQL;

$stmt = $pdo->prepare($sql);
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?=strtolower($mainLanguage);?>" class="js">

<?php require __DIR__ . '/inc/head-scripts.php'; ?>

<body class="bg-gray-100">
    
    <?php require __DIR__ . '/inc/header.php'; ?>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
  <!-- Hero / Search -->
  <section class="py-8 sm:py-2">
    <div class="rounded-2xl bg-gray-50 p-6 sm:p-10 shadow-sm">
      <h1 class="text-2xl sm:text-4xl font-bold tracking-tight"><?= htmlspecialchars($label_hero_title, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="mt-3 text-gray-600 max-w-2xl"><?= htmlspecialchars($label_hero_subtitle, ENT_QUOTES, 'UTF-8') ?></p>
        <form role="search" aria-label="Site search" action="<?=$rootDomain.$base;?>s/bargain#results" method="post">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
            <div class="">
              <input type="text" 
                     name="keyword_search" 
                     data-hj-allow
                     placeholder="<?= htmlspecialchars($label_search_placeholder, ENT_QUOTES, 'UTF-8') ?>" 
                     class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none focus:ring-2 focus:ring-black/10">
              <input type="hidden" name="mode" value="standard" />
            </div>
            <div class="">
                <button class="inline-flex items-center gap-2 rounded-xl bg-blue-500 px-5 py-3 text-white hover:bg-gray-800">
                <span><?= htmlspecialchars($label_search_button, ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            </div>
          </div>
        </form>
      
    </div>
  </section>

  <!-- Popular Categories -->
  <section id="categories" class="py-6 sm:py-2 rounded-2xl bg-gray-50 mt-8">
    <div class="p-6 sm:p-10 shadow-sm">
      <div class="mb-5 flex items-center justify-between">
          <h2 class="text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_popular_categories, ENT_QUOTES, 'UTF-8') ?></h2>
          
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
              <a href="<?=$rootDomain.$base;?>s/<?= htmlspecialchars($cat['url'], ENT_QUOTES, 'UTF-8') ?>"
              class="rounded-xl border border-gray-200 bg-white px-3 py-2 hover:shadow-sm">
              <?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?>
              </a>
          <?php endforeach; ?>
          <?php else: ?>
          <p class="text-gray-500 col-span-full text-center"><?= htmlspecialchars($label_no_categories, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if($countryCode =="US" or $isLocal){ ?>
  <!-- MAGAZINE SECTION -->
  <section id="magazine" class="py-8 sm:py-2 rounded-2xl bg-gray-50 mt-8">
    <div class="p-6 sm:p-10 shadow-sm">
      <h2 class="text-xl font-semibold"><?=$label_last_news;?></h2>
      <ul id="rss-feed" class="homelist py-8 px-8">
          <?=homepageBlog($pdo);?>
      </ul>
    </div>
  </section>
  <?php } ?>

  <!-- TOP 20 Products -->
  <section id="top20" class="py-8 sm:py-2 rounded-2xl bg-gray-50 mt-8">
    <div class="p-6 sm:p-10 shadow-sm">
      <div class="mb-5 flex items-center justify-between">
        <h2 class="text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_top20_title, ENT_QUOTES, 'UTF-8') ?></h2>
        
      </div>

      <!-- Grid of product cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($products as $prod): ?>
          <article class="group rounded-2xl border border-gray-200 bg-white px-3 py-2 hover:shadow-sm">
            <a href="<?= htmlspecialchars($prod['keywordURL'] ?? '#', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="nofollow noopener" class="block">            
              
                <?= htmlspecialchars($prod['keyword_name'] ?? $label_fallback_untitled, ENT_QUOTES, 'UTF-8') ?>
              
            </a>         
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- International Network -->
  <section class="py-10 sm:py-2 rounded-2xl bg-gray-50 mt-8">
    <div class="rounded-2xl bg-white p-6 sm:p-8 border border-gray-200 p-6 sm:p-10 shadow-sm">
      <h2 class="text-xl sm:text-2xl font-semibold"><?= htmlspecialchars($label_international_title, ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="prose max-w-none prose-p:mt-2 prose-p:leading-relaxed">
        <p><?= $label_international_p1; ?></p>
      </div>

      <!-- Country chips -->
      <div class="mt-5 flex flex-wrap gap-2">
        <a href="https://www.site-annonce.fr" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_france, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.in-vendita.it" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_italy, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.gebraucht-kaufen.de" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_germany, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.for-sale.co.uk" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_uk, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.used.forsale" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_usa, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.for-sale.ie" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_ireland, ENT_QUOTES, 'UTF-8') ?></a>
        <a href="https://www.site-annonce.be" class="rounded-full bg-gray-100 px-3 py-1 text-sm"><?= htmlspecialchars($label_country_belgium, ENT_QUOTES, 'UTF-8') ?></a>
      </div>
    </div>
  </section>


</main>

    <?php require __DIR__ . '/inc/footer.php'; ?>

</body>
</html>
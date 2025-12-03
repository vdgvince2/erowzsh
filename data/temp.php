<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root { --gap: 16px; --radius: 14px; }
  body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#fafafa; color:#111; }
  header { padding:24px 20px; border-bottom:1px solid #eee; background:#fff; position:sticky; top:0; }
  h1 { margin:0; font-size: clamp(20px, 3vw, 28px); line-height: 1.2; }
  .meta { color:#666; font-size:14px; margin-top:6px; }
  .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
  .grid {
    display:grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: var(--gap);
  }
  .card {
    background:#fff; border:1px solid #eee; border-radius: var(--radius);
    overflow:hidden; display:flex; flex-direction:column; transition: box-shadow .15s ease;
  }
  .card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.08); }
  .thumb {
    aspect-ratio: 4/3; width:100%; object-fit:cover; background:#f2f2f2;
  }
  .content { padding:12px 14px; display:flex; flex-direction:column; gap:8px; }
  .title { font-size:15px; font-weight:600; line-height:1.3; }
  .price { font-size:14px; color:#0b6; font-weight:700; }
  .idx { font-size:12px; color:#999; }
  .empty { padding:24px; text-align:center; color:#666; }
  footer { padding:24px 20px; color:#666; font-size:13px; }
  .badge {
    display:inline-block; background:#eef7ff; color:#246; border:1px solid #d6ecff;
    padding:2px 8px; border-radius:999px; font-size:12px; margin-left:8px;
  }
</style>
</head>
<body>
  <header>
    <h1>
      <?= htmlspecialchars($pageTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
      <span class="badge">source: interne_tous</span>
    </h1>
    <div class="meta">Adresse : <?= htmlspecialchars($adresse ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
  </header>

  <main class="container">
    <?php if (empty($products)): ?>
      <div class="empty">Aucun produit détecté pour cette adresse.</div>
    <?php else: ?>
      <section class="grid" aria-label="Produits associés">
        <?php foreach ($products as $prod): ?>
          <article class="card" role="article">
            <?php if (!empty($prod['image'])): ?>
              <img class="thumb" src="<?= htmlspecialchars($prod['image'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($prod['title'] ?? 'Image produit', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <?php else: ?>
              <div class="thumb" aria-hidden="true"></div>
            <?php endif; ?>
            <div class="content">
              <div class="title"><?= htmlspecialchars($prod['title'] ?? '(Sans titre)', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
              <?php if (!empty($prod['price'])): ?>
                <div class="price"><?= htmlspecialchars($prod['price'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
              <?php endif; ?>
              <div class="idx">Produit #<?= (int)$prod['index'] ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <footer class="container">
    Généré depuis la table <code>interne_tous</code>. Personnalise le rendu selon tes besoins (liens, CTA, etc.).
  </footer>
</body>
</html>

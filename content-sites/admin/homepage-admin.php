<?php
/**
 * Homepage Content Admin
 *
 * Accès local uniquement (IP check).
 * URL : http://antiques.localhost:8888/SH/content-sites/admin/homepage-admin.php
 *
 * Fonctions :
 *   - Sélectionner un site (domaine → niche automatiquement liée)
 *   - Éditer les 3 zones éditoriales de la homepage niche (Quill.js)
 *   - Rechercher et prévisualiser une image Pexels par zone
 *   - Sauvegarder en DB (niche_homepage_content, filtré par domain)
 */

// ── Sécurité : local only ─────────────────────────────────────────────────────
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($clientIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403); die('Accès refusé.');
}

define('CS_CLI', false);
$rootDir = dirname(__DIR__);

// ── Charge sites.json ─────────────────────────────────────────────────────────
$_sitesRaw = json_decode(file_get_contents($rootDir . '/sites.json'), true) ?? [];

// Construit la liste des sites éditables : entrées avec un champ "niche"
$availableSites = [];
foreach ($_sitesRaw as $domain => $cfg) {
    if ($domain[0] === '_') continue; // ignorer _doc etc.
    if (is_string($cfg)) continue;    // ignorer les alias localhost
    if (!isset($cfg['niche'])) continue; // ignorer les clés pays (IE, GB…)

    $ref = $cfg['ref'] ?? null;
    $base = $ref ? ($_sitesRaw[$ref] ?? []) : [];
    $merged = array_merge($base, $cfg);

    $availableSites[$domain] = [
        'domain'  => $domain,
        'niche'   => $cfg['niche'],
        'country' => $merged['country']  ?? '??',
        'lang'    => $merged['language'] ?? 'EN',
        'label'   => $domain . ' (' . ($merged['country'] ?? '??') . ')',
    ];
}

// Trie par niche puis pays
uasort($availableSites, fn($a, $b) =>
    [$a['niche'], $a['country']] <=> [$b['niche'], $b['country']]
);

// ── Site actif ────────────────────────────────────────────────────────────────
$activeDomain = $_GET['site'] ?? $_POST['site'] ?? array_key_first($availableSites) ?? '';
if (!isset($availableSites[$activeDomain]) && !empty($availableSites)) {
    $activeDomain = array_key_first($availableSites);
}
$activeSite = $availableSites[$activeDomain] ?? null;

// ── Bootstrap DB via config.php ───────────────────────────────────────────────
// On injecte le domaine choisi comme contexte ; config.php gère DB + fonctions
$currentDomain = $activeDomain;
$_SERVER['HTTP_HOST'] = $activeDomain ?: 'antiques.localhost';
require_once $rootDir . '/inc/config.php';

// ── Résout la niche en DB ─────────────────────────────────────────────────────
$activeNiche = null;
if ($activeSite) {
    $stmtN = $pdo->prepare('SELECT * FROM niches WHERE slug = :slug LIMIT 1');
    $stmtN->execute([':slug' => $activeSite['niche']]);
    $activeNiche = $stmtN->fetch() ?: null;
}

// ── Action : recherche Pexels (AJAX) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pexels_search') {
    header('Content-Type: application/json');
    $keyword = trim($_POST['keyword'] ?? '');
    if (!$keyword || !PEXELS_API_KEY) {
        echo json_encode(['error' => 'Keyword ou clé Pexels manquant']); exit;
    }
    $ch = curl_init('https://api.pexels.com/v1/search?query=' . urlencode($keyword) . '&per_page=6&orientation=landscape');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . PEXELS_API_KEY],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
    if ($err) { echo json_encode(['error' => $err]); exit; }
    $data   = json_decode($resp, true);
    $photos = array_map(fn($p) => [
        'thumb'        => $p['src']['medium']  ?? '',
        'url'          => $p['src']['large2x'] ?? $p['src']['large'] ?? '',
        'alt'          => $p['alt']             ?? $keyword,
        'photographer' => $p['photographer']    ?? '',
    ], $data['photos'] ?? []);
    echo json_encode(['photos' => $photos]); exit;
}

// ── Action : sauvegarde ───────────────────────────────────────────────────────
$saveMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $nicheId     = (int)($_POST['niche_id']   ?? 0);
    $saveDomain  = trim($_POST['site']         ?? $activeDomain);
    if ($nicheId && $saveDomain) {
        $colNames = []; $colVals = []; $updates = []; $params = [':nid' => $nicheId, ':dom' => $saveDomain];
        foreach ([1, 2, 3] as $z) {
            foreach (['title', 'html', 'pexels_keyword', 'pexels_url'] as $f) {
                $col = "zone{$z}_{$f}";
                $colNames[] = "`{$col}`"; $colVals[] = ":{$col}"; $updates[] = "`{$col}` = VALUES(`{$col}`)";
                $params[":{$col}"] = ($_POST[$col] ?? '') !== '' ? $_POST[$col] : null;
            }
        }
        $pdo->prepare(
            'INSERT INTO niche_homepage_content (niche_id, domain, ' . implode(', ', $colNames) . ')
             VALUES (:nid, :dom, ' . implode(', ', $colVals) . ')
             ON DUPLICATE KEY UPDATE ' . implode(', ', $updates)
        )->execute($params);
        $saveMsg = 'Contenu sauvegardé pour ' . htmlspecialchars($saveDomain) . '.';
    }
}

// ── Charge le contenu existant ────────────────────────────────────────────────
$content = null;
if ($activeNiche && $activeDomain) {
    $stmt = $pdo->prepare('SELECT * FROM niche_homepage_content WHERE niche_id = :nid AND domain = :dom LIMIT 1');
    $stmt->execute([':nid' => $activeNiche['id'], ':dom' => $activeDomain]);
    $content = $stmt->fetch() ?: null;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function field(?array $content, string $key): string {
    return htmlspecialchars($content[$key] ?? '', ENT_QUOTES);
}
function fieldRaw(?array $content, string $key): string {
    return $content[$key] ?? '';
}

// Groupe les sites par niche pour l'affichage
$sitesByNiche = [];
foreach ($availableSites as $domain => $site) {
    $sitesByNiche[$site['niche']][] = $site;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Homepage Admin — Content Sites</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26','brand-dark':'#c94e1e'}}}}</script>
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
  <style>
    .ql-container{font-size:14px;min-height:140px}
    .ql-toolbar{background:#f9fafb;border-color:#e5e7eb!important}
    .ql-container{border-color:#e5e7eb!important}
    .pexels-grid img{cursor:pointer;transition:outline .15s}
    .pexels-grid img.selected{outline:3px solid #e85d26;outline-offset:2px}
    .zone-card{border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
    .zone-card-header{background:#f9fafb;padding:12px 16px;border-bottom:1px solid #e5e7eb;font-weight:700;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#374151}
    optgroup{font-weight:700;font-style:normal}
  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Header -->
<header class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between">
  <div>
    <span class="text-xs font-bold tracking-widest uppercase text-brand">Content Sites</span>
    <h1 class="text-base font-bold mt-0.5">Homepage Content Editor</h1>
  </div>
  <div class="flex items-center gap-3">
    <!-- Sélecteur de site -->
    <form method="get" class="flex items-center gap-2">
      <label class="text-xs text-gray-400 whitespace-nowrap">Site :</label>
      <select name="site" onchange="this.form.submit()"
              class="bg-gray-800 text-white text-xs rounded px-2 py-1 border border-gray-600 max-w-[220px]">
        <?php foreach ($sitesByNiche as $niche => $sites): ?>
        <optgroup label="── <?= htmlspecialchars($niche) ?>">
          <?php foreach ($sites as $s): ?>
          <option value="<?= htmlspecialchars($s['domain']) ?>"
                  <?= $s['domain'] === $activeDomain ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['domain']) ?>
          </option>
          <?php endforeach; ?>
        </optgroup>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</header>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminPages = [
    ['href' => 'homepage-admin.php', 'file' => 'homepage-admin.php', 'label' => 'Homepage Content', 'icon' => '🏠'],
    ['href' => 'recover-admin.php',  'file' => 'recover-admin.php',  'label' => 'Recover',          'icon' => '♻️'],
];
?>
<nav class="bg-gray-800 border-b border-gray-700 px-6">
  <div class="max-w-5xl mx-auto flex items-center gap-1 overflow-x-auto">
    <?php foreach ($adminPages as $page): ?>
    <a href="<?= htmlspecialchars($page['href']) ?>?site=<?= urlencode($activeDomain) ?>"
       class="flex items-center gap-1.5 px-4 py-3 text-xs font-semibold tracking-wide whitespace-nowrap border-b-2 transition-colors
              <?= $currentPage === $page['file']
                  ? 'border-brand text-white'
                  : 'border-transparent text-gray-400 hover:text-white hover:border-gray-500' ?>">
      <span><?= $page['icon'] ?></span>
      <span><?= htmlspecialchars($page['label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
</nav>

<div class="max-w-5xl mx-auto px-4 py-8">

  <?php if ($saveMsg): ?>
  <div class="bg-green-50 border border-green-300 text-green-800 text-sm rounded px-4 py-3 mb-6">
    <?= htmlspecialchars($saveMsg) ?>
  </div>
  <?php endif; ?>

  <?php if (!$activeSite || !$activeNiche): ?>
  <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm rounded px-4 py-3">
    Aucun site configuré avec une niche dans <code>sites.json</code>.
  </div>
  <?php else: ?>

  <!-- Info site actif -->
  <div class="bg-white border border-gray-200 rounded-lg px-5 py-3 mb-6 flex items-center gap-4">
    <div class="flex-1">
      <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Site actif</p>
      <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($activeDomain) ?></p>
    </div>
    <div class="text-center px-5 border-l border-gray-100">
      <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Niche</p>
      <p class="text-sm font-semibold text-brand"><?= htmlspecialchars($activeNiche['name']) ?></p>
    </div>
    <div class="text-center px-5 border-l border-gray-100">
      <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Pays</p>
      <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($activeSite['country']) ?></p>
    </div>
    <div class="text-center px-5 border-l border-gray-100">
      <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Langue</p>
      <p class="text-sm font-semibold text-gray-700"><?= htmlspecialchars($activeSite['lang']) ?></p>
    </div>
    <div class="text-center px-5 border-l border-gray-100">
      <p class="text-xs text-gray-400 uppercase tracking-wider font-bold mb-0.5">Contenu</p>
      <p class="text-sm font-semibold <?= $content ? 'text-green-600' : 'text-gray-400' ?>">
        <?= $content ? 'Existant' : 'Vide' ?>
      </p>
    </div>
  </div>

  <form method="post" id="mainForm">
    <input type="hidden" name="action"   value="save">
    <input type="hidden" name="site"     value="<?= htmlspecialchars($activeDomain) ?>">
    <input type="hidden" name="niche_id" value="<?= $activeNiche['id'] ?>">

    <div class="flex items-center justify-between mb-5">
      <h2 class="text-lg font-extrabold uppercase tracking-wide text-gray-900">
        Zones éditoriales
      </h2>
      <button type="submit"
              class="bg-brand hover:bg-brand-dark text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded transition-colors">
        Sauvegarder
      </button>
    </div>

    <div class="space-y-8">
      <?php foreach ([1, 2, 3] as $z): ?>
      <div class="zone-card bg-white shadow-sm">
        <div class="zone-card-header flex items-center justify-between">
          <span>Zone <?= $z ?></span>
          <?php if ($content && !empty($content["zone{$z}_pexels_url"])): ?>
          <span class="text-green-600 text-[10px] font-semibold">✓ Image configurée</span>
          <?php endif; ?>
        </div>
        <div class="p-5 space-y-5">

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Titre de la zone</label>
            <input type="text" name="zone<?= $z ?>_title"
                   value="<?= field($content, "zone{$z}_title") ?>"
                   placeholder="Ex: Notre sélection d'experts"
                   class="w-full border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-brand">
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Contenu (WYSIWYG)</label>
            <div id="quill-zone<?= $z ?>" style="min-height:140px"><?= fieldRaw($content, "zone{$z}_html") ?></div>
            <input type="hidden" name="zone<?= $z ?>_html" id="hidden-zone<?= $z ?>">
          </div>

          <div class="border-t border-gray-100 pt-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Image Pexels</label>
            <div class="flex gap-2 mb-3">
              <input type="text" id="pexels-kw-<?= $z ?>"
                     value="<?= field($content, "zone{$z}_pexels_keyword") ?>"
                     placeholder="Mot-clé Pexels (ex: antique clock)"
                     class="flex-1 border border-gray-200 rounded px-3 py-2 text-sm focus:outline-none focus:border-brand">
              <button type="button" onclick="searchPexels(<?= $z ?>)"
                      class="bg-gray-800 hover:bg-gray-700 text-white text-xs font-bold px-4 py-2 rounded transition-colors whitespace-nowrap">
                Rechercher
              </button>
            </div>
            <input type="hidden" name="zone<?= $z ?>_pexels_keyword" id="pexels-kw-hidden-<?= $z ?>" value="<?= field($content, "zone{$z}_pexels_keyword") ?>">
            <input type="hidden" name="zone<?= $z ?>_pexels_url"     id="pexels-url-<?= $z ?>"       value="<?= field($content, "zone{$z}_pexels_url") ?>">
            <?php if ($content && !empty($content["zone{$z}_pexels_url"])): ?>
            <div class="mb-3">
              <p class="text-[10px] text-gray-400 mb-1.5">Image actuelle :</p>
              <img src="<?= htmlspecialchars($content["zone{$z}_pexels_url"]) ?>"
                   class="h-28 w-auto rounded object-cover border border-gray-200" alt="">
            </div>
            <?php endif; ?>
            <div id="pexels-results-<?= $z ?>" class="pexels-grid grid grid-cols-3 gap-2 hidden"></div>
            <div id="pexels-loading-<?= $z ?>" class="hidden text-xs text-gray-400 py-2">Recherche en cours…</div>
          </div>

        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-6 flex justify-end">
      <button type="submit"
              class="bg-brand hover:bg-brand-dark text-white text-xs font-bold uppercase tracking-widest px-8 py-3 rounded transition-colors">
        Sauvegarder le contenu
      </button>
    </div>
  </form>
  <?php endif; ?>

</div>

<script>
const quillToolbar = [
  [{ header: [2, 3, false] }],
  ['bold', 'italic', 'underline'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  ['link'], ['clean']
];

[1, 2, 3].forEach(z => {
  const editor = new Quill('#quill-zone' + z, { theme: 'snow', modules: { toolbar: quillToolbar } });
  editor.on('text-change', () => {
    document.getElementById('hidden-zone' + z).value = editor.root.innerHTML;
  });
  document.getElementById('hidden-zone' + z).value = editor.root.innerHTML;
});

document.getElementById('mainForm')?.addEventListener('submit', () => {
  [1, 2, 3].forEach(z => {
    const q = Quill.find(document.getElementById('quill-zone' + z));
    if (q) document.getElementById('hidden-zone' + z).value = q.root.innerHTML;
    document.getElementById('pexels-kw-hidden-' + z).value =
      document.getElementById('pexels-kw-' + z).value;
  });
});

async function searchPexels(zone) {
  const kw  = document.getElementById('pexels-kw-' + zone).value.trim();
  const res = document.getElementById('pexels-results-' + zone);
  const ld  = document.getElementById('pexels-loading-' + zone);
  if (!kw) return;

  res.classList.add('hidden');
  ld.classList.remove('hidden');

  const fd = new FormData();
  fd.append('action', 'pexels_search');
  fd.append('keyword', kw);
  fd.append('site', '<?= htmlspecialchars($activeDomain, ENT_QUOTES) ?>');

  const resp = await fetch('', { method: 'POST', body: fd });
  const data = await resp.json();
  ld.classList.add('hidden');

  if (data.error) { alert('Erreur Pexels : ' + data.error); return; }

  res.innerHTML = '';
  data.photos.forEach(p => {
    const img = document.createElement('img');
    img.src = p.thumb; img.alt = p.alt; img.title = 'Photo by ' + p.photographer;
    img.className = 'w-full h-20 object-cover rounded border border-gray-200';
    img.addEventListener('click', () => {
      res.querySelectorAll('img').forEach(i => i.classList.remove('selected'));
      img.classList.add('selected');
      document.getElementById('pexels-url-' + zone).value      = p.url;
      document.getElementById('pexels-kw-hidden-' + zone).value = kw;
    });
    res.appendChild(img);
  });
  res.classList.remove('hidden');
}
</script>

</body>
</html>

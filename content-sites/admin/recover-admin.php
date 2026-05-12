<?php
/**
 * Recovered Sites — Interface d'administration
 *
 * Accès local uniquement (IP check).
 * Accessible via : http://[niche].localhost:8888/SH/content-sites/scripts/recover/admin.php
 *
 * Fonctions :
 *   - Ajouter un domaine récupéré (domain, language, niche, country)
 *   - Lancer le crawl CommonCrawl (CLI en arrière-plan)
 *   - Lancer la génération de contenu AI
 *   - Voir les stats par site
 *   - Activer / désactiver / supprimer un site
 */

// ── Sécurité : local only ─────────────────────────────────────────────────────
require_once __DIR__ . '/auth.php';

define('CS_CLI', false);

$rootDir = dirname(__DIR__); // /content-sites

/**
 * Trouve le binaire PHP CLI fiable pour exec().
 * PHP_BINARY sous Apache/MAMP pointe vers le PHP Apache (non CLI).
 * On cherche d'abord un php dans le même dossier que PHP_BINARY,
 * puis des emplacements connus de MAMP, puis le fallback système.
 */
function rec_php_binary(): string
{
    $candidates = [
        PHP_BINARY,
        dirname(PHP_BINARY) . '/php',
        '/Applications/MAMP/bin/php/php8.2.0/bin/php',
        '/Applications/MAMP/bin/php/php8.1.0/bin/php',
        '/usr/local/bin/php',
        '/usr/bin/php',
    ];
    foreach ($candidates as $bin) {
        if (is_executable($bin)) return escapeshellcmd($bin);
    }
    return 'php'; // dernier recours
}

// ── Charge config ─────────────────────────────────────────────────────────────

$envFile = $rootDir . '/../.env';
if (!file_exists($envFile)) $envFile = $rootDir . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v);
        if (!getenv($k)) putenv("{$k}={$v}");
    }
}

// ── Langues disponibles ───────────────────────────────────────────────────────

$languages = ['EN' => 'English', 'FR' => 'French', 'DE' => 'German', 'IT' => 'Italian', 'NL' => 'Dutch'];

// ── DB unique CONTENT (depuis .env) ──────────────────────────────────────────

$_dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$_dbPort = getenv('DB_PORT') ?: '8889';
$_dbName = getenv('DB_NAME') ?: 'CONTENT';
$_dbUser = getenv('DB_USER') ?: '';
$_dbPass = getenv('DB_PASS') ?: '';
$pdo = new PDO(
    "mysql:host={$_dbHost};port={$_dbPort};dbname={$_dbName};charset=utf8mb4",
    $_dbUser, $_dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
unset($_dbHost, $_dbPort, $_dbName, $_dbUser, $_dbPass);

// Charge les niches pour le select
$niches = $pdo->query('SELECT id, name, slug FROM niches ORDER BY sort_order, name')->fetchAll();

// ── Handler AJAX : lecture de log ────────────────────────────────────────────

if (isset($_GET['action']) && $_GET['action'] === 'fetch_log') {
    $siteId = (int)($_GET['site_id'] ?? 0);
    $type   = ($_GET['type'] ?? '') === 'gen' ? 'gen' : 'crawl';
    $file   = '/tmp/rec_' . $type . '_' . $siteId . '.log';
    header('Content-Type: text/plain; charset=utf-8');
    if (!$siteId || !file_exists($file)) {
        echo '(aucun log disponible)';
    } else {
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        echo implode("\n", array_slice($lines, -200));
    }
    exit;
}

// ── Actions POST ──────────────────────────────────────────────────────────────

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_site') {
        $domain      = strtolower(trim($_POST['domain'] ?? ''));
        $crawlDomain = strtolower(trim($_POST['crawl_domain'] ?? ''));
        $language    = strtoupper(trim($_POST['language'] ?? 'EN'));
        $nicheId     = (int)($_POST['niche_id'] ?? 0) ?: null;

        // Normalise les domaines (retire www. et http://)
        $domain      = rtrim(preg_replace('#^(https?://)?(www\.)?#i', '', $domain), '/');
        $crawlDomain = rtrim(preg_replace('#^(https?://)?(www\.)?#i', '', $crawlDomain), '/');
        if (!$crawlDomain) $crawlDomain = $domain; // fallback si non renseigné

        if (!$domain || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
            $flash = '<div class="flash error">Domaine invalide.</div>';
        } else {
            try {
                $pdo->prepare('INSERT INTO recovered_sites (domain, crawl_domain, language, niche_id) VALUES (?, ?, ?, ?)')
                    ->execute([$domain, $crawlDomain, $language, $nicheId]);
                $flash = '<div class="flash ok">Domaine <strong>' . htmlspecialchars($domain) . '</strong> ajouté (crawl : ' . htmlspecialchars($crawlDomain) . ').</div>';
            } catch (PDOException $e) {
                $flash = '<div class="flash error">Erreur : ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    if ($action === 'toggle_status') {
        $siteId = (int)($_POST['site_id'] ?? 0);
        $pdo->prepare('UPDATE recovered_sites SET status = IF(status="active","inactive","active") WHERE id = ?')
            ->execute([$siteId]);
        $flash = '<div class="flash ok">Statut mis à jour.</div>';
    }

    if ($action === 'delete_site') {
        $siteId = (int)($_POST['site_id'] ?? 0);
        $pdo->prepare('DELETE FROM recovered_sites WHERE id = ?')->execute([$siteId]);
        $flash = '<div class="flash ok">Site supprimé.</div>';
    }

    if ($action === 'run_crawl') {
        $siteId  = (int)($_POST['site_id'] ?? 0);
        $phpBin  = rec_php_binary();
        $script  = escapeshellarg($rootDir . '/scripts/recover/fetch_commoncrawl.php');
        $log     = escapeshellarg('/tmp/rec_crawl_' . $siteId . '.log');
        $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $apiUrl  = escapeshellarg($proto . '://' . $_SERVER['HTTP_HOST']);
        $cmd     = "$phpBin $script --site-id=" . escapeshellarg((string)$siteId) . " --api-url=$apiUrl > $log 2>&1 &";
        exec($cmd);
        $flash = '<div class="flash ok">Crawl CommonCrawl lancé pour le site #' . $siteId . '.</div>';
    }

    if ($action === 'run_generate') {
        $siteId = (int)($_POST['site_id'] ?? 0);
        $limit  = min(200, (int)($_POST['gen_limit'] ?? 50));
        $phpBin = rec_php_binary();
        $script = escapeshellarg($rootDir . '/scripts/recover/generate_content.php');
        $log    = escapeshellarg('/tmp/rec_gen_' . $siteId . '.log');
        $cmd    = "$phpBin $script --site-id=" . escapeshellarg((string)$siteId) . " --limit=" . escapeshellarg((string)$limit) . " > $log 2>&1 &";
        exec($cmd);
        $flash = '<div class="flash ok">Génération IA lancée (' . $limit . ' pages) pour le site #' . $siteId . '.</div>';
    }

    if ($action === 'retry_errors') {
        $siteId = (int)($_POST['site_id'] ?? 0);
        $count  = $pdo->prepare("UPDATE recovered_pages SET status = 'pending', error_msg = NULL WHERE site_id = ? AND status = 'error'");
        $count->execute([$siteId]);
        $flash = '<div class="flash ok">' . $count->rowCount() . ' page(s) remises en pending.</div>';
    }

    if ($action === 'update_niche') {
        $siteId  = (int)($_POST['site_id'] ?? 0);
        $nicheId = (int)($_POST['niche_id'] ?? 0) ?: null;
        $lang    = strtoupper(trim($_POST['language'] ?? 'EN'));
        $pdo->prepare('UPDATE recovered_sites SET niche_id = ?, language = ? WHERE id = ?')
            ->execute([$nicheId, $lang, $siteId]);
        $flash = '<div class="flash ok">Configuration mise à jour.</div>';
    }
}

// ── Charge les sites ──────────────────────────────────────────────────────────

$sites = $pdo->query('
    SELECT rs.*, n.name AS niche_name,
           (SELECT COUNT(*) FROM recovered_pages rp WHERE rp.site_id = rs.id) AS total_pages,
           (SELECT COUNT(*) FROM recovered_pages rp WHERE rp.site_id = rs.id AND rp.status = "generated") AS generated_pages,
           (SELECT COUNT(*) FROM recovered_pages rp WHERE rp.site_id = rs.id AND rp.status = "pending") AS pending_pages,
           (SELECT COUNT(*) FROM recovered_pages rp WHERE rp.site_id = rs.id AND rp.status = "error") AS error_pages
    FROM recovered_sites rs
    LEFT JOIN niches n ON n.id = rs.niche_id
    ORDER BY rs.id DESC
')->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recovered Sites — Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>tailwind.config={theme:{extend:{colors:{brand:'#e85d26'}}}}</script>
  <style>
    body{font-family:system-ui,sans-serif}
    .flash{padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;font-size:.875rem}
    .flash.ok{background:#ecfdf5;border:1px solid #6ee7b7;color:#065f46}
    .flash.error{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
    code{background:#f3f4f6;padding:.1rem .3rem;border-radius:.25rem;font-size:.8rem}
  </style>
</head>
<body class="bg-gray-50">

<header class="bg-gray-900 text-white px-6 py-4">
  <span class="text-xs font-bold tracking-widest uppercase text-brand">Content Sites</span>
  <h1 class="text-base font-bold mt-0.5">Recovered Sites — Admin</h1>
</header>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminPages = [
    ['href' => 'homepage-admin.php', 'file' => 'homepage-admin.php', 'label' => 'Homepage Content', 'icon' => '🏠'],
    ['href' => 'recover-admin.php',  'file' => 'recover-admin.php',  'label' => 'Recover',          'icon' => '♻️'],
];
?>
<nav class="bg-gray-800 border-b border-gray-700 px-6">
  <div class="max-w-6xl mx-auto flex items-center gap-1 overflow-x-auto">
    <?php foreach ($adminPages as $page): ?>
    <a href="<?= htmlspecialchars($page['href']) ?>"
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

<main class="max-w-6xl mx-auto px-6 py-8">

  <?= $flash ?>

  <!-- Ajouter un site -->
  <section class="bg-white border border-gray-200 rounded-xl p-6 mb-8">
    <h2 class="text-sm font-700 uppercase tracking-widest text-gray-500 mb-4">Ajouter un domaine</h2>
    <form method="post" class="grid sm:grid-cols-5 gap-3 items-end">
      <input type="hidden" name="action" value="add_site">

      <div>
        <label class="block text-xs text-gray-500 mb-1">Hostname routing</label>
        <input type="text" name="domain" placeholder="minderlist.localhost" required
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
        <p class="text-xs text-gray-400 mt-0.5">Ex local : minderlist.localhost</p>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">Vrai domaine <span class="text-brand">(CommonCrawl)</span></label>
        <input type="text" name="crawl_domain" placeholder="minderlist.com"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
        <p class="text-xs text-gray-400 mt-0.5">Ex : minderlist.com</p>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">Langue</label>
        <select name="language" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
          <?php foreach ($languages as $code => $name): ?>
            <option value="<?= $code ?>"><?= $name ?> (<?= $code ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">Niche (optionnel)</label>
        <select name="niche_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
          <option value="">— aucune —</option>
          <?php foreach ($niches as $n): ?>
            <option value="<?= $n['id'] ?>"><?= htmlspecialchars($n['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button type="submit" class="bg-brand text-white rounded-lg px-4 py-2 text-sm font-600 hover:bg-orange-600 transition-colors">
        Ajouter
      </button>
    </form>
  </section>

  <!-- Liste des sites -->
  <section>
    <h2 class="text-sm font-700 uppercase tracking-widest text-gray-500 mb-4">
      Sites configurés (<?= count($sites) ?>)
    </h2>

    <?php if (empty($sites)): ?>
      <p class="text-sm text-gray-400 py-8 text-center">Aucun site configuré.</p>
    <?php endif; ?>

    <div class="grid gap-4">
      <?php foreach ($sites as $site): ?>
      <div class="bg-white border border-gray-200 rounded-xl overflow-hidden <?= $site['status'] === 'inactive' ? 'opacity-60' : '' ?>">

        <!-- Header ligne -->
        <div class="px-5 py-4 flex items-center justify-between gap-4 border-b border-gray-100">
          <div class="flex items-center gap-3">
            <span class="w-2 h-2 rounded-full <?= $site['status'] === 'active' ? 'bg-green-400' : 'bg-gray-300' ?>"></span>
            <strong class="text-sm font-700 text-gray-800"><?= htmlspecialchars($site['domain']) ?></strong>
            <?php if ($site['crawl_domain'] && $site['crawl_domain'] !== $site['domain']): ?>
              <span class="text-xs text-gray-400">→ crawl: <code class="text-xs"><?= htmlspecialchars($site['crawl_domain']) ?></code></span>
            <?php endif; ?>
            <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full"><?= $site['language'] ?></span>
            <?php if ($site['niche_name']): ?>
              <span class="text-xs bg-orange-50 text-brand px-2 py-0.5 rounded-full"><?= htmlspecialchars($site['niche_name']) ?></span>
            <?php endif; ?>
          </div>
          <div class="flex items-center gap-2">
            <!-- Toggle status -->
            <form method="post" class="inline">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
              <button type="submit" class="text-xs text-gray-500 hover:text-brand border border-gray-200 rounded-lg px-2 py-1 transition-colors">
                <?= $site['status'] === 'active' ? 'Désactiver' : 'Activer' ?>
              </button>
            </form>
            <!-- Supprimer -->
            <form method="post" class="inline" onsubmit="return confirm('Supprimer ce site et toutes ses pages ?')">
              <input type="hidden" name="action" value="delete_site">
              <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
              <button type="submit" class="text-xs text-red-400 hover:text-red-600 border border-red-100 rounded-lg px-2 py-1 transition-colors">
                Supprimer
              </button>
            </form>
          </div>
        </div>

        <div class="px-5 py-4 grid sm:grid-cols-3 gap-6">

          <!-- Stats -->
          <div>
            <p class="text-xs text-gray-400 mb-2 uppercase tracking-wider">Pages</p>
            <div class="flex gap-4 text-sm">
              <span class="text-gray-800 font-600"><?= $site['total_pages'] ?> <span class="text-gray-400 font-400 text-xs">total</span></span>
              <span class="text-green-600 font-600"><?= $site['generated_pages'] ?> <span class="text-gray-400 font-400 text-xs">générées</span></span>
              <span class="text-orange-500 font-600"><?= $site['pending_pages'] ?> <span class="text-gray-400 font-400 text-xs">pending</span></span>
              <?php if ($site['error_pages'] > 0): ?>
                <span class="text-red-500 font-600"><?= $site['error_pages'] ?> <span class="text-gray-400 font-400 text-xs">erreurs</span></span>
              <?php endif; ?>
            </div>
            <?php if ($site['crawled_at']): ?>
              <p class="text-xs text-gray-400 mt-1">Dernier crawl : <?= date('d/m/Y H:i', strtotime($site['crawled_at'])) ?></p>
            <?php endif; ?>
          </div>

          <!-- Actions : crawl + generate -->
          <div>
            <p class="text-xs text-gray-400 mb-2 uppercase tracking-wider">Actions</p>
            <div class="flex flex-col gap-2">
              <form method="post" class="flex items-center gap-2">
                <input type="hidden" name="action" value="run_crawl">
                <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
                <button type="submit" class="text-xs bg-gray-800 text-white rounded-lg px-3 py-1.5 hover:bg-gray-700 transition-colors">
                  🕷 Crawl CommonCrawl
                </button>
                <span class="text-xs text-gray-400">max 200 URLs</span>
              </form>
              <form method="post" class="flex items-center gap-2">
                <input type="hidden" name="action" value="run_generate">
                <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
                <input type="number" name="gen_limit" value="50" min="1" max="200"
                       class="w-16 border border-gray-200 rounded text-xs px-2 py-1 text-center">
                <button type="submit" class="text-xs bg-brand text-white rounded-lg px-3 py-1.5 hover:bg-orange-600 transition-colors">
                  ✨ Générer contenu
                </button>
              </form>
            </div>
          </div>

          <!-- Config : niche + langue -->
          <div>
            <p class="text-xs text-gray-400 mb-2 uppercase tracking-wider">Configuration</p>
            <form method="post" class="flex flex-col gap-2">
              <input type="hidden" name="action" value="update_niche">
              <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
              <select name="niche_id" class="border border-gray-200 rounded text-xs px-2 py-1.5 focus:outline-none focus:border-brand">
                <option value="">— pas de niche —</option>
                <?php foreach ($niches as $n): ?>
                  <option value="<?= $n['id'] ?>" <?= $site['niche_id'] == $n['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($n['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select name="language" class="border border-gray-200 rounded text-xs px-2 py-1.5 focus:outline-none focus:border-brand">
                <?php foreach ($languages as $code => $name): ?>
                  <option value="<?= $code ?>" <?= $site['language'] === $code ? 'selected' : '' ?>>
                    <?= $name ?> (<?= $code ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="text-xs border border-gray-300 text-gray-600 rounded-lg px-3 py-1.5 hover:border-brand hover:text-brand transition-colors self-start">
                Sauvegarder
              </button>
            </form>
          </div>

        </div>

        <!-- Logs crawl / génération -->
        <div class="border-t border-gray-100 bg-gray-950 px-5 py-3" id="log-area-<?= $site['id'] ?>">
          <div class="flex items-center gap-3 mb-2">
            <span class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Logs</span>
            <button onclick="loadLog(<?= $site['id'] ?>, 'crawl', this)"
                    class="text-xs border border-gray-700 text-gray-400 hover:text-white hover:border-gray-400 rounded px-2 py-0.5 transition-colors">
              Crawl
            </button>
            <button onclick="loadLog(<?= $site['id'] ?>, 'gen', this)"
                    class="text-xs border border-gray-700 text-gray-400 hover:text-white hover:border-gray-400 rounded px-2 py-0.5 transition-colors">
              Génération
            </button>
            <button onclick="stopLog(<?= $site['id'] ?>)"
                    class="text-xs text-gray-600 hover:text-gray-400 rounded px-2 py-0.5 transition-colors hidden" id="log-stop-<?= $site['id'] ?>">
              ✕ stop
            </button>
            <span class="text-xs text-gray-600 ml-auto" id="log-status-<?= $site['id'] ?>"></span>
          </div>
          <pre id="log-output-<?= $site['id'] ?>"
               class="text-[11px] font-mono text-green-400 bg-black rounded p-3 max-h-48 overflow-y-auto whitespace-pre-wrap break-all leading-relaxed hidden"></pre>
        </div>

        <!-- Erreurs de génération -->
        <?php
        $errPages = $pdo->prepare('SELECT id, title, slug, error_msg FROM recovered_pages WHERE site_id = ? AND status = "error" ORDER BY id DESC LIMIT 20');
        $errPages->execute([$site['id']]);
        $errList = $errPages->fetchAll();
        if ($errList):
        ?>
        <div class="border-t border-red-100 bg-red-50 px-5 py-3">
          <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-600 text-red-600 uppercase tracking-wider">⚠ Erreurs de génération (<?= count($errList) ?>)</p>
            <form method="post" class="inline">
              <input type="hidden" name="action" value="retry_errors">
              <input type="hidden" name="site_id" value="<?= $site['id'] ?>">
              <button type="submit" class="text-xs bg-red-500 text-white rounded px-2 py-1 hover:bg-red-600 transition-colors">
                Retry toutes
              </button>
            </form>
          </div>
          <div class="space-y-1.5 max-h-48 overflow-y-auto">
            <?php foreach ($errList as $ep): ?>
            <div class="text-xs bg-white border border-red-100 rounded p-2">
              <span class="font-500 text-gray-700">[<?= $ep['id'] ?>] <?= htmlspecialchars(mb_substr($ep['title'], 0, 60)) ?></span>
              <?php if ($ep['error_msg']): ?>
                <div class="text-red-500 mt-0.5 font-mono text-[11px] break-all"><?= htmlspecialchars($ep['error_msg']) ?></div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Domaine local dev info -->
        <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-400">
          <strong>Local dev :</strong>
          enregistrer le domaine comme <code><?= htmlspecialchars(preg_replace('/\.com$|\.ie$|\.fr$|\.de$|\.co\.uk$/', '.localhost', $site['domain'])) ?></code>
          et ajouter dans <code>sites.json</code> :
          <code>"<?= htmlspecialchars(preg_replace('/\.com$|\.ie$|\.fr$|\.de$|\.co\.uk$/', '.localhost', $site['domain'])) ?>": "IE"</code>
          — En prod, le domaine est <code><?= htmlspecialchars($site['domain']) ?></code>.
        </div>

      </div>
      <?php endforeach; ?>
    </div>
  </section>

</main>

<script>
const _logTimers = {};

function loadLog(siteId, type, btn) {
  const out    = document.getElementById('log-output-' + siteId);
  const status = document.getElementById('log-status-' + siteId);
  const stop   = document.getElementById('log-stop-'   + siteId);

  stopLog(siteId);
  out.classList.remove('hidden');
  stop.classList.remove('hidden');
  status.textContent = 'chargement…';

  const poll = () => {
    fetch('?action=fetch_log&site_id=' + siteId + '&type=' + type)
      .then(r => r.text())
      .then(txt => {
        out.textContent = txt;
        out.scrollTop   = out.scrollHeight;
        status.textContent = new Date().toLocaleTimeString();
      })
      .catch(() => { status.textContent = 'erreur'; });
    _logTimers[siteId] = setTimeout(poll, 3000);
  };
  poll();
}

function stopLog(siteId) {
  clearTimeout(_logTimers[siteId]);
  const stop = document.getElementById('log-stop-' + siteId);
  if (stop) stop.classList.add('hidden');
  const status = document.getElementById('log-status-' + siteId);
  if (status) status.textContent = '';
}
</script>
</body>
</html>

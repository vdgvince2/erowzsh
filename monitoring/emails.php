<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Monitoring alertes & contacts multi-pays</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg: #0b0c10;
            --bg-alt: #11131a;
            --card: #151823;
            --accent: #4f9cff;
            --accent-soft: rgba(79, 156, 255, 0.1);
            --border: #252a3a;
            --text: #e5e9ff;
            --muted: #9ca3c9;
            --danger: #ff4f6a;
            --success: #4ade80;
            --radius-lg: 12px;
            --radius-full: 999px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
            background: radial-gradient(circle at top, #171c2d 0, #05060b 55%, #020308 100%);
            color: var(--text);
            display: flex;
            justify-content: center;
            padding: 24px;
        }

        .app {
            width: 100%;
            max-width: 1300px;
        }

        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 12px;
        }

        .title h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: 0.04em;
        }

        .title p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .btn {
            border: none;
            border-radius: var(--radius-full);
            padding: 7px 14px;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.08s ease, box-shadow 0.08s ease, background 0.12s;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f9cff, #8b5dff);
            color: #fff;
            box-shadow: 0 10px 25px rgba(79, 156, 255, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(79, 156, 255, 0.35);
        }

        .tabs {
            display: inline-flex;
            padding: 4px;
            border-radius: var(--radius-full);
            background: rgba(7, 10, 22, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 14px;
        }

        .tab {
            padding: 7px 16px;
            border-radius: var(--radius-full);
            font-size: 12px;
            cursor: pointer;
            color: var(--muted);
            border: none;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tab.active {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .content-card {
            background: radial-gradient(circle at top left, #1b2340 0, #10131f 45%, #080914 100%);
            border-radius: var(--radius-lg);
            padding: 16px 18px 14px;
            border: 1px solid rgba(255, 255, 255, 0.03);
            box-shadow:
                0 20px 45px rgba(0, 0, 0, 0.7),
                inset 0 0 0 1px rgba(255, 255, 255, 0.02);
        }

        .meta-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 12px;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.04);
            font-size: 11px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: var(--success);
            box-shadow: 0 0 10px rgba(74, 222, 128, 0.7);
        }

        .status.error .dot {
            background: var(--danger);
            box-shadow: 0 0 10px rgba(255, 79, 106, 0.7);
        }

        .status span {
            font-size: 11px;
        }

        .table-wrapper {
            max-height: 520px;
            overflow: auto;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(5, 7, 15, 0.9);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        thead {
            position: sticky;
            top: 0;
            z-index: 1;
        }

        thead tr {
            background: linear-gradient(180deg, rgba(31, 63, 115, 0.95), rgba(10, 16, 32, 0.98));
        }

        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: top;
        }

        th {
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 11px;
            color: #c3d3ff;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.01);
        }

        tbody tr:hover {
            background: rgba(79, 156, 255, 0.06);
        }

        .cell-small { white-space: nowrap; }

        .cell-email {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
        }

        .email-pill {
            padding: 4px 9px;
            border-radius: var(--radius-full);
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.7);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
        }

        .btn-copy {
            border: none;
            border-radius: var(--radius-full);
            padding: 3px 8px;
            font-size: 10px;
            cursor: pointer;
            background: rgba(148, 163, 184, 0.08);
            color: var(--muted);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-copy:hover {
            background: rgba(148, 163, 184, 0.24);
        }

        .mono {
            font-family: ui-monospace, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
        }

        .truncate {
            max-width: 340px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-cell {
            max-width: 500px;
            white-space: pre-wrap;
            line-height: 1.4;
            font-size: 12px;
        }

        .pill-boolean {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 7px;
            border-radius: var(--radius-full);
            font-size: 11px;
        }

        .pill-boolean.true {
            background: rgba(34, 197, 94, 0.15);
            color: #bbf7d0;
        }

        .pill-boolean.false {
            background: rgba(148, 163, 184, 0.12);
            color: #e5e7eb;
        }

        .toast {
            position: fixed;
            bottom: 18px;
            right: 18px;
            padding: 9px 14px;
            font-size: 12px;
            border-radius: var(--radius-full);
            background: #020617;
            color: var(--text);
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow: 0 18px 35px rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 999;
        }

        .toast.show { display: inline-flex; }

        .toast-icon { font-size: 14px; }

        .empty-state {
            padding: 24px 12px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        .empty-state span {
            display: block;
            margin-top: 6px;
        }

        .site-select {
            padding: 5px 10px;
            border-radius: var(--radius-full);
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(7, 10, 22, 0.95);
            color: var(--text);
            font-size: 12px;
        }

        @media (max-width: 900px) {
            body { padding: 16px; }

            .header { align-items: flex-start; }

            .meta-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            th:nth-child(5),
            td:nth-child(5),
            th:nth-child(6),
            td:nth-child(6),
            th:nth-child(7),
            td:nth-child(7),
            th:nth-child(8),
            td:nth-child(8),
            th:nth-child(9),
            td:nth-child(9),
            th:nth-child(10),
            td:nth-child(10),
            th:nth-child(11),
            td:nth-child(11),
            th:nth-child(12),
            td:nth-child(12) {
                display: none;
            }

            .message-cell {
                max-width: 260px;
            }
        }
    </style>
</head>
<body>
<div class="app">
    <div class="header">
        <div class="title">
            <h1>Inbox multi-pays</h1>
            <p>Alertes de recherche & messages de contact, centralisés par site.</p>
        </div>
        <button class="btn btn-primary" id="refreshBtn">
            ↻ Rafraîchir tous les sites
        </button>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:10px;flex-wrap:wrap;">
        <div class="tabs">
            <button class="tab active" data-target="search_alerts">
                🔔 <span>Search Alerts</span>
            </button>
            <button class="tab" data-target="contact_messages">
                ✉️ <span>Contact messages</span>
            </button>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <select id="siteFilter" class="site-select">
                <option value="all">Tous les sites</option>
                <option value="site-annonce.be">site-annonce.be</option>
                <option value="for-sale.ie">for-sale.ie</option>
                <option value="for-sale.co.uk">for-sale.co.uk</option>
                <option value="used.forsale">used.forsale</option>
                <option value="gebraucht-kaufen.de">gebraucht-kaufen.de</option>
                <option value="in-vendita.it">in-vendita.it</option>
            </select>
            <div class="badge">
                <span id="summaryText">En attente de chargement…</span>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="meta-bar">
            <div class="status" id="statusIndicator">
                <span class="dot"></span>
                <span>Idle</span>
            </div>
            <div style="display:flex;gap:6px;align-items:center;font-size:11px;">
                <span id="currentResourceLabel">Ressource : search_alerts</span>
                <span>•</span>
                <span id="rowCountLabel">0 lignes</span>
            </div>
        </div>

        <div class="table-wrapper" id="tableWrapper">
            <!-- tables injectées ici -->
        </div>
    </div>
</div>

<div class="toast" id="toast">
    <span class="toast-icon">✅</span>
    <span id="toastText">Copié</span>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // IMPORTANT: remplace par ton vrai token API (le même sur tous les sites)
    const API_TOKEN = 'FJKWrRptk7vOEv4jxuxvWQqJif26RIHN';

    const SITES = [
        { id: 'site-annonce.be',       label: 'site-annonce.be',       baseUrl: 'https://www.site-annonce.be/' },
        { id: 'for-sale.ie',           label: 'for-sale.ie',           baseUrl: 'https://www.for-sale.ie/' },
        { id: 'for-sale.co.uk',        label: 'for-sale.co.uk',        baseUrl: 'https://www.for-sale.co.uk/' },
        { id: 'used.forsale',          label: 'used.forsale',          baseUrl: 'https://www.used.forsale/' },
        { id: 'gebraucht-kaufen.de',   label: 'gebraucht-kaufen.de',   baseUrl: 'https://www.gebraucht-kaufen.de/' },
        { id: 'in-vendita.it',         label: 'in-vendita.it',         baseUrl: 'https://www.in-vendita.it/' },
    ];

    const refreshBtn = document.getElementById('refreshBtn');
    const statusIndicator = document.getElementById('statusIndicator');
    const summaryText = document.getElementById('summaryText');
    const currentResourceLabel = document.getElementById('currentResourceLabel');
    const rowCountLabel = document.getElementById('rowCountLabel');
    const tableWrapper = document.getElementById('tableWrapper');
    const toast = document.getElementById('toast');
    const toastText = document.getElementById('toastText');
    const tabs = document.querySelectorAll('.tab');
    const siteFilter = document.getElementById('siteFilter');

    let cache = {
        search_alerts: {},      // siteId -> array
        contact_messages: {}    // siteId -> array
    };

    function setStatus(text, type = 'ok') {
        statusIndicator.classList.toggle('error', type === 'error');
        const span = statusIndicator.querySelector('span:last-child');
        span.textContent = text;
    }

    function showToast(text) {
        toastText.textContent = text;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 1500);
    }

    async function fetchResourceForSite(resource, site) {
        const url = `${site.baseUrl}inc/api.php?resource=${encodeURIComponent(resource)}&token=${encodeURIComponent(API_TOKEN)}&limit=200`;
        try {
            const res = await fetch(url, { credentials: 'include' }); // si répertoire protégé par basic auth
            if (!res.ok) {
                throw new Error(site.label + ' - HTTP ' + res.status);
            }
            const json = await res.json();
            if (json.status !== 'ok') {
                throw new Error(site.label + ' - ' + (json.error || 'Réponse non OK'));
            }
            cache[resource][site.id] = json.data || [];
        } catch (e) {
            console.error(e);
            setStatus('Erreur sur ' + site.label, 'error');
            // on laisse simplement le site vide dans le cache
            cache[resource][site.id] = cache[resource][site.id] || [];
        }
    }

    function totalCount(resource) {
        return SITES.reduce((acc, s) => acc + ((cache[resource][s.id] || []).length), 0);
    }

    function renderTable(resource) {
        const selectedSiteId = siteFilter.value;
        currentResourceLabel.textContent = `Ressource : ${resource}`;

        let rows = [];
        if (selectedSiteId === 'all') {
            SITES.forEach(site => {
                const siteRows = cache[resource][site.id] || [];
                siteRows.forEach(r => {
                    rows.push({ ...r, _siteId: site.id });
                });
            });
        } else {
            const siteRows = cache[resource][selectedSiteId] || [];
            siteRows.forEach(r => {
                rows.push({ ...r, _siteId: selectedSiteId });
            });
        }

        rowCountLabel.textContent = `${rows.length} lignes`;

        if (rows.length === 0) {
            tableWrapper.innerHTML = `
                <div class="empty-state">
                    Aucune donnée disponible pour <strong>${resource}</strong> sur ce filtre de site.
                    <span>Vérifie l’API, les droits ou les entrées en base.</span>
                </div>`;
            return;
        }

        if (resource === 'search_alerts') {
            tableWrapper.innerHTML = renderSearchAlertsTable(rows);
        } else {
            tableWrapper.innerHTML = renderContactMessagesTable(rows);
        }

        attachCopyButtons();
    }

    function siteLabelFromId(id) {
        const s = SITES.find(x => x.id === id);
        return s ? s.label : id;
    }

    function renderSearchAlertsTable(rows) {
        return `
            <table>
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>ID</th>
                        <th>Keyword</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>User agent</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows.map(r => `
                        <tr>
                            <td class="cell-small mono">${escapeHtml(siteLabelFromId(r._siteId))}</td>
                            <td class="cell-small mono">${escapeHtml(r.id)}</td>
                            <td class="mono truncate" title="${escapeHtml(r.keyword || '')}">
                                ${escapeHtml(r.keyword || '')}
                            </td>
                            <td class="cell-email">
                                <span class="email-pill" data-email="${escapeAttr(r.email || '')}">
                                    ${escapeHtml(r.email || '')}
                                </span>
                                <button class="btn-copy" data-email="${escapeAttr(r.email || '')}">
                                    📋 Copier l’email
                                </button>
                            </td>
                            <td class="cell-small mono">${escapeHtml(r.created_at || '')}</td>
                            <td class="cell-small mono">${escapeHtml(r.updated_at || '')}</td>
                            <td class="truncate" title="${escapeHtml(r.user_agent || '')}">
                                ${escapeHtml(r.user_agent || '')}
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function renderContactMessagesTable(rows) {
        return `
            <table>
                <thead>
                    <tr>
                        <th>Site</th>
                        <th>ID</th>
                        <th>Created</th>
                        <th>Email</th>
                        <th>Message</th>                        
                    </tr>
                </thead>
                <tbody>
                    ${rows.map(r => `
                        <tr>
                            <td class="cell-small mono">${escapeHtml(siteLabelFromId(r._siteId))}</td>
                            <td class="cell-small mono">${escapeHtml(r.id)}</td>
                            <td class="cell-small mono">${escapeHtml(r.created_at || '')}</td>
                            <td class="cell-email">
                                <span class="email-pill" data-email="${escapeAttr(r.email || '')}">
                                    ${escapeHtml(r.email || '')}
                                </span>
                                <button class="btn-copy" data-email="${escapeAttr(r.email || '')}">
                                    📋 Copier l’email
                                </button>
                            </td>
                            <td class="message-cell">
                                ${escapeHtml(r.message || '')}
                            </td>                            
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    function renderBoolPill(value) {
        const val = String(value ?? '').toLowerCase();
        if (!val) return '';
        const isTrue = (val === '1' || val === 'true' || val === 'yes');
        return `
            <span class="pill-boolean ${isTrue ? 'true' : 'false'}">
                ${isTrue ? '● Oui' : '○ Non'}
            </span>
        `;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;');
    }

    function attachCopyButtons() {
        const buttons = tableWrapper.querySelectorAll('.btn-copy');
        buttons.forEach(btn => {
            btn.addEventListener('click', async () => {
                const email = btn.getAttribute('data-email') || '';
                if (!email) return;
                try {
                    await navigator.clipboard.writeText(email);
                    showToast('Email copié dans le presse-papier');
                } catch (e) {
                    console.error(e);
                    showToast('Impossible de copier');
                }
            });
        });
    }

    async function loadAll() {
        setStatus('Chargement des données…');
        summaryText.textContent = 'Chargement en cours sur tous les sites…';

        cache = { search_alerts: {}, contact_messages: {} };

        const tasks = [];
        for (const site of SITES) {
            tasks.push(fetchResourceForSite('search_alerts', site));
            tasks.push(fetchResourceForSite('contact_messages', site));
        }

        await Promise.all(tasks);

        const totalAlerts = totalCount('search_alerts');
        const totalContacts = totalCount('contact_messages');

        summaryText.textContent = `Search alerts : ${totalAlerts} • Contact messages : ${totalContacts}`;
        setStatus('Données à jour');

        const activeTab = document.querySelector('.tab.active');
        const resource = activeTab?.dataset.target || 'search_alerts';
        renderTable(resource);
    }

    refreshBtn.addEventListener('click', loadAll);

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const resource = tab.dataset.target;
            renderTable(resource);
        });
    });

    siteFilter.addEventListener('change', () => {
        const activeTab = document.querySelector('.tab.active');
        const resource = activeTab?.dataset.target || 'search_alerts';
        renderTable(resource);
    });

    // premier chargement
    loadAll();
});
</script>
</body>
</html>

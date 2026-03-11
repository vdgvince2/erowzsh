// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Construit l'URL partageable à partir du formulaire (paramètres non-vides uniquement).
 */
function getShareableUrl() {
    const form = document.getElementById('bargain-form');
    if (!form) return window.location.pathname;

    const params = new URLSearchParams();
    const data   = new FormData(form);

    for (const [key, value] of data.entries()) {
        if (value !== '' && key !== 'ajax') {
            params.set(key, value);
        }
    }

    const qs = params.toString();
    return window.location.pathname + (qs ? '?' + qs : '');
}

/**
 * Remplit le formulaire depuis les paramètres GET de l'URL courante.
 */
function populateFormFromUrl() {
    const form   = document.getElementById('bargain-form');
    const params = new URLSearchParams(window.location.search);
    if (!form || !params.toString()) return;

    for (const [key, value] of params.entries()) {
        const el = form.querySelector('[name="' + key + '"]');
        if (el) el.value = value;
    }
}


// ─── Fetch AJAX ───────────────────────────────────────────────────────────────

function fetchBargainResults() {
    const form    = document.getElementById('bargain-form');
    const results = document.getElementById('results');
    const loading = document.getElementById('loading');

    if (!form || !results) return;

    const formData = new FormData(form);
    formData.append('ajax', '1');

    loading && loading.classList.remove('hidden');
    if (results) results.style.opacity = '0.4';

    fetch('bargain', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.html !== undefined) {
            results.innerHTML = data.html;
            initCountdowns();
        } else {
            results.innerHTML =
                '<div class="bg-red-100 text-red-700 px-4 py-3 rounded">Unexpected response from server.</div>';
        }

        // Afficher les filtres après submit
        document.getElementById('refine-panel')?.classList.remove('hidden');

        // Mettre à jour l'URL (partageable + bouton retour)
        history.pushState({}, '', getShareableUrl());

        // Scroll vers les résultats sur mobile
        if (window.innerWidth < 768) {
            results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    })
    .catch(err => {
        console.error(err);
        results.innerHTML =
            '<div class="bg-red-100 text-red-700 px-4 py-3 rounded">Error while loading deals.</div>';
    })
    .finally(() => {
        loading && loading.classList.add('hidden');
        results.style.opacity = '1';
        updateActiveChips();
    });
}

// Accessible globalement (utilisé depuis bargain.php pour l'auto-search)
window.fetchBargainResults = fetchBargainResults;


// ─── Submit form ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bargain-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetchBargainResults();
    });
});


// ─── Bouton retour navigateur ─────────────────────────────────────────────────

window.addEventListener('popstate', function () {
    populateFormFromUrl();
    const q = new URLSearchParams(window.location.search).get('q');
    if (q) fetchBargainResults();
});


// ─── Quick chips ──────────────────────────────────────────────────────────────

document.addEventListener('click', function (e) {
    const chip = e.target.closest('.chip-filter');
    if (!chip) return;

    const form = document.getElementById('bargain-form');
    if (!form) return;

    const name  = chip.dataset.name;
    const value = chip.dataset.value;
    const input = form.querySelector('[name="' + name + '"]');

    if (input) {
        // Toggle : re-cliquer désactive le filtre
        const resetValue = (name === 'mode') ? 'standard' : '';
        input.value = (input.value === value) ? resetValue : value;
    }

    fetchBargainResults();
});

/**
 * Met en évidence les chips dont la valeur correspond à l'état du formulaire.
 */
function updateActiveChips() {
    const form = document.getElementById('bargain-form');
    if (!form) return;

    document.querySelectorAll('.chip-filter').forEach(chip => {
        const input = form.querySelector('[name="' + chip.dataset.name + '"]');
        const isActive = input && input.value === chip.dataset.value;

        chip.classList.toggle('bg-blue-600',    isActive);
        chip.classList.toggle('text-white',     isActive);
        chip.classList.toggle('border-blue-600', isActive);
        chip.classList.toggle('bg-white',       !isActive);
        chip.classList.toggle('border-gray-300', !isActive);
    });
}


// ─── Init sur page GET avec ?q=... ───────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);
    const q      = params.get('q');

    if (q) {
        populateFormFromUrl();

        const results  = document.getElementById('results');
        const hasCards = results && results.querySelectorAll('.bg-white.rounded-lg.shadow').length > 0;

        if (!hasCards) {
            // Pas de résultats côté serveur (API locale ko ou première visite) → fallback AJAX
            fetchBargainResults();
        } else if (window.innerWidth < 768) {
            // Résultats déjà là → scroll sur mobile
            setTimeout(() => results.scrollIntoView({ behavior: 'smooth', block: 'start' }), 150);
        }
    }

    updateActiveChips();
});


// ─── Countdown enchères ───────────────────────────────────────────────────────

function startCountdown(el) {
    const endTs = Date.parse(el.dataset.endtime);

    if (isNaN(endTs)) { el.textContent = 'Unknown end time'; return; }

    function update() {
        let diffSec = Math.floor((endTs - Date.now()) / 1000);

        if (diffSec <= 0) { el.textContent = 'Ended'; clearInterval(timer); return; }

        const days  = Math.floor(diffSec / 86400); diffSec %= 86400;
        const hours = Math.floor(diffSec / 3600);  diffSec %= 3600;
        const mins  = Math.floor(diffSec / 60);
        const secs  = diffSec % 60;

        let txt = '';
        if (days > 0) txt += days + 'd ';
        txt += String(hours).padStart(2, '0') + 'h '
             + String(mins).padStart(2, '0')  + 'm '
             + String(secs).padStart(2, '0')  + 's';

        el.textContent = txt;
    }

    update();
    const timer = setInterval(update, 1000);
}

function initCountdowns() {
    document.querySelectorAll('.auction-countdown').forEach(el => {
        if (!el.dataset.countdownInitialized) {
            el.dataset.countdownInitialized = '1';
            startCountdown(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', initCountdowns);


// ─── Toggle filtres mobile ────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('refine-toggle');
    const panel  = document.getElementById('refine-panel');
    const icon   = document.getElementById('refine-toggle-icon');

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');
            icon?.classList.toggle('rotate-180');
        });
    }
});

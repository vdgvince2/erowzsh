function fetchBargainResults() {
    const form = document.getElementById('bargain-form');
    const results = document.getElementById('results');
    const loading = document.getElementById('loading');

    if (!form || !results) return;

    const formData = new FormData(form);
    formData.append('ajax', '1'); // pour déclencher le mode JSON côté PHP

    loading && loading.classList.remove('hidden');
    if (results) results.style.opacity = '0.4';

    fetch('bargain', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data && data.html !== undefined) {
            results.innerHTML = data.html;
            initCountdowns();
        } else {
            results.innerHTML =
                '<div class="bg-red-100 text-red-700 px-4 py-3 rounded">Unexpected response from server.</div>';
        }
        /* afficher les filtres après submit */
        const refinePanel   = document.getElementById('refine-panel');
        refinePanel.classList.remove('hidden');
        /* aller à l'acnre results pour mobile */
        const el = document.getElementById('results');
        el.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    })
    .catch(err => {
        console.error(err);
        results.innerHTML =
            '<div class="bg-red-100 text-red-700 px-4 py-3 rounded">Error while loading deals.</div>';
    })
    .finally(() => {
        if (loading) loading.classList.add('hidden');
        if (results) results.style.opacity = '1';
    });
}

// ancien comportement : submit AJAX
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('bargain-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        fetchBargainResults();
    });
});

// optionnel, mais clair : fonction accessible en global
window.fetchBargainResults = fetchBargainResults;




/* compteur bid*/
function startCountdown(el) {
    const endStr = el.dataset.endtime;
    const endTs  = Date.parse(endStr); // parse ISO de l’API eBay

    if (isNaN(endTs)) {
        el.textContent = 'Unknown end time';
        return;
    }

    function update() {
        const now   = Date.now();
        let diffSec = Math.floor((endTs - now) / 1000);

        if (diffSec <= 0) {
            el.textContent = 'Ended';
            clearInterval(timer);
            return;
        }

        const days = Math.floor(diffSec / 86400);
        diffSec   %= 86400;
        const hours = Math.floor(diffSec / 3600);
        diffSec   %= 3600;
        const mins  = Math.floor(diffSec / 60);
        const secs  = diffSec % 60;

        let txt = '';
        if (days > 0) {
            txt += days + 'd ';
        }
        txt += String(hours).padStart(2, '0') + 'h '
            + String(mins).padStart(2, '0') + 'm '
            + String(secs).padStart(2, '0') + 's';

        el.textContent = txt;
    }

    update();
    const timer = setInterval(update, 1000);
}

function initCountdowns() {
    document.querySelectorAll('.auction-countdown').forEach(function (el) {
        // éviter de relancer un setInterval sur les mêmes éléments
        if (!el.dataset.countdownInitialized) {
            el.dataset.countdownInitialized = '1';
            startCountdown(el);
        }
    });
}

//  init au chargement initial
document.addEventListener('DOMContentLoaded', function () {
    initCountdowns();
});

/* dynamic filters for mobile */
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('refine-toggle');
    var panel  = document.getElementById('refine-panel');
    var icon   = document.getElementById('refine-toggle-icon');

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            panel.classList.toggle('hidden');

            if (icon) {
                icon.classList.toggle('rotate-180');
            }
        });
    }
});

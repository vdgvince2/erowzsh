/* Homepage — category-first deal browser */
(function () {
  'use strict';

  const strip        = document.getElementById('deal-strip');
  const stripWrapper = document.getElementById('deal-strip-wrapper');
  const catLabel     = document.getElementById('deal-strip-label');
  const catSeeAll    = document.getElementById('deal-strip-seeall');
  const cards        = document.querySelectorAll('.category-card');
  const filterPills  = document.querySelectorAll('.deal-filter-pill');

  // Exposed via PHP in homepage.php
  const API_URL = window.CAT_DEALS_URL || '/category-deals.php';

  let activeSlug    = '';
  let activeName    = '';
  let activeSeeAll  = '';
  let activeFilter  = 'all'; // 'all' | 'nobids'

  /* ── Countdown timers ─────────────────────────────── */
  function formatCountdown(endTimeStr) {
    const end  = new Date(endTimeStr).getTime();
    const now  = Date.now();
    const diff = end - now;
    if (diff <= 0) return 'Ended';
    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    const s = Math.floor((diff % 60_000) / 1_000);
    if (h > 0)  return `⏱ ${h}h ${m}m`;
    if (m > 0)  return `⏱ ${m}m ${s}s`;
    return `⏱ ${s}s`;
  }

  function getUrgencyClass(endTimeStr) {
    const diff = new Date(endTimeStr).getTime() - Date.now();
    if (diff < 300_000)  return 'text-red-500 font-bold';   // < 5 min
    if (diff < 3_600_000) return 'text-orange-500 font-semibold'; // < 1 h
    return 'text-orange-400 font-semibold';
  }

  const activeTimers = [];

  function clearTimers() {
    activeTimers.forEach(clearInterval);
    activeTimers.length = 0;
  }

  function initCountdowns() {
    document.querySelectorAll('.countdown-timer[data-endtime]').forEach(el => {
      const endTime = el.dataset.endtime;
      if (!endTime) return;

      const update = () => {
        el.textContent = formatCountdown(endTime);
        el.className = 'countdown-timer text-[10px] ' + getUrgencyClass(endTime);
      };

      update();
      activeTimers.push(setInterval(update, 1_000));
    });
  }

  /* ── Skeleton loader ──────────────────────────────── */
  function showSkeleton() {
    const skeletons = Array.from({ length: 10 }, () =>
      `<div class="bg-white rounded-xl overflow-hidden border border-gray-200 animate-pulse flex flex-col">
        <div class="aspect-square bg-gray-200"></div>
        <div class="p-2 flex flex-col gap-2">
          <div class="h-2 bg-gray-200 rounded w-1/2"></div>
          <div class="h-2 bg-gray-200 rounded w-full"></div>
          <div class="h-2 bg-gray-200 rounded w-3/4"></div>
          <div class="h-3 bg-gray-200 rounded w-1/3 mt-1"></div>
        </div>
      </div>`
    ).join('');
    strip.innerHTML = skeletons;
  }

  /* ── Active category state ────────────────────────── */
  function setActiveCategory(activeCard) {
    cards.forEach(c => {
      c.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-600', 'text-white');
      c.classList.add('bg-white', 'text-gray-800');
    });
    activeCard.classList.add('ring-2', 'ring-blue-500', 'bg-blue-600', 'text-white');
    activeCard.classList.remove('bg-white', 'text-gray-800');
  }

  /* ── Filter pill state ───────────────────────────── */
  function setActiveFilter(filter) {
    activeFilter = filter;
    filterPills.forEach(pill => {
      const isActive = pill.dataset.filter === filter;
      pill.classList.toggle('bg-blue-500',   isActive);
      pill.classList.toggle('text-white',    isActive);
      pill.classList.toggle('border-blue-500', isActive);
      pill.classList.toggle('bg-white',      !isActive);
      pill.classList.toggle('text-gray-600', !isActive);
      pill.classList.toggle('border-gray-300', !isActive);
    });
  }

  /* ── Fetch & render deals ─────────────────────────── */
  async function loadCategoryDeals(slug, name, seeAllUrl, filter) {
    filter = filter || activeFilter;
    clearTimers();
    showSkeleton();

    if (catLabel)  catLabel.textContent = name;
    if (catSeeAll) {
      catSeeAll.href        = seeAllUrl;
      catSeeAll.textContent = seeAllUrl ? name + ' →' : '';
    }

    const params = new URLSearchParams({ slug, limit: 30 });
    if (filter === 'nobids') params.set('nobids', '1');

    try {
      const res  = await fetch(`${API_URL}?${params}`);
      const data = await res.json();

      if (!res.ok || data.error) {
        strip.innerHTML = `<p class="col-span-full text-center text-gray-400 py-10 text-sm">${data.error || 'Could not load deals.'}</p>`;
        return;
      }

      strip.innerHTML = data.html || '';
      initCountdowns();
    } catch (err) {
      strip.innerHTML = '<p class="col-span-full text-center text-gray-400 py-10 text-sm">Network error — please try again.</p>';
    }
  }

  /* ── Filter pill click handler ───────────────────── */
  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      const filter = pill.dataset.filter;
      setActiveFilter(filter);
      if (activeSlug) loadCategoryDeals(activeSlug, activeName, activeSeeAll, filter);
    });
  });

  /* ── Category card click handler ─────────────────── */
  cards.forEach(card => {
    card.addEventListener('click', e => {
      e.preventDefault();
      activeSlug   = card.dataset.slug;
      activeName   = card.dataset.name;
      activeSeeAll = card.getAttribute('href');
      setActiveCategory(card);
      loadCategoryDeals(activeSlug, activeName, activeSeeAll);
    });
  });

  /* ── Auto-load first category on page ready ──────── */
  const firstCard = cards[0];
  if (firstCard && strip) {
    activeSlug   = firstCard.dataset.slug;
    activeName   = firstCard.dataset.name;
    activeSeeAll = firstCard.getAttribute('href');
    setActiveCategory(firstCard);
    loadCategoryDeals(activeSlug, activeName, activeSeeAll);
  }
})();

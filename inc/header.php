<?php
// load adsense in production only
if (!$isLocal && !isset($noAds)) echo $googleadsense_topBody;
// load custom CSS & tailwind
inline_css_for_page();
?>

<header class="w-full bg-white shadow">
  <div class="mx-auto px-4 py-2">
    <div class="flex items-center gap-3">

      <!-- Logo -->
      <a href="<?= $rootDomain.$base;?>" class="text-2xl font-bold text-blue-500 leading-none shrink-0">
        <?=$WebsiteName;?>
      </a>

      <!-- Search input -->
      <form role="search" aria-label="Site search" action="<?=$rootDomain.$base;?>s/bargain" method="get" class="flex-1 flex">
        <input type="text"
               name="q"
               data-hj-allow
               placeholder="<?= htmlspecialchars($label_search_placeholder ?? 'Search…', ENT_QUOTES, 'UTF-8') ?>"
               class="flex-1 min-w-0 rounded-l-xl border border-gray-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-400">
        <input type="hidden" name="mode" value="standard" />
        <button type="submit" aria-label="Search" class="rounded-r-xl bg-blue-500 px-3 py-2 text-white hover:bg-blue-600 shrink-0 flex items-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
          </svg>
        </button>
      </form>

      <!-- Hamburger (desktop) -->
      <button
        id="desktopMenuToggle"
        class="hidden md:flex w-9 h-9 items-center justify-center rounded-xl border border-gray-200 shadow-sm hover:bg-gray-50 shrink-0">
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
      </button>

      <!-- Hamburger (mobile) -->
      <button
        id="mobileMenuToggle"
        class="md:hidden w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 shadow-sm shrink-0">
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
      </button>

    </div>
  </div>
</header>

<!-- FULL MENU OVERLAY (vertical, desktop + mobile) -->
<nav id="mainMenu" class="fixed inset-0 z-40 hidden">
  <!-- Fond assombri -->
  <div id="menuBackdrop" class="absolute inset-0 bg-black/40"></div>

  <!-- Panneau latéral -->
  <div class="absolute right-0 top-0 h-full w-72 md:w-80 bg-white shadow-xl flex flex-col">
    <!-- Header du panneau -->
    <div class="flex items-center justify-between px-4 py-4 border-b border-gray-200">
      <span class="text-base font-semibold text-gray-800">
        <?=$WebsiteName;?>
      </span>
      <button id="menuClose" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Liens (verticaux) -->
    <div class="flex-1 overflow-y-auto">
      <div class="flex flex-col divide-y divide-gray-200 text-sm font-medium">
        <a href="<?=$rootDomain.$base;?>" class="px-4 py-3 hover:bg-gray-50">
          <?=$breadcrumb_home;?>
        </a>
        <a href="<?=$rootDomain.$base;?>s/bargain?mode=standard" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_search;?>
        </a>
        <a href="<?=$rootDomain.$base;?>s/bargain?mode=local" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_local;?>
        </a>
        <a href="<?=$rootDomain.$base;?>s/bargain?mode=misspelled" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_misspelled;?>
        </a>
        <a href="<?=$rootDomain.$base;?>s/bargain?mode=lastminute" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_lastminute;?>
        </a>
        <a href="https://www.facebook.com/profile.php?id=61584598651411" class="px-4 py-3 hover:bg-gray-50">
          Facebook Page
        </a>
        <a href="<?=$rootDomain.$base;?>mag/" class="px-4 py-3 hover:bg-gray-50">
          Blog
        </a>
        <a href="<?=$rootDomain.$base;?>s/contact" class="px-4 py-3 hover:bg-gray-50">
          <?=$Header_Link_Help;?>
        </a>
      </div>
    </div>

    <!-- Zone bas -->
    <div class="border-t border-gray-200 px-4 py-3 flex flex-col gap-2 text-sm">
      <a href="<?=$rootDomain.$base;?>s/myaccount" class="flex items-center gap-2 hover:text-blue-600">
        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
          <path d="M6 20a6 6 0 0 1 12 0" />
        </svg>
        <span><?=$Header_Link_myaccount;?></span>
      </a>
      <a href="<?=$rootDomain.$base;?>s/cart" class="flex items-center gap-2 hover:text-blue-600">
        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M3 3h2l.4 2M7 13h10L19 6H5.4M7 13 5.4 5M7 13l-1.293 2.293A1 1 0 0 0 6.618 17H18M9 21h.01M15 21h.01" />
        </svg>
        <span><?=$Header_Link_cart;?></span>
      </a>
    </div>
  </div>
</nav>

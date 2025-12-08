<?php 
// load adsense in production only
if (!$isLocal && !isset($noAds)) echo $googleadsense_topBody;
// load custom CSS & tailwind
inline_css_for_page();
?>

<header class="w-full bg-white shadow">
  <!-- Top bar -->
  <div class="bg-blue-500 text-white text-sm">
    <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-center gap-2 text-center">

      <!-- Location icon -->
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
           viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 21s-6-5.686-6-10a6 6 0 1 1 12 0c0 4.314-6 10-6 10z" />
        <circle cx="12" cy="11" r="2.5" />
      </svg>

      <a href="<?=$rootDomain.$base;?>bargain.php" class="inline-flex items-center gap-2 text-xl font-medium focus:outline-none">
        <?= $label_bargain_topheader;?>
      </a>

    </div>
  </div>

  <!-- Main header row -->
  <div class="mx-auto px-4 py-4">
    <div class="flex items-center justify-between gap-4">
      <!-- Left : title -->
      <div class="flex items-center">
        <a href="<?= $rootDomain.$base;?>" class="text-3xl md:text-4xl font-bold text-blue-500 leading-none">
          <?=$WebsiteName;?>
        </a>
      </div>

      <!-- Center : search desktop -->
      <form class="hidden md:flex flex-1 mx-4" action="<?=$rootDomain.$base;?>bargain.php#results" method="post">
        <div class="flex w-full bg-gray-50 rounded-full shadow-sm overflow-hidden">
          <input type="hidden" name="mode" value="standard" />
          <input
            type="text"
            placeholder="ipad, smartphone, ..."
            class="flex-1 px-4 py-3 text-gray-700 bg-gray-50 outline-none"
            name="keyword_search"
          />
          <button
            type="submit"
            class="flex items-center justify-center px-5 bg-blue-500 text-white"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
            </svg>
          </button>
        </div>
      </form>

      <!-- Right desktop icons -->
       
      <div class="hidden md:flex items-center gap-6">
        <?php /*
        <a href="<?= $rootDomain.$base;?>s/cart" class="relative">
          <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3h2l.4 2M7 13h10L19 6H5.4M7 13 5.4 5M7 13l-1.293 2.293A1 1 0 0 0 6.618 17H18M9 21h.01M15 21h.01" />
          </svg>
          <span class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center">0</span>
        </a>

        <a href="<?= $rootDomain.$base;?>s/contact">
          <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path d="M4 6h16v12H4z" />
            <path d="m4 7 8 6 8-6" />
          </svg>
        </a>

        <a href="<?= $rootDomain.$base;?>s/myaccount">
          <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24">
            <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
            <path d="M6 20a6 6 0 0 1 12 0" />
          </svg>
        </a>
*/ ?>
        <!-- Desktop hamburger -->
        <button
          id="desktopMenuToggle"
          class="w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 shadow-sm hover:bg-gray-50">
          <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 7h16M4 12h16M4 17h16" />
          </svg>
        </button>
      </div>

      <!-- Mobile hamburger -->
      <button
        id="mobileMenuToggle"
        class="md:hidden w-10 h-10 flex items-center justify-center rounded-xl border border-gray-200 shadow-sm">
        <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 7h16M4 12h16M4 17h16" />
        </svg>
      </button>
    </div>

    <!-- Mobile search -->
    <form class="mt-3 md:hidden" action="<?=$rootDomain.$base;?>bargain.php#results" method="post">
      <div class="flex w-full bg-gray-50 rounded-full shadow-sm overflow-hidden">
        <input type="hidden" name="mode" value="standard" />
        <input type="text" name="keyword_search" placeholder="ipad, smartphone, ..."
               class="flex-1 px-4 py-3 text-gray-700 bg-gray-50 outline-none" />
        <button class="flex items-center justify-center px-5 bg-blue-500 text-white">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
            <path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15z" />
          </svg>
        </button>
      </div>
    </form>

    <!-- Mobile actions -->
    <div id="mobileActions" class="md:hidden hidden mt-3 flex items-center gap-6">
      <!-- Cart -->
      <button class="relative">
        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 3h2l.4 2M7 13h10L19 6H5.4M7 13 5.4 5M7 13l-1.293 2.293A1 1 0 0 0 6.618 17H18M9 21h.01M15 21h.01" />
        </svg>
        <span class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center">0</span>
      </button>

      <!-- Mail -->
      <button>
        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 6h16v12H4z" />
          <path d="m4 7 8 6 8-6" />
        </svg>
      </button>

      <!-- User -->
      <button>
        <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
          <path d="M6 20a6 6 0 0 1 12 0" />
        </svg>
      </button>
    </div>
  </div>
</header>

<!-- FULL MENU (desktop + mobile) -->
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
        <a href="<?=$rootDomain.$base;?>bargain.php?mode=standard" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_search;?>
        </a>                                
        <a href="<?=$rootDomain.$base;?>bargain.php?mode=local" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_local;?>
        </a>
        <a href="<?=$rootDomain.$base;?>bargain.php?mode=misspelled" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_misspelled;?>
        </a>
        <a href="<?=$rootDomain.$base;?>bargain.php?mode=lastminute" class="px-4 py-3 hover:bg-gray-50">
          <?=$label_bargain_lastminute;?>
        </a>                        
        <a href="https://www.facebook.com/profile.php?id=61584598651411" class="px-4 py-3 hover:bg-gray-50">
          Facebook Page
        </a>
        <a href="<?=$rootDomain.$base;?>s/contact" class="px-4 py-3 hover:bg-gray-50">
          <?=$Header_Link_Help;?>
        </a>
      </div>
    </div>

    <!-- Zone bas (optionnelle : liens compte, etc.) -->
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

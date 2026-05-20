<?php $condition = null; ?>

<!-- FILTERS BAR -->
<div class="bg-white/90 backdrop-blur-sm border border-gray-100 rounded-lg shadow px-3 py-2 mb-3 sticky top-[3.5rem] z-30">

    <!-- Desktop : pills + dropdowns inline (≥ md) -->
    <div class="hidden md:flex flex-wrap items-center gap-x-5 gap-y-2">
        <?php foreach ($array_advices as $adviceBlock => $advice_key): ?>
        <div class="flex items-center gap-1.5">
            <span class="text-[10px] font-semibold text-blue-500 uppercase tracking-wide whitespace-nowrap">
                <?= ucfirst($translate_advices_labels[$adviceBlock]) ?>
            </span>

            <?php if (count($advice_key) > 3): ?>
            <select class="clickable-product text-xs border border-gray-200 rounded-full px-2.5 py-1 bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-400 hover:border-gray-300">
                <option>—</option>
                <?php foreach ($advice_key as $advice => $filterKey):
                    if ($adviceBlock === 'condition' && in_array($filterKey, ['conditionNew', 'conditionUsed'])) {
                        $condition = $filterKey;
                        $filterKey = null;
                    }
                    $url = base64_encode(str_replace(
                        'customid=' . $countryCode . '_',
                        'customid=' . $countryCode . '_FILTERDESKTOP_',
                        tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition)
                    ));
                ?>
                <option data-url="<?= $url ?>"><?= htmlspecialchars($advice, ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>

            <?php else: ?>
            <?php foreach ($advice_key as $advice => $filterKey):
                if ($adviceBlock === 'condition' && in_array($filterKey, ['conditionNew', 'conditionUsed'])) {
                    $condition = $filterKey;
                    $filterKey = null;
                }
                $url = base64_encode(str_replace(
                    'customid=' . $countryCode . '_',
                    'customid=' . $countryCode . '_FILTERDESKTOP_',
                    tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition)
                ));
            ?>
            <button type="button"
                    class="clickable-product text-xs px-2.5 py-1 rounded-full border border-gray-200 hover:border-blue-400 hover:text-blue-600 text-gray-600 transition cursor-pointer whitespace-nowrap"
                    data-url="<?= $url ?>">
                <?= htmlspecialchars($advice, ENT_QUOTES) ?>
            </button>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Mobile : bouton FILTRES + condition inline + panel déroulant (< md) -->
    <?php
    $condition = null;
    $conditionMobileOpts = [];
    foreach (($array_advices['condition'] ?? []) as $advice => $filterKey) {
        if (in_array($filterKey, ['conditionNew', 'conditionUsed'])) {
            $condition = $filterKey;
            $filterKey = null;
        }
        $conditionMobileOpts[] = [
            'label' => $advice,
            'url'   => base64_encode(str_replace(
                'customid=' . $countryCode . '_',
                'customid=' . $countryCode . '_FILTERMOBILE_',
                tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition)
            )),
        ];
    }
    $condition = null;
    ?>
    <div class="md:hidden">
        <div class="flex items-center gap-2">
            <button id="filter-toggle-btn" type="button"
                    class="flex items-center gap-1.5 text-xs font-semibold text-blue-600 border border-blue-200 rounded-full px-3 py-1.5 bg-blue-50 active:bg-blue-100 transition whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M11 16h2"/>
                </svg>
                <?= htmlspecialchars(strtoupper($label_filters ?? 'Filters'), ENT_QUOTES) ?>
                <svg id="filter-chevron" xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <select class="clickable-product text-xs border border-gray-200 rounded-full px-2.5 py-1.5 bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-400">
                <option><?= htmlspecialchars(ucfirst($translate_advices_labels['condition'] ?? 'condition'), ENT_QUOTES) ?></option>
                <?php foreach ($conditionMobileOpts as $opt): ?>
                <option data-url="<?= $opt['url'] ?>"><?= htmlspecialchars($opt['label'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="filter-mobile-panel" class="hidden mt-2 grid grid-cols-2 gap-x-3 gap-y-3">
            <?php foreach ($array_advices as $adviceBlock => $advice_key): ?>
            <div>
                <p class="text-[10px] font-semibold text-blue-500 uppercase tracking-wide mb-1">
                    <?= ucfirst($translate_advices_labels[$adviceBlock]) ?>
                </p>
                <select class="clickable-product w-full text-xs border border-gray-200 rounded-lg px-2 py-1.5 bg-white text-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-400">
                    <option>—</option>
                    <?php foreach ($advice_key as $advice => $filterKey):
                        if ($adviceBlock === 'condition' && in_array($filterKey, ['conditionNew', 'conditionUsed'])) {
                            $condition = $filterKey;
                            $filterKey = null;
                        }
                        $url = base64_encode(str_replace(
                            'customid=' . $countryCode . '_',
                            'customid=' . $countryCode . '_FILTERMOBILE_',
                            tracking_link_builder($ebaySearchKeyword, $countryCode, null, $filterKey, $condition)
                        ));
                    ?>
                    <option data-url="<?= $url ?>"><?= htmlspecialchars($advice, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<script>
(function () {
    var btn   = document.getElementById('filter-toggle-btn');
    var panel = document.getElementById('filter-mobile-panel');
    var chev  = document.getElementById('filter-chevron');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
        var open = !panel.classList.contains('hidden');
        panel.classList.toggle('hidden', open);
        chev.style.transform = open ? '' : 'rotate(180deg)';
    });
})();
</script>

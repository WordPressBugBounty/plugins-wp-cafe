/* global jQuery, wpcafeNutrition */
(function ($) {
    'use strict';

    var STATES = ['', 'contains', 'may_contain'];

    $(function () {
        initRepeaters();
        initAllergenChips();
    });

    function initRepeaters() {
        $('.wpcafe-nutrition-panel').on('click', '.wpcafe-repeater__add', function () {
            var $repeater = $(this).closest('.wpcafe-repeater');
            var $rows = $repeater.find('.wpcafe-repeater__rows');
            var template = $repeater.find('.wpcafe-repeater__template').html();
            if (!template) return;

            var nextIndex = $rows.children('.wpcafe-repeater__row').length;
            var html = template.replace(/__INDEX__/g, nextIndex);
            $rows.append(html);
        });

        $('.wpcafe-nutrition-panel').on('click', '.wpcafe-repeater__remove', function () {
            $(this).closest('.wpcafe-repeater__row').remove();
        });
    }

    function initAllergenChips() {
        var i18n = (window.wpcafeNutrition && window.wpcafeNutrition.i18n) || {
            off: 'off', contains: 'Contains', mayContain: 'May Contain'
        };

        $('.wpcafe-nutrition-panel .wpcafe-allergen').each(function () {
            applyChipLabel($(this), i18n);
        });

        $('.wpcafe-nutrition-panel').on('click', '.wpcafe-allergen__btn', function (e) {
            e.preventDefault();
            var $chip = $(this).closest('.wpcafe-allergen');
            var current = $chip.attr('data-state') || '';
            var next = STATES[(STATES.indexOf(current) + 1) % STATES.length];
            $chip.attr('data-state', next);
            $chip.find('input[type="hidden"]').val(next);
            applyChipLabel($chip, i18n);
        });
    }

    function applyChipLabel($chip, i18n) {
        var state = $chip.attr('data-state') || '';
        var stateLabel = '';
        if (state === 'contains') stateLabel = i18n.contains;
        else if (state === 'may_contain') stateLabel = i18n.mayContain;
        $chip.find('.wpcafe-allergen__state').text(stateLabel);
    }
})(jQuery);

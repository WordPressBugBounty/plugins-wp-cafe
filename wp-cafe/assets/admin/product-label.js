/**
 * WP Cafe — Product Label term editor
 * Wires color picker, dashicons grid + search, SVG media uploader,
 * and the live preview on the wpcafe_product_label term add/edit screens.
 */
(function ($) {
    'use strict';

    if (typeof wpcafeProductLabel === 'undefined') {
        return;
    }

    var dashicons = wpcafeProductLabel.dashicons || [];
    var i18n = wpcafeProductLabel.i18n || {};

    function $f(name) {
        return $('[name="wpcafe_label_' + name + '"]');
    }

    function getValues() {
        return {
            display: $f('display').val() || 'name',
            fg: $f('fg').val() || '#FFFFFF',
            bg: $f('bg').val() || '#1F2937',
            iconType: ($('[name="wpcafe_label_icon_type"]:checked').val()) || 'dashicons',
            iconValue: $f('icon_value').val() || ''
        };
    }

    function buildIconHtml(values) {
        if (!values.iconValue) {
            return '';
        }
        if (values.iconType === 'svg') {
            // we don't have URL here unless preview wrap stored it; use the SVG preview img if any
            var $svgPreview = $('.wpcafe-label-svg-preview img');
            if ($svgPreview.length) {
                return '<img class="wpc-product-label__icon wpc-product-label__icon--svg" src="' + $svgPreview.attr('src') + '" alt="" />';
            }
            return '';
        }
        return '<span class="wpc-product-label__icon dashicons ' + values.iconValue + '"></span>';
    }

    function updatePreview() {
        var v = getValues();
        var $wrap = $('.wpcafe-label-preview-wrap');
        if (!$wrap.length) return;

        var $preview = $wrap.find('.wpc-product-label, .wpcafe-label-preview');
        if (!$preview.length) return;

        // Build name + icon based on display mode
        var icon = buildIconHtml(v);
        var name = $('#tag-name').val() || $('#name').val() || i18n.preview || 'Preview';
        var inner = '';
        var display = v.display;
        if ((display === 'icon' || display === 'icon_name' || display === 'name_icon') && !icon) {
            display = 'name';
        }
        switch (display) {
            case 'icon':
                inner = icon;
                break;
            case 'icon_name':
                inner = icon + '<span class="wpc-product-label__name">' + escapeHtml(name) + '</span>';
                break;
            case 'name_icon':
                inner = '<span class="wpc-product-label__name">' + escapeHtml(name) + '</span>' + icon;
                break;
            default:
                inner = '<span class="wpc-product-label__name">' + escapeHtml(name) + '</span>';
        }

        var html = '<span class="wpc-product-label wpc-product-label--' + display + '" style="background-color:' + v.bg + ';color:' + v.fg + ';">' + inner + '</span>';
        $wrap.html(html);
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c];
        });
    }

    function initColorPickers() {
        $('.wpcafe-label-color').wpColorPicker({
            change: function () {
                setTimeout(updatePreview, 50);
            },
            clear: updatePreview
        });
    }

    function buildDashiconsGrid(filter) {
        var $grid = $('.wpcafe-label-icon-grid');
        if (!$grid.length) return;
        $grid.empty();

        var f = (filter || '').trim().toLowerCase();
        var matched = 0;
        var current = $f('icon_value').val();

        dashicons.forEach(function (cls) {
            if (f && cls.indexOf(f) === -1) return;
            matched++;
            var selected = (cls === current) ? ' is-selected' : '';
            $grid.append(
                '<button type="button" class="wpcafe-label-icon-cell' + selected + '" data-icon="' + cls + '" title="' + cls + '">' +
                '<span class="dashicons ' + cls + '"></span>' +
                '</button>'
            );
        });

        if (!matched) {
            $grid.append('<p class="description">' + (i18n.noResults || 'No icons found.') + '</p>');
        }
    }

    function initDashiconsPicker() {
        buildDashiconsGrid('');

        $(document).on('input', '.wpcafe-label-icon-search', function () {
            buildDashiconsGrid($(this).val());
        });

        $(document).on('click', '.wpcafe-label-icon-cell', function (e) {
            e.preventDefault();
            var cls = $(this).data('icon');
            $f('icon_value').val(cls);
            $('.wpcafe-label-icon-cell').removeClass('is-selected');
            $(this).addClass('is-selected');
            updatePreview();
        });

        $(document).on('change', '[name="wpcafe_label_icon_type"]', function () {
            var type = $(this).val();
            $('.wpcafe-label-icon-panel--dashicons').toggle(type === 'dashicons');
            $('.wpcafe-label-icon-panel--svg').toggle(type === 'svg');
            // Reset value when switching types so old class doesn't leak as attachment id.
            $f('icon_value').val('');
            $('.wpcafe-label-icon-cell').removeClass('is-selected');
            $('.wpcafe-label-svg-preview').empty();
            $('.wpcafe-label-svg-clear').hide();
            updatePreview();
        });
    }

    function initSvgUploader() {
        var frame;
        $(document).on('click', '.wpcafe-label-svg-upload', function (e) {
            e.preventDefault();
            if (frame) {
                frame.open();
                return;
            }
            frame = wp.media({
                title: i18n.svgTitle || 'Select SVG',
                button: { text: i18n.useSvg || 'Use this SVG' },
                library: { type: ['image/svg+xml', 'image'] },
                multiple: false
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $f('icon_value').val(attachment.id);
                $('.wpcafe-label-svg-preview').html('<img src="' + attachment.url + '" alt="" style="max-width:48px;height:auto;" />');
                $('.wpcafe-label-svg-clear').show();
                updatePreview();
            });
            frame.open();
        });

        $(document).on('click', '.wpcafe-label-svg-clear', function (e) {
            e.preventDefault();
            $f('icon_value').val('');
            $('.wpcafe-label-svg-preview').empty();
            $(this).hide();
            updatePreview();
        });
    }

    function initLivePreview() {
        $(document).on('change input', '#wpcafe-label-display, #tag-name, #name', updatePreview);

        // After Ajax submit (on add-form) WordPress repopulates the form;
        // re-build the dashicons grid and re-bind preview each time.
        $(document).ajaxComplete(function () {
            buildDashiconsGrid($('.wpcafe-label-icon-search').val() || '');
            initColorPickers();
            updatePreview();
        });
    }

    $(function () {
        initColorPickers();
        initDashiconsPicker();
        initSvgUploader();
        initLivePreview();
        updatePreview();
    });
})(jQuery);

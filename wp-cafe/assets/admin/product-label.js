/* global wpcafeProductLabel, wp, jQuery */
/**
 * WP Cafe — Product Label term editor.
 * Wires the color picker, dashicons grid + search, SVG media uploader, and the
 * live preview on the wpcafe_product_label term add/edit screens.
 *
 * Mostly vanilla JS. jQuery ($) is retained ONLY where there is no vanilla
 * equivalent: WP's wpColorPicker plugin, and jQuery's global ajaxComplete event
 * (WordPress's term add-form submits through its own jQuery AJAX).
 */
( function ( $ ) {
	'use strict';

	if ( typeof wpcafeProductLabel === 'undefined' ) {
		return;
	}

	var dashicons = wpcafeProductLabel.dashicons || [];
	var i18n = wpcafeProductLabel.i18n || {};

	function f( name ) {
		return document.querySelector( '[name="wpcafe_label_' + name + '"]' );
	}

	function fval( name ) {
		var el = f( name );
		return el ? el.value : '';
	}

	function getValues() {
		var checkedType = document.querySelector( '[name="wpcafe_label_icon_type"]:checked' );
		return {
			display: fval( 'display' ) || 'name',
			fg: fval( 'fg' ) || '#FFFFFF',
			bg: fval( 'bg' ) || '#1F2937',
			iconType: ( checkedType && checkedType.value ) || 'dashicons',
			iconValue: fval( 'icon_value' ) || ''
		};
	}

	function buildIconHtml( values ) {
		if ( ! values.iconValue ) {
			return '';
		}
		if ( values.iconType === 'svg' ) {
			// No URL here unless the preview img stored it; reuse that img's src.
			var svgPreview = document.querySelector( '.wpcafe-label-svg-preview img' );
			if ( svgPreview ) {
				return '<img class="wpc-product-label__icon wpc-product-label__icon--svg" src="' + svgPreview.getAttribute( 'src' ) + '" alt="" />';
			}
			return '';
		}
		return '<span class="wpc-product-label__icon dashicons ' + values.iconValue + '"></span>';
	}

	function updatePreview() {
		var v = getValues();
		var wrap = document.querySelector( '.wpcafe-label-preview-wrap' );
		if ( ! wrap ) {
			return;
		}

		var preview = wrap.querySelector( '.wpc-product-label, .wpcafe-label-preview' );
		if ( ! preview ) {
			return;
		}

		var icon = buildIconHtml( v );
		var nameEl = document.getElementById( 'tag-name' ) || document.getElementById( 'name' );
		var name = ( nameEl && nameEl.value ) || i18n.preview || 'Preview';
		var inner = '';
		var display = v.display;
		if ( ( display === 'icon' || display === 'icon_name' || display === 'name_icon' ) && ! icon ) {
			display = 'name';
		}
		switch ( display ) {
			case 'icon':
				inner = icon;
				break;
			case 'icon_name':
				inner = icon + '<span class="wpc-product-label__name">' + escapeHtml( name ) + '</span>';
				break;
			case 'name_icon':
				inner = '<span class="wpc-product-label__name">' + escapeHtml( name ) + '</span>' + icon;
				break;
			default:
				inner = '<span class="wpc-product-label__name">' + escapeHtml( name ) + '</span>';
		}

		wrap.innerHTML = '<span class="wpc-product-label wpc-product-label--' + display + '" style="background-color:' + v.bg + ';color:' + v.fg + ';">' + inner + '</span>';
	}

	function escapeHtml( s ) {
		return String( s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ c ];
		} );
	}

	// jQuery bridge: WP's wpColorPicker plugin has no vanilla equivalent.
	function initColorPickers() {
		$( '.wpcafe-label-color' ).wpColorPicker( {
			change: function () {
				setTimeout( updatePreview, 50 );
			},
			clear: updatePreview
		} );
	}

	function buildDashiconsGrid( filter ) {
		var grid = document.querySelector( '.wpcafe-label-icon-grid' );
		if ( ! grid ) {
			return;
		}
		grid.innerHTML = '';

		var needle = ( filter || '' ).trim().toLowerCase();
		var matched = 0;
		var current = fval( 'icon_value' );

		dashicons.forEach( function ( cls ) {
			if ( needle && cls.indexOf( needle ) === -1 ) {
				return;
			}
			matched++;
			var selected = ( cls === current ) ? ' is-selected' : '';
			grid.insertAdjacentHTML( 'beforeend',
				'<button type="button" class="wpcafe-label-icon-cell' + selected + '" data-icon="' + cls + '" title="' + cls + '">' +
				'<span class="dashicons ' + cls + '"></span>' +
				'</button>'
			);
		} );

		if ( ! matched ) {
			grid.insertAdjacentHTML( 'beforeend', '<p class="description">' + ( i18n.noResults || 'No icons found.' ) + '</p>' );
		}
	}

	function initDashiconsPicker() {
		buildDashiconsGrid( '' );

		document.addEventListener( 'input', function ( e ) {
			var searchEl = e.target.closest( '.wpcafe-label-icon-search' );
			if ( searchEl ) {
				buildDashiconsGrid( searchEl.value );
			}
		} );

		document.addEventListener( 'click', function ( e ) {
			var cell = e.target.closest( '.wpcafe-label-icon-cell' );
			if ( ! cell ) {
				return;
			}
			e.preventDefault();
			var iconValue = f( 'icon_value' );
			if ( iconValue ) {
				iconValue.value = cell.dataset.icon;
			}
			document.querySelectorAll( '.wpcafe-label-icon-cell' ).forEach( function ( c ) {
				c.classList.remove( 'is-selected' );
			} );
			cell.classList.add( 'is-selected' );
			updatePreview();
		} );

		document.addEventListener( 'change', function ( e ) {
			var radio = e.target.closest( '[name="wpcafe_label_icon_type"]' );
			if ( ! radio ) {
				return;
			}
			var type = radio.value;
			var dashPanel = document.querySelector( '.wpcafe-label-icon-panel--dashicons' );
			var svgPanel = document.querySelector( '.wpcafe-label-icon-panel--svg' );
			if ( dashPanel ) {
				dashPanel.style.display = ( type === 'dashicons' ) ? '' : 'none';
			}
			if ( svgPanel ) {
				svgPanel.style.display = ( type === 'svg' ) ? '' : 'none';
			}
			// Reset value when switching types so an old class doesn't leak as an attachment id.
			var iconValue = f( 'icon_value' );
			if ( iconValue ) {
				iconValue.value = '';
			}
			document.querySelectorAll( '.wpcafe-label-icon-cell' ).forEach( function ( c ) {
				c.classList.remove( 'is-selected' );
			} );
			var svgPreview = document.querySelector( '.wpcafe-label-svg-preview' );
			if ( svgPreview ) {
				svgPreview.innerHTML = '';
			}
			var svgClear = document.querySelector( '.wpcafe-label-svg-clear' );
			if ( svgClear ) {
				svgClear.style.display = 'none';
			}
			updatePreview();
		} );
	}

	function initSvgUploader() {
		var frame;
		document.addEventListener( 'click', function ( e ) {
			var uploadBtn = e.target.closest( '.wpcafe-label-svg-upload' );
			if ( uploadBtn ) {
				e.preventDefault();
				if ( frame ) {
					frame.open();
					return;
				}
				frame = wp.media( {
					title: i18n.svgTitle || 'Select SVG',
					button: { text: i18n.useSvg || 'Use this SVG' },
					library: { type: [ 'image/svg+xml', 'image' ] },
					multiple: false
				} );
				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();
					var iconValue = f( 'icon_value' );
					if ( iconValue ) {
						iconValue.value = attachment.id;
					}
					var svgPreview = document.querySelector( '.wpcafe-label-svg-preview' );
					if ( svgPreview ) {
						svgPreview.innerHTML = '<img src="' + attachment.url + '" alt="" style="max-width:48px;height:auto;" />';
					}
					var svgClear = document.querySelector( '.wpcafe-label-svg-clear' );
					if ( svgClear ) {
						svgClear.style.display = '';
					}
					updatePreview();
				} );
				frame.open();
				return;
			}

			var clearBtn = e.target.closest( '.wpcafe-label-svg-clear' );
			if ( clearBtn ) {
				e.preventDefault();
				var iconValue = f( 'icon_value' );
				if ( iconValue ) {
					iconValue.value = '';
				}
				var svgPreview = document.querySelector( '.wpcafe-label-svg-preview' );
				if ( svgPreview ) {
					svgPreview.innerHTML = '';
				}
				clearBtn.style.display = 'none';
				updatePreview();
			}
		} );
	}

	function onPreviewFieldEvent( e ) {
		if ( e.target.closest( '#wpcafe-label-display, #tag-name, #name' ) ) {
			updatePreview();
		}
	}

	function initLivePreview() {
		document.addEventListener( 'change', onPreviewFieldEvent );
		document.addEventListener( 'input', onPreviewFieldEvent );

		// jQuery bridge: after WP's add-form AJAX repopulates the fields, rebuild the
		// grid, re-init the color pickers, and refresh the preview. ajaxComplete is the
		// only hook for WordPress's own jQuery AJAX — no vanilla equivalent.
		$( document ).ajaxComplete( function () {
			var search = document.querySelector( '.wpcafe-label-icon-search' );
			buildDashiconsGrid( ( search && search.value ) || '' );
			initColorPickers();
			updatePreview();
		} );
	}

	$( function () {
		initColorPickers();
		initDashiconsPicker();
		initSvgUploader();
		initLivePreview();
		updatePreview();
	} );
} )( jQuery );

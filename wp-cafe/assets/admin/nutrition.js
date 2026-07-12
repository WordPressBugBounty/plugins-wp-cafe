/* global wpcafeNutrition */
/**
 * Product nutrition admin panel: repeater add/remove + allergen chip state cycle.
 * Converted from jQuery to vanilla JS — admin-only, no WooCommerce events.
 */
( function () {
	'use strict';

	const STATES = [ '', 'contains', 'may_contain' ];

	function init() {
		initRepeaters();
		initAllergenChips();
	}

	function initRepeaters() {
		// Delegated on document: rows are added/removed dynamically.
		document.addEventListener( 'click', function ( e ) {
			const addBtn = e.target.closest( '.wpcafe-nutrition-panel .wpcafe-repeater__add' );
			if ( addBtn ) {
				const repeater = addBtn.closest( '.wpcafe-repeater' );
				if ( ! repeater ) {
					return;
				}
				const rows = repeater.querySelector( '.wpcafe-repeater__rows' );
				const template = repeater.querySelector( '.wpcafe-repeater__template' );
				const tpl = template ? template.innerHTML : '';
				if ( ! rows || ! tpl ) {
					return;
				}

				const nextIndex = rows.querySelectorAll( '.wpcafe-repeater__row' ).length;
				rows.insertAdjacentHTML( 'beforeend', tpl.replace( /__INDEX__/g, nextIndex ) );
				return;
			}

			const removeBtn = e.target.closest( '.wpcafe-nutrition-panel .wpcafe-repeater__remove' );
			if ( removeBtn ) {
				const row = removeBtn.closest( '.wpcafe-repeater__row' );
				if ( row ) {
					row.remove();
				}
			}
		} );
	}

	function initAllergenChips() {
		const i18n = ( window.wpcafeNutrition && window.wpcafeNutrition.i18n ) || {
			off: 'off',
			contains: 'Contains',
			mayContain: 'May Contain',
		};

		document.querySelectorAll( '.wpcafe-nutrition-panel .wpcafe-allergen' ).forEach( function ( chip ) {
			applyChipLabel( chip, i18n );
		} );

		document.addEventListener( 'click', function ( e ) {
			const btn = e.target.closest( '.wpcafe-nutrition-panel .wpcafe-allergen__btn' );
			if ( ! btn ) {
				return;
			}
			e.preventDefault();
			const chip = btn.closest( '.wpcafe-allergen' );
			if ( ! chip ) {
				return;
			}
			const current = chip.getAttribute( 'data-state' ) || '';
			const next = STATES[ ( STATES.indexOf( current ) + 1 ) % STATES.length ];
			chip.setAttribute( 'data-state', next );
			const hidden = chip.querySelector( 'input[type="hidden"]' );
			if ( hidden ) {
				hidden.value = next;
			}
			applyChipLabel( chip, i18n );
		} );
	}

	function applyChipLabel( chip, i18n ) {
		const state = chip.getAttribute( 'data-state' ) || '';
		let stateLabel = '';
		if ( state === 'contains' ) {
			stateLabel = i18n.contains;
		} else if ( state === 'may_contain' ) {
			stateLabel = i18n.mayContain;
		}
		const stateEl = chip.querySelector( '.wpcafe-allergen__state' );
		if ( stateEl ) {
			stateEl.textContent = stateLabel;
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

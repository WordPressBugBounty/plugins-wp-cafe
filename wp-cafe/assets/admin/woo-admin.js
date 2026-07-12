/* global wpcWooAdmin */
/**
 * WP Cafe WooCommerce admin chrome: collapsible sidebar, mobile sidebar overlay,
 * and "wpcafe=true" link parameter preservation across admin navigation.
 *
 * Extracted from the inline jQuery script in core/admin/woo-admin.php and
 * converted to vanilla JS. The admin URL (previously interpolated into the
 * inline string) now arrives via wp_localize_script as wpcWooAdmin.adminUrl.
 */
( function () {
	'use strict';

	var ADMIN_URL = ( window.wpcWooAdmin && window.wpcWooAdmin.adminUrl ) || '';

	function initSidebar() {
		var sidebar = document.getElementById( 'woo-wpc-sidebar' );
		var toggleBtn = document.getElementById( 'sidebar-toggle' );
		var wpcontent = document.getElementById( 'wpcontent' );
		var topbar = document.querySelector( '.woo-wpc-topbar-wrapper' );

		if ( ! sidebar ) {
			return;
		}

		if ( toggleBtn ) {
			toggleBtn.addEventListener( 'click', function () {
				sidebar.classList.toggle( 'collapsed' );
				document.body.classList.toggle( 'sidebar-collapsed' );

				var collapsed = sidebar.classList.contains( 'collapsed' );
				if ( wpcontent ) {
					wpcontent.style.marginLeft = collapsed ? '80px' : '220px';
				}
				if ( topbar ) {
					topbar.style.width = collapsed ? 'calc(100% - 120px)' : 'calc(100% - 260px)';
					topbar.style.marginLeft = collapsed ? '80px' : '220px';
				}
			} );
		}

		// Delegated on the sidebar: links may re-render.
		sidebar.addEventListener( 'click', function ( e ) {
			var link = e.target.closest( '.sidebar-link' );
			if ( ! link || ! sidebar.contains( link ) ) {
				return;
			}

			var href = link.getAttribute( 'href' );
			var isExternal = link.classList.contains( 'external-link' );
			var isExcluded = link.getAttribute( 'data-wpcafe-exclude' ) === 'true';

			if ( isExternal || ( href && href.indexOf( 'http' ) === 0 ) ) {
				return; // Let external links work normally.
			}
			if ( href && href.indexOf( 'wpcafe=' ) !== -1 ) {
				return; // Already carries the param.
			}

			e.preventDefault();

			if ( href && href.indexOf( 'admin.php?page=wpcafe' ) !== -1 ) {
				window.location.href = href;
			} else if ( isExcluded ) {
				window.location.href = href;
			} else {
				var separator = href.indexOf( '?' ) !== -1 ? '&' : '?';
				window.location.href = href + separator + 'wpcafe=true';
			}
		} );
	}

	function addWpcafeParam( url ) {
		if ( ! url ) {
			return url;
		}
		if ( url.indexOf( 'wpcafe=' ) !== -1 ) {
			return url;
		}
		var separator = url.indexOf( '?' ) !== -1 ? '&' : '?';
		return url + separator + 'wpcafe=true';
	}

	/**
	 * Add wpcafe=true to internal admin anchors.
	 *
	 * @param {Iterable<HTMLAnchorElement>} links Anchor elements (NodeList or array).
	 */
	function processLinks( links ) {
		links.forEach( function ( link ) {
			var href = link.getAttribute( 'href' );

			if ( ! href || ( href.indexOf( 'http' ) === 0 && href.indexOf( window.location.origin ) === -1 ) ) {
				return;
			}
			if ( href.indexOf( '#' ) === 0 ) {
				return;
			}

			// Skip WordPress home/icon links and explicitly excluded links.
			if (
				link.classList.contains( 'wp-wordpress-icon' ) ||
				link.getAttribute( 'data-wpcafe-exclude' ) === 'true' ||
				href === '/wp-admin/' ||
				href === ADMIN_URL ||
				href === ADMIN_URL + 'index.php' ||
				link.textContent.toLowerCase().includes( 'wordpress' ) ||
				( link.querySelector( 'svg' ) && href.includes( '/wp-admin/' ) )
			) {
				return;
			}

			if ( href.indexOf( ADMIN_URL ) !== -1 && href.indexOf( '?' ) === -1 ) {
				return;
			}

			link.setAttribute( 'href', addWpcafeParam( href ) );
		} );
	}

	function initMobileSidebar() {
		var wpAdminToggle = document.getElementById( 'wp-admin-bar-menu-toggle' );
		var sidebar = document.getElementById( 'woo-wpc-sidebar' );
		var overlay = document.getElementById( 'woo-wpc-sidebar-overlay' );

		if ( ! wpAdminToggle || ! sidebar ) {
			return;
		}

		function openMobileSidebar() {
			sidebar.classList.add( 'mobile-open' );
			if ( overlay ) {
				overlay.classList.add( 'active' );
			}
			document.body.style.overflow = 'hidden';
		}

		function closeMobileSidebar() {
			sidebar.classList.remove( 'mobile-open' );
			if ( overlay ) {
				overlay.classList.remove( 'active' );
			}
			document.body.style.overflow = '';
		}

		// Hijack the WP admin-bar toggle for the mobile sidebar (mobile widths only).
		wpAdminToggle.addEventListener( 'click', function ( e ) {
			if ( window.innerWidth <= 768 ) {
				e.preventDefault();
				e.stopPropagation();
				if ( sidebar.classList.contains( 'mobile-open' ) ) {
					closeMobileSidebar();
				} else {
					openMobileSidebar();
				}
			}
		} );

		if ( overlay ) {
			overlay.addEventListener( 'click', closeMobileSidebar );
		}

		sidebar.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '.sidebar-link' ) && window.innerWidth <= 768 ) {
				setTimeout( closeMobileSidebar, 200 );
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && sidebar.classList.contains( 'mobile-open' ) ) {
				closeMobileSidebar();
			}
		} );
	}

	function addWpcafeToForm( form ) {
		if ( form && ! form.querySelector( 'input[name="wpcafe"]' ) ) {
			form.insertAdjacentHTML( 'beforeend', '<input type="hidden" name="wpcafe" value="true">' );
		}
	}

	function init() {
		initSidebar();
		initMobileSidebar();

		processLinks( document.querySelectorAll( 'a[href]' ) );

		// Preserve the wpcafe param when saving an order or publishing a post.
		document.addEventListener( 'click', function ( e ) {
			var saveOrder = e.target.closest( 'button[name="save"].save_order' );
			if ( saveOrder ) {
				addWpcafeToForm( saveOrder.closest( 'form' ) );
				return;
			}
			var publish = e.target.closest( '#publish' );
			if ( publish ) {
				addWpcafeToForm( publish.closest( 'form' ) );
			}
		} );

		// Re-process links injected dynamically (AJAX list tables, etc.).
		if ( window.MutationObserver ) {
			var observer = new MutationObserver( function ( mutations ) {
				mutations.forEach( function ( mutation ) {
					if ( mutation.type !== 'childList' ) {
						return;
					}
					mutation.addedNodes.forEach( function ( node ) {
						// Element nodes only; text/comment nodes have no querySelector/matches.
						if ( node.nodeType !== 1 ) {
							return;
						}
						processLinks( node.querySelectorAll( 'a[href]' ) );
						if ( node.matches( 'a[href]' ) ) {
							processLinks( [ node ] );
						}
					} );
				} );
			} );

			observer.observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

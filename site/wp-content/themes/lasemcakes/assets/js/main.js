/**
 * Lasem Cakes theme scripts: mobile menu, home slider, announcement pop-up.
 */
( function ( $ ) {
	'use strict';

	var settings = window.lasemTheme || {};

	/* ------------------------------------------------------------------
	 * Mobile menu
	 * ------------------------------------------------------------------ */
	function initMenu() {
		var $toggle = $( '.menu-toggle' );
		var $nav = $( '#site-navigation' );

		$toggle.on( 'click', function () {
			var open = ! $nav.hasClass( 'is-open' );
			$nav.toggleClass( 'is-open', open );
			$toggle.attr( 'aria-expanded', open ? 'true' : 'false' );
		} );

		// A +/- button beside each item that has a dropdown.
		$nav.find( '.menu-item-has-children' ).each( function () {
			var $item = $( this );
			var $button = $( '<button type="button" class="submenu-toggle" aria-expanded="false">+</button>' )
				.attr( 'aria-label', settings.openMenu || 'Open sub-menu' );

			$item.children( 'a' ).after( $button );

			$button.on( 'click', function () {
				var open = ! $item.hasClass( 'is-expanded' );
				$item.toggleClass( 'is-expanded', open );
				$button
					.text( open ? '−' : '+' )
					.attr( 'aria-expanded', open ? 'true' : 'false' )
					.attr( 'aria-label', open ? settings.closeMenu : settings.openMenu );
			} );
		} );

		// "#" parent links open their dropdown instead of jumping to the top.
		$nav.on( 'click', 'a[href="#"]', function ( event ) {
			event.preventDefault();
			$( this ).siblings( '.submenu-toggle' ).trigger( 'click' );
		} );

		$( document ).on( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && $nav.hasClass( 'is-open' ) ) {
				$toggle.trigger( 'click' ).trigger( 'focus' );
			}
		} );
	}

	/* ------------------------------------------------------------------
	 * Home slider: <div class="lasem-slider" data-delay="5000"><img>...</div>
	 * ------------------------------------------------------------------ */
	function initSliders() {
		$( '.lasem-slider' ).each( function () {
			var $slider = $( this );
			var $slides = $slider.children( 'img' );
			var delay = parseInt( $slider.data( 'delay' ), 10 ) || 5000;
			var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
			var current = 0;
			var timer;

			if ( $slides.length < 2 ) {
				$slides.addClass( 'is-active' );
				return;
			}

			var $dots = $( '<div class="lasem-slider-dots"></div>' );
			$slides.each( function ( index ) {
				var label = ( settings.slideLabel || 'Show slide %d' ).replace( '%d', index + 1 );
				$( '<button type="button"></button>' )
					.attr( 'aria-label', label )
					.on( 'click', function () {
						show( index );
						restart();
					} )
					.appendTo( $dots );
			} );
			$slider.append( $dots );

			function show( index ) {
				current = ( index + $slides.length ) % $slides.length;
				$slides.removeClass( 'is-active' ).eq( current ).addClass( 'is-active' );
				$dots.children().attr( 'aria-current', 'false' ).eq( current ).attr( 'aria-current', 'true' );
			}

			function restart() {
				window.clearInterval( timer );
				if ( ! reduceMotion ) {
					timer = window.setInterval( function () {
						show( current + 1 );
					}, delay );
				}
			}

			$slider.on( 'mouseenter focusin', function () {
				window.clearInterval( timer );
			} ).on( 'mouseleave focusout', restart );

			show( 0 );
			restart();
		} );
	}

	/* ------------------------------------------------------------------
	 * Announcement pop-up
	 * ------------------------------------------------------------------ */
	function initAnnouncement() {
		var $popup = $( '#lasem-announcement' );
		if ( ! $popup.length ) {
			return;
		}

		var key = 'lasemAnnouncement';
		var version = String( $popup.data( 'version' ) );
		var maxAgeDays = parseInt( settings.popupMaxAge, 10 );
		var lastFocus;

		function seenRecently() {
			if ( ! maxAgeDays ) {
				return false;
			}
			try {
				var seen = JSON.parse( window.localStorage.getItem( key ) || 'null' );
				return seen && seen.version === version && Date.now() - seen.time < maxAgeDays * 864e5;
			} catch ( e ) {
				return false;
			}
		}

		function remember() {
			try {
				window.localStorage.setItem( key, JSON.stringify( { version: version, time: Date.now() } ) );
			} catch ( e ) {}
		}

		function close() {
			$popup.removeClass( 'is-open' ).attr( 'hidden', true );
			remember();
			if ( lastFocus ) {
				lastFocus.focus();
			}
		}

		if ( seenRecently() ) {
			return;
		}

		lastFocus = document.activeElement;
		$popup.removeAttr( 'hidden' ).addClass( 'is-open' );
		$popup.find( '.lasem-announcement-close' ).trigger( 'focus' );

		$popup.on( 'click', '[data-close]', close );
		$( document ).on( 'keydown.lasemAnnouncement', function ( event ) {
			if ( 'Escape' === event.key && $popup.hasClass( 'is-open' ) ) {
				close();
			}
		} );
	}

	$( function () {
		initMenu();
		initSliders();
		initAnnouncement();
	} );
}( jQuery ) );

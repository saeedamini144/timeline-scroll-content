/**
 * ND Process Timeline
 * Vanilla JS. One shared scroll listener drives every timeline on the page.
 */
( function () {
	'use strict';

	var instances = [];
	var ticking = false;
	var bound = false;

	function toArray( list ) {
		return Array.prototype.slice.call( list );
	}

	function Timeline( root ) {
		this.root = root;
		this.rail = root.querySelector( '.nd-pt__rail' );
		this.fill = root.querySelector( '.nd-pt__rail-fill' );
		this.steps = toArray( root.querySelectorAll( '.nd-pt__step' ) );
		this.nodes = toArray( root.querySelectorAll( '.nd-pt__node' ) );
		this.details = toArray( root.querySelectorAll( '.nd-pt__panel .nd-pt__detail' ) );
		this.line = ( parseFloat( root.getAttribute( 'data-nd-trigger' ) ) || 50 ) / 100;
		this.last = -2;
		this.observer = null;

		var stepsWrap = root.querySelector( '.nd-pt__steps' );
		if ( window.ResizeObserver && stepsWrap ) {
			this.observer = new ResizeObserver( this.measure.bind( this ) );
			this.observer.observe( stepsWrap );
		}
		this.measure();
	}

	// The rail runs from the first node's centre to the last node's centre.
	Timeline.prototype.measure = function () {
		if ( ! this.steps.length || ! this.rail ) {
			return;
		}
		var first = this.steps[ 0 ];
		var last = this.steps[ this.steps.length - 1 ];
		this.rail.style.height = ( last.offsetTop - first.offsetTop ) + 'px';
		this.update( true );
	};

	Timeline.prototype.update = function ( force ) {
		if ( ! this.rail || ! this.fill ) {
			return;
		}

		// A step counts as reached once its node passes the trigger line.
		var line = window.innerHeight * this.line;

		var railRect = this.rail.getBoundingClientRect();
		var fill = Math.min( railRect.height, Math.max( 0, line - railRect.top ) );
		this.fill.style.height = fill + 'px';

		var current = -1;
		for ( var i = 0; i < this.nodes.length; i++ ) {
			var r = this.nodes[ i ].getBoundingClientRect();
			if ( r.top + r.height / 2 <= line ) {
				current = i;
			}
		}

		if ( current === this.last && ! force ) {
			return;
		}
		this.last = current;

		var show = Math.max( 0, current ); // before the first step, the first detail is shown
		this.steps.forEach( function ( el, i ) {
			el.classList.toggle( 'is-active', i <= current );
		} );
		this.details.forEach( function ( el, i ) {
			var on = i === show;
			el.classList.toggle( 'is-current', on );
			el.setAttribute( 'aria-hidden', on ? 'false' : 'true' );
		} );
	};

	Timeline.prototype.destroy = function () {
		if ( this.observer ) {
			this.observer.disconnect();
			this.observer = null;
		}
	};

	// Drops timelines whose element was removed (e.g. re-rendered in the Elementor editor).
	function each( fn ) {
		instances = instances.filter( function ( instance ) {
			if ( ! instance.root.isConnected ) {
				instance.destroy();
				return false;
			}
			return true;
		} );
		instances.forEach( fn );
	}

	function onScroll() {
		if ( ticking ) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame( function () {
			ticking = false;
			each( function ( instance ) {
				instance.update( false );
			} );
		} );
	}

	function onResize() {
		each( function ( instance ) {
			instance.measure();
		} );
	}

	function initAll() {
		toArray( document.querySelectorAll( '.nd-pt:not([data-nd-ready])' ) ).forEach( function ( root ) {
			root.setAttribute( 'data-nd-ready', '1' );
			instances.push( new Timeline( root ) );
		} );

		if ( ! bound && instances.length ) {
			bound = true;
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			window.addEventListener( 'resize', onResize );
			window.addEventListener( 'load', onResize );
			if ( document.fonts && document.fonts.ready ) {
				document.fonts.ready.then( onResize );
			}
		}
	}

	function start() {
		initAll();

		// Elementor editor: widgets are re-rendered on every change, so pick up new markup.
		if ( document.body.classList.contains( 'elementor-editor-active' ) && window.MutationObserver ) {
			var queued = false;
			new MutationObserver( function () {
				if ( queued ) {
					return;
				}
				queued = true;
				window.requestAnimationFrame( function () {
					queued = false;
					initAll();
				} );
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
}() );

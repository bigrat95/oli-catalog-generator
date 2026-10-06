/**
 * Oli Catalog & Product PDF — catalogue preview: drag to reorder, remove / undo,
 * price list pictures, image zoom (saved through admin-ajax), print.
 */
( function () {
	var cfg = window.olicgCatalog || {};
	var statusEl = document.querySelector( '.toolbar .status' );
	var undoBtn = document.querySelector( '.toolbar .undo' );
	var removed = [];
	var dragging = null;
	var before = '';

	function status( text ) { if ( statusEl ) { statusEl.textContent = text; } }
	function label( n ) { return ( n === 1 ? cfg.one : cfg.many ).replace( '%d', n ); }

	function post( data ) {
		var body = new URLSearchParams();
		body.append( 'action', 'olicg_arrange' );
		body.append( '_ajax_nonce', cfg.nonce );
		Object.keys( data ).forEach( function ( key ) {
			[].concat( data[ key ] ).forEach( function ( value ) {
				body.append( Array.isArray( data[ key ] ) ? key + '[]' : key, value );
			} );
		} );
		status( cfg.saving );
		return fetch( cfg.url, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( ! json || ! json.success ) { throw new Error( 'save failed' ); }
				status( cfg.saved );
				return json.data || {};
			} )
			.catch( function ( err ) { status( cfg.failed ); throw err; } );
	}

	function visibleCards( root ) {
		return Array.prototype.filter.call( ( root || document ).querySelectorAll( cfg.item ), function ( card ) { return ! card.hidden; } );
	}
	function orderKey() { return visibleCards().map( function ( card ) { return card.dataset.id; } ).join( ',' ); }

	function updateCounts() {
		document.querySelectorAll( '.section' ).forEach( function ( section ) {
			var n = visibleCards( section ).length;
			section.hidden = n === 0;
			var count = section.querySelector( '.section-count' );
			if ( count ) { count.textContent = label( n ); }
		} );
		var total = visibleCards().length;
		document.querySelectorAll( '.js-total' ).forEach( function ( el ) { el.textContent = total; } );
		document.querySelectorAll( '.js-total-label' ).forEach( function ( el ) { el.textContent = label( total ); } );
		undoBtn.hidden = removed.length === 0;
	}

	function childOf( container, el ) {
		var item = el.closest && el.closest( '[data-id]' );
		return item && item.parentNode === container ? item : null;
	}

	function picOf( item ) {
		var section = item.closest( '.section' );
		return section ? section.querySelector( '.pic[data-id="' + item.dataset.id + '"]' ) : null;
	}

	// Price list: the table rows and the pictures below them share one order.
	function mirror( container ) {
		var section = container.closest( '.section' );
		var rows = section.querySelector( '.rows' );
		var pics = section.querySelector( '.pics' );
		if ( ! rows || ! pics ) { return; }
		if ( container === rows ) {
			Array.prototype.forEach.call( rows.children, function ( row ) {
				var pic = picOf( row );
				if ( pic ) { pics.appendChild( pic ); }
			} );
			return;
		}
		var all = Array.prototype.slice.call( rows.children );
		var picIds = Array.prototype.map.call( pics.children, function ( pic ) { return pic.dataset.id; } );
		var slots = [];
		var byId = {};
		all.forEach( function ( row, i ) {
			byId[ row.dataset.id ] = row;
			if ( picIds.indexOf( row.dataset.id ) !== -1 ) { slots.push( i ); }
		} );
		picIds.forEach( function ( id, k ) { all[ slots[ k ] ] = byId[ id ]; } );
		all.forEach( function ( row ) { rows.appendChild( row ); } );
	}

	document.querySelectorAll( '.grid, .rows, .pics' ).forEach( function ( container ) {
		container.addEventListener( 'dragstart', function ( e ) {
			var item = childOf( container, e.target );
			if ( ! item ) { return; }
			dragging = item;
			before = orderKey();
			item.classList.add( 'is-dragging' );
			e.dataTransfer.effectAllowed = 'move';
			e.dataTransfer.setData( 'text/plain', item.dataset.id );
		} );
		container.addEventListener( 'dragover', function ( e ) {
			if ( ! dragging || dragging.parentNode !== container ) { return; }
			e.preventDefault();
			var over = childOf( container, e.target );
			if ( ! over || over === dragging ) { return; }
			var items = Array.prototype.slice.call( container.children );
			if ( items.indexOf( dragging ) < items.indexOf( over ) ) {
				over.after( dragging );
			} else {
				over.before( dragging );
			}
		} );
		container.addEventListener( 'drop', function ( e ) {
			if ( dragging && dragging.parentNode === container ) {
				e.preventDefault();
				finishDrag();
			}
		} );
		container.addEventListener( 'dragend', finishDrag );
	} );

	function finishDrag() {
		if ( ! dragging ) { return; }
		var container = dragging.parentNode;
		dragging.classList.remove( 'is-dragging' );
		dragging = null;
		mirror( container );
		if ( orderKey() !== before ) {
			post( { op: 'order', ids: orderKey().split( ',' ) } );
		}
	}

	function setPicture( pic, show ) {
		var row = document.querySelector( '.row[data-id="' + pic.dataset.id + '"]' );
		pic.hidden = ! show;
		if ( row ) { row.classList.toggle( 'no-pic', ! show ); }
		return post( { op: 'picture', id: pic.dataset.id, show: show ? 1 : 0 } );
	}

	document.addEventListener( 'click', function ( e ) {
		var bulk = e.target.closest && e.target.closest( '.pics-bar button' );
		if ( bulk ) {
			var show = bulk.dataset.show === '1';
			var section = bulk.closest( '.section' );
			var ids = [];
			section.querySelectorAll( '.pic' ).forEach( function ( pic ) {
				var row = section.querySelector( '.row[data-id="' + pic.dataset.id + '"]' );
				if ( row && row.hidden ) { return; }
				pic.hidden = ! show;
				if ( row ) { row.classList.toggle( 'no-pic', ! show ); }
				ids.push( pic.dataset.id );
			} );
			if ( ids.length ) { post( { op: 'picture', ids: ids, show: show ? 1 : 0 } ); }
			return;
		}
		var toggle = e.target.closest && e.target.closest( '.pic-toggle' );
		if ( toggle ) {
			var togglePic = picOf( toggle.closest( '.row' ) );
			if ( togglePic ) { setPicture( togglePic, togglePic.hidden ); }
			return;
		}
		var hide = e.target.closest && e.target.closest( '.pic-remove' );
		if ( hide ) {
			var pic = hide.closest( '.pic' );
			setPicture( pic, false ).then( function () {
				removed.push( { pic: pic } );
				updateCounts();
			} );
			return;
		}
		var btn = e.target.closest && e.target.closest( '.card-remove' );
		if ( ! btn ) { return; }
		var card = btn.closest( cfg.item );
		var cardPic = picOf( card );
		card.classList.add( 'is-removing' );
		post( { op: 'remove', id: card.dataset.id } ).then( function ( data ) {
			card.hidden = true;
			card.classList.remove( 'is-removing' );
			removed.push( { card: card, wasAdded: data.was_added ? 1 : 0, pic: cardPic, picHidden: cardPic ? cardPic.hidden : true } );
			if ( cardPic ) { cardPic.hidden = true; }
			updateCounts();
		}, function () {
			card.classList.remove( 'is-removing' );
		} );
	} );

	function fitOf( box ) {
		return { s: parseFloat( box.dataset.s ) || 1, x: parseFloat( box.dataset.x ) || 0, y: parseFloat( box.dataset.y ) || 0 };
	}
	function applyFit( box, fit ) {
		box.dataset.s = fit.s;
		box.dataset.x = fit.x;
		box.dataset.y = fit.y;
		box.querySelector( 'img' ).style.transform = ( fit.s === 1 && ! fit.x && ! fit.y ) ? '' : 'translate(' + fit.x + '%, ' + fit.y + '%) scale(' + fit.s + ')';
		box.classList.toggle( 'is-zoomed', fit.s !== 1 || !! fit.x || !! fit.y );
	}
	function saveFit( box ) {
		var fit = fitOf( box );
		post( { op: 'image', id: box.closest( '[data-id]' ).dataset.id, layout: cfg.layout, s: fit.s, x: fit.x, y: fit.y } );
	}
	function clamp( v, min, max ) { return Math.min( max, Math.max( min, v ) ); }

	document.querySelectorAll( '.card-img' ).forEach( function ( box ) {
		var img = box.querySelector( 'img' );
		applyFit( box, fitOf( box ) );

		function track( e, onMove ) {
			var card = box.closest( '[data-id]' );
			var target = e.target;
			e.preventDefault();
			e.stopPropagation();
			card.draggable = false;
			box.classList.add( 'is-editing' );
			try { target.setPointerCapture( e.pointerId ); } catch ( err ) {}
			var startX = e.clientX, startY = e.clientY, start = fitOf( box );
			function move( ev ) { onMove( ev.clientX - startX, ev.clientY - startY, start ); }
			function up() {
				target.removeEventListener( 'pointermove', move );
				target.removeEventListener( 'pointerup', up );
				target.removeEventListener( 'pointercancel', up );
				card.draggable = true;
				box.classList.remove( 'is-editing' );
				var end = fitOf( box );
				if ( end.s !== start.s || end.x !== start.x || end.y !== start.y ) { saveFit( box ); }
			}
			target.addEventListener( 'pointermove', move );
			target.addEventListener( 'pointerup', up );
			target.addEventListener( 'pointercancel', up );
		}

		box.querySelector( '.img-zoom' ).addEventListener( 'pointerdown', function ( e ) {
			var size = Math.max( box.clientWidth, box.clientHeight );
			track( e, function ( dx, dy, start ) {
				var s = clamp( start.s + ( dx + dy ) / size * 1.5, 0.3, 5 );
				applyFit( box, { s: Math.round( s * 1000 ) / 1000, x: start.x, y: start.y } );
			} );
		} );

		img.addEventListener( 'pointerdown', function ( e ) {
			if ( ! box.classList.contains( 'is-zoomed' ) ) { return; }
			track( e, function ( dx, dy, start ) {
				applyFit( box, {
					s: start.s,
					x: Math.round( clamp( start.x + dx / img.offsetWidth * 100, -150, 150 ) * 100 ) / 100,
					y: Math.round( clamp( start.y + dy / img.offsetHeight * 100, -150, 150 ) * 100 ) / 100
				} );
			} );
		} );

		box.addEventListener( 'dblclick', function ( e ) {
			e.preventDefault();
			if ( box.classList.contains( 'is-zoomed' ) ) {
				applyFit( box, { s: 1, x: 0, y: 0 } );
				saveFit( box );
			}
		} );
	} );

	undoBtn.addEventListener( 'click', function () {
		var last = removed.pop();
		if ( ! last ) { return; }
		if ( ! last.card ) {
			setPicture( last.pic, true );
			updateCounts();
			return;
		}
		last.card.hidden = false;
		if ( last.pic ) { last.pic.hidden = last.picHidden; }
		updateCounts();
		post( { op: 'restore', id: last.card.dataset.id, was_added: last.wasAdded } );
	} );

	// Print once every image has loaded, so none is missing from the PDF.
	document.querySelectorAll( '.js-print' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			var pending = Array.prototype.filter.call( document.images, function ( img ) { return ! img.complete; } );
			if ( ! pending.length ) { window.print(); return; }
			var left = pending.length;
			pending.forEach( function ( img ) {
				var done = function () { if ( --left === 0 ) { window.print(); } };
				img.addEventListener( 'load', done, { once: true } );
				img.addEventListener( 'error', done, { once: true } );
			} );
		} );
	} );
} )();

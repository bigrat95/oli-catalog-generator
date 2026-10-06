( function ( $ ) {
	function syncSection( $tbody ) {
		var $boxes = $tbody.find( '.olicg-include' );
		$tbody.find( '.olicg-toggle-section' ).prop( 'checked', $boxes.length && $boxes.filter( ':checked' ).length === $boxes.length );
	}

	$( function () {
		if ( $.fn.wpColorPicker ) {
			$( '.olicg-color' ).wpColorPicker();
		}

		$( '.olicg-section' ).each( function () {
			syncSection( $( this ) );
		} );

		$( document ).on( 'change', '.olicg-include', function () {
			$( this ).closest( 'tr' ).toggleClass( 'is-excluded', ! this.checked );
			syncSection( $( this ).closest( '.olicg-section' ) );
		} );

		$( document ).on( 'change', '.olicg-toggle-section', function () {
			var checked = this.checked;
			$( this ).closest( '.olicg-section' ).find( '.olicg-include' ).each( function () {
				this.checked = checked;
				$( this ).closest( 'tr' ).toggleClass( 'is-excluded', ! checked );
			} );
		} );

		if ( $.fn.sortable ) {
			$( '.olicg-section' ).sortable( {
				items: '> .olicg-row',
				handle: '.olicg-handle',
				axis: 'y',
				helper: function ( event, $row ) {
					$row.children().each( function () {
						$( this ).width( $( this ).width() );
					} );
					return $row;
				},
				update: function () {
					$( '.olicg-order-changed' ).val( '1' );
				}
			} );
		}

		$( document ).on( 'click', '.olicg-row-remove', function () {
			var $row = $( this ).closest( '.olicg-row' );
			var $box = $row.find( '.olicg-include' );
			var removing = ! $row.hasClass( 'is-excluded' );

			if ( $box.length ) {
				$box.prop( 'checked', ! removing ).trigger( 'change' );
				return;
			}

			var id = String( $row.data( 'id' ) );
			var $select = $( '#olicg_added' );
			if ( removing ) {
				$row.data( 'label', $select.find( 'option[value="' + id + '"]' ).text() );
				$select.find( 'option[value="' + id + '"]' ).remove();
			} else {
				$select.append( new Option( $row.data( 'label' ) || id, id, true, true ) );
			}
			$select.trigger( 'change' );
			$row.toggleClass( 'is-excluded', removing );
		} );

		$( '.olicg-filter' ).on( 'input', function () {
			var term = $.trim( this.value.toLowerCase() );
			$( '.olicg-section' ).each( function () {
				var $tbody = $( this ), visible = 0;
				$tbody.find( '.olicg-row' ).each( function () {
					var show = ! term || String( $( this ).data( 'search' ) ).indexOf( term ) !== -1;
					$( this ).toggle( show );
					visible += show ? 1 : 0;
				} );
				$tbody.find( '.olicg-section-row' ).toggle( visible > 0 );
			} );
		} );
	} );
} )( jQuery );

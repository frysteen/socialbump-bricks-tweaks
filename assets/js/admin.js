/**
 * SB Bricks Tweaks settings screen.
 *
 * Colour fields: the swatch shows whatever the text box resolves to on this
 * page, including var(--name). Picking with the swatch writes a hex back.
 * Card settings show or hide as the switch is flipped.
 */
( function ( $ ) {
	/**
	 * Resolve any CSS colour (hex, rgb, hsl, var) to a hex, or null if it can't be.
	 * A variable that isn't defined here inherits the sentinel colour, so it's caught.
	 */
	function resolveHex( value ) {
		if ( ! value ) {
			return null;
		}

		var holder = document.createElement( 'span' );
		var probe  = document.createElement( 'span' );

		holder.style.color   = 'rgb(1, 2, 3)';
		holder.style.display = 'none';
		holder.appendChild( probe );
		document.body.appendChild( holder );

		probe.style.color = value;

		var accepted = probe.style.color !== '';
		var computed = window.getComputedStyle( probe ).color;

		holder.parentNode.removeChild( holder );

		var m = accepted ? computed.match( /rgba?\(\s*(\d+)[,\s]+(\d+)[,\s]+(\d+)/ ) : null;

		if ( ! m || ( m[1] === '1' && m[2] === '2' && m[3] === '3' ) ) {
			return null;
		}

		return '#' + [ m[1], m[2], m[3] ].map( function ( n ) {
			return ( '0' + parseInt( n, 10 ).toString( 16 ) ).slice( -2 );
		} ).join( '' );
	}

	$( function () {
		$( '.sbbt-colour' ).each( function () {
			var $swatch = $( this ).find( '.sbbt-colour__swatch' );
			var $value  = $( this ).find( '.sbbt-colour__value' );

			var sync = function () {
				var hex = resolveHex( $.trim( $value.val() ) );

				if ( hex ) {
					$swatch.val( hex );
				}
			};

			$swatch.on( 'input change', function () {
				$value.val( this.value );
			} );

			$value.on( 'input', sync );
			sync();
		} );

		$( '.sbbt-card .sbbt-switch input' ).on( 'change', function () {
			$( this ).closest( '.sbbt-card' ).toggleClass( 'is-on', this.checked );
		} );
	} );
} )( jQuery );
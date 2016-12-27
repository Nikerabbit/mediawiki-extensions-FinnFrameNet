( function ( $ ) {
	function getAnnotationType( element ) {
		var match = $( element ).attr( 'class' ).match( /ffn-ann-([^ ]+)/ );
		return match[ 0 ];
	}

	function getTypes( $elements ) {
		var types = [];
		$elements.each( function () {
			var match = getAnnotationType( this );
			match && types.push( match );
		} );

		types = types.filter( function ( value ) {
			return value !== 'ffn-ann-FEE';
		} );

		return types;
	}

	function onMouseEvent( e ) {
		var i, className, elements;

		$( '.ffn-feh' ).removeClass( 'ffn-feh' );
		if ( e.type !== 'mouseenter' ) {
			return;
		}

		for ( i = 0; i < this.classList.length; i++ ) {
			className = this.classList.item( i );

			if ( !/ffn-ann-/.test( className ) ) {
				continue;
			}

			elements = document.getElementsByClassName( className );
			[].forEach.call( elements, function ( element ) {
				element.classList.add( 'ffn-feh' );
			} );
		}
	}

	function toggleFiltering() {
		var types;

		this.classList.toggle( 'ffn-filter' );
		$( '.ffn-hide' ).removeClass( 'ffn-hide' );

		types = getTypes( $( '.ffn-typelist .ffn-filter' ) );
		if ( types.length === 0 ) {
			return;
		}

		$( '.ffn-sentences li' ).each( function () {
			var i,
				$s = $( this ),
				show = true;

			for ( i = 0; i < types.length; i++ ) {
				show = show && $s.find( '.' + types[ i ] ).length;
			}

			show || $s.addClass( 'ffn-hide' );
		} );

		$( '.ffn-sentences h2' ).each( function () {
			if ( $( this ).next( 'ul' ).find( 'li' ).not( '.ffn-hide' ).length === 0 ) {
				$( this ).addClass( 'ffn-hide' );
			}
		} );
	}

	function init() {
		var $annToggle;

		$( '#mw-content-text' ).on( 'mouseenter mouseleave', '.ffn-fe', onMouseEvent );
		$( '.ffn-typelist .ffn-fe' ).on( 'click', toggleFiltering );
		$( '.ffn-typelist' ).stick_in_parent();

		$annToggle = $( '<button>' )
			.text( 'Annotations' )
			.addClass( 'mw-ui-button mw-ui-progressive' )
			.click( function () {
				$( '.ffn-sentences' ).toggleClass( 'ffn-show-anns' );
			} );
		$( '.ffn-typelist p' ).append( $annToggle );
	}

	if ( document.readyState === 'interactive' ) {
		init();
	} else {
		$( init );
	}
}( jQuery ) );

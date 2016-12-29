( function ( $, mw ) {
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

		$( this ).toggleClass( 'ffn-filter' );
		$( '.ffn-sentences .ffn-hide' ).removeClass( 'ffn-hide' );

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

	function addButtons( $content ) {
		var $actions = $content.find( '.ffn-actions' );

		$( '<button>' )
			.text( mw.message( 'ffn-actions--annotations' ).text() )
			.addClass( 'mw-ui-button mw-ui-progressive' )
			.click( function () {
				$( '.ffn-sentences' ).toggleClass( 'ffn-show-anns' );
			} )
			.appendTo( $actions );

		$( '<button>' )
			.text( mw.message( 'ffn-actions--toc' ).text() )
			.addClass( 'mw-ui-button mw-ui-progressive' )
			.click( function () {
				$( '.ffn-toc' ).toggleClass( 'ffn-hide' );
			} )
			.appendTo( $actions );

		$content.prepend( $actions );
	}

	function replaceAll( str, mapObj ) {
		var re = new RegExp( Object.keys( mapObj ).join( '|' ), 'gi' );

		return str.replace( re, function ( m ) {
			return mapObj[ m ];
		} );
	}

	function addLexicalUnitSearchLinks( $headings ) {
		var conf, ns;

		conf = mw.config.get( [ 'wgFormattedNamespaces', 'wgNamespaceNumber' ] );
		ns = conf.wgFormattedNamespaces[ conf.wgNamespaceNumber ];

		$headings.each( function () {
			var url, $link, lu = $( this ).text();

			url = new mw.Title( 'Special:Ask' ).getUrl( {
				q: replaceAll( '[[Category:0]] [[FrameNet:Has lexical unit::1]]', [ ns, lu ] ),
				'p[format]': 'ul',
				'p[link]': 'none',
				'p[limit]': 500,
				'p[template]': 'FFN/Search result',
				'p[userparam]': lu
			} );

			$link = $( '<a>' )
				.prop( {
					href: url,
					title: mw.message( 'ffn-search-lus' ).text()
				} )
				.text( '🔎' );

			$( this ).append( ' ', $link );
		} );
	}

	function init() {
		$( '#mw-content-text' ).on( 'mouseenter mouseleave', '.ffn-fe', onMouseEvent );
		$( '.ffn-typelist .ffn-fe' ).on( 'click', toggleFiltering );
		$( '.ffn-typelist' ).stick_in_parent();

		if ( mw.config.get( 'wgAction' ) === 'view' ) {
			mw.hook( 'wikipage.content' ).add( addButtons );
			addLexicalUnitSearchLinks( $( '.ffn-sentences > h2' ) );
		}
	}

	if ( document.readyState === 'interactive' ) {
		init();
	} else {
		$( init );
	}
}( jQuery, mediaWiki ) );

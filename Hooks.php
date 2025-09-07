<?php

namespace FinnFrameNet;

use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Html\Html;
use Override;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks implements BeforePageDisplayHook, ContentAlterParserOutputHook {
	private static array $colors = [
		'#452a74',
		'#832f1b',
		'#882554',
		'#5746a1',
		'#88367e',
		'#347638',
		'#756a1d',
		'#bd3754',
		'#3d7922',
		'#c84c39',
		'#c550ab',
		'#567dcf',
		'#c85f68',
		'#5679e8',
		'#c46722',
		'#9072db',
		'#b26acd',
		'#729429',
		'#d76194',
		'#a485d3',
		'#dd7761',
		'#bf894d',
		'#d68647',
		'#3ba7e5',
		'#56b555',
		'#ce9c2d',
		'#dc87d2',
		'#c3a74b',
		'#75be7b',
		'#aeb363',
		'#acb839',
		'#42c87f',
		'#43c8ac',
		'#8ec368',
		'#9ac859'
	];

	#[Override]
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $out->getTitle()->inNamespaces( NS_FINNFRAMENET, NS_TRANSFRAMENET ) ) {
			$out->addModules( 'ext.finnframenet' );
			$out->addModuleStyles( 'ext.finnframenet.styles' );
		}
	}

	#[Override]
	public function onContentAlterParserOutput(
		$content,
		$title,
		$parserOutput
	): void {
		if ( $title->inNamespaces( NS_FINNFRAMENET, NS_TRANSFRAMENET ) ) {
			$text = $content->getNativeData();
			if ( preg_match( '/types\s*=\s*([^|]+)\|/', (string)$text, $match ) ) {
				$types = explode( ';', $match[1] );
				$css = self::getCSS( $types );
				$parserOutput->addHeadItem( Html::inlineStyle( $css ) );
			}
		}
	}

	private static function getCSS( array $types ): string {
		$css = [];
		$len = count( $types );

		$j = 0;
		for ( $i = 0; $i < $len; $i++ ) {
			$type = trim( (string)$types[$i] );
			$color = self::$colors[$j % count( self::$colors )];
			if ( $type !== 'FEE' && $type !== 'FEEM' ) {
				$j++;
				$css[] = ".ffn-ann-$type { color: $color; }";
			}
			$css[] = ".ffn-show-anns .ffn-ann-$type::before,";
			$css[] = ".client-nojs .ffn-sentences .ffn-ann-$type::before { content: '[$type '; }";
		}

		return implode( "\n", $css );
	}
}

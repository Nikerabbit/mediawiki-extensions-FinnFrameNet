<?php

ini_set( 'memory_limit', '2G' );

$IN = isset( $argv[1] ) ? $argv[1] : 'finframenet-beta';
$OUT = isset( $argv[2] ) ? $argv[2] : 'finnframenet.json';
process( $IN, $OUT );

function process( $IN, $OUT ) {
	$all = [];

	$iter = new DirectoryIterator( $IN );
	foreach ( $iter as $entry ) {
		if ( !$entry->isFile() || $entry->getExtension() !== 'vrt' ) {
			continue;
		}

		$filename = $entry->getFilename();
		$input = file_get_contents( "$IN/$filename" );
		$all = array_merge( $all, parse( $input ) );
		echo [ 'O', 'o' ][ mt_rand( 0, 1 ) ];
	}

	file_put_contents( $OUT, json_encode( $all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
	echo " ^__^\n";
}

function parse( $string ) {
	$output = [];
	$matches = [];
	preg_match_all( '~<sentence (.+)>\n(.+)\n</sentence>~sU', $string, $matches, PREG_SET_ORDER );

	foreach ( $matches as $sentence ) {
		$attributes = parseAttributes( $sentence[1] );
		unset( $attributes['origin'], $attributes['fes'], $attributes['rid'] );

		$columns = parseSentences( $sentence[2] );
		for ( $i = 1; $i < 4; $i++ ) {
			$columns[$i] = array_filter( $columns[$i], function ( $x ) {
				return $x !== '_';
			} );
		}

		$output[] = [
			array_values( $attributes ),
			$columns
		];
	}

	return $output;
}

function parseAttributes( $string ) {
	$output = [];
	$matches = [];
	preg_match_all( '~([a-z]+)="([^"]*)"~', $string, $matches, PREG_SET_ORDER );
	foreach ( $matches as $match ) {
		$output[$match[1]] = trim( $match[2] );
	}

	return $output;
}

function parseSentences( $string ) {
	$matches = [];
	preg_match_all( '~^(.+)\t(.+)\t(.+)\t(.+)\t(.+)$~m', $string, $matches );

	array_shift( $matches );
	$matches[0] = $matches[4];
	unset( $matches[4] );

	return $matches;
}

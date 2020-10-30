<?php

ini_set( 'memory_limit', '2G' );

$IN = isset( $argv[1] ) ? $argv[1] : 'transframe/data-sep-2015/';
$OUT = isset( $argv[2] ) ? $argv[2] : 'transframenet.json';
process( $IN, $OUT );

function process( $IN, $OUT ) {
	$all = [];

	$iter = new DirectoryIterator( $IN );
	foreach ( $iter as $entry ) {
		if ( !$entry->isFile() || $entry->getExtension() !== 'csv' ) {
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

	$string = preg_replace( '~\R~u', "\n", $string );

	$parts = preg_split( "/\n,\s*,\s*,\s*,\s*,\n/", $string );

	foreach ( $parts as $part ) {
		$attributes = parseAttributes( $part );

		$columns = parseSentences( $part );
		for ( $i = 1; $i < 4; $i++ ) {
			$columns[$i] = array_filter( $columns[$i], function ( $x ) {
				return $x !== '';
			} );
		}

		$columns[2] = array_map( 'strtolower', $columns[2] );

		$output[] = [
			array_values( $attributes ),
			$columns
		];
	}

	return $output;
}

function parseAttributes( $string ) {
	$matches = [];
	if ( !preg_match( '/^# item,([^,]+),([^,]+)/m', $string, $matches ) ) {
		var_dump( $string );
		die();
	}

	return [
		'frame' => $matches[1],
		'lex' => $matches[2],
	];
}

function parseSentences( $string ) {
	$output = [];

	$lines = explode( "\n", $string );
	foreach ( $lines as $lineNo => $line ) {
		if ( $lineNo < 2 || trim( $line ) === '' || $line[0] === '#' ) {
			continue;
		}

		$cells = str_getcsv( $line, ',', '"', '"' );
		foreach ( $cells as $index => $cell ) {
			$output[ $index ][] = $cell;
		}
	}

	unset( $output[2] );
	unset( $output[4] );
	$main = $output[5];
	unset( $output[5] );
	array_unshift( $output, $main );
	$output = array_values( $output );

	return $output;
}

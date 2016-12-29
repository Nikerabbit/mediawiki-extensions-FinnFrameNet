<?php

ini_set( 'memory_limit', '2G' );

$IN = isset( $argv[1] ) ? $argv[1] : 'transframenet.json';
$OUT = isset( $argv[2] ) ? $argv[2] : 'entrypages';

process( $IN, $OUT );

function parseEntry( array $entry ) {
	// LU = Lexical Unit
	list( $frame, $LU ) = $entry[0];

	$linearDoc = linearize( $entry[1] );
	$text = stringify( $linearDoc );

	if ( trim( $LU ) === '' ) {
		$LU = '???';
	}

	if ( trim( $frame ) === '' ) {
		$frame = '???';
	}

	return [ "TransFrameNet:$frame" => [ $LU => $text ] ];
}

function stringify( array $linearDoc ) {
	$string = '';

	foreach ( $linearDoc as $segment ) {
		$numLayers = count( $segment );
		$translation = isset( $segment[3] ) ? $segment[3] : false;
		unset( $segment[3] );
		$text = array_shift( $segment );

		if ( $numLayers === 1 ) {
			$string .= "{{FFN/P|$text}} ";
		} else {
			$classes = str_replace( ' ', '_', implode( ';', $segment ) );
			if ( $translation ) {
				$string .= "{{FFN/T|$classes|$text|$translation}} ";
			} else {
				$string .= "{{FFN/T|$classes|$text}} ";
			}
		}
	}
	return trim( $string );
}

function linearize( array $sentence ) {
	$linearDoc = [];

	$columns = count( $sentence );
	$rows = count( $sentence[0] );

	for ( $r = 0; $r < $rows; $r++ ) {
		$segment = [];

		for ( $c = 0; $c < $columns; $c++ ) {
			// Only the first column is a proper list, rest
			// are shallow lists where some indexes are missing
			if ( !isset( $sentence[$c][$r] ) ) {
				continue;
			}

			$segment[$c] = $sentence[$c][$r];
		}

		$linearDoc[] = $segment;
	}

	return $linearDoc;
}

function collectTypes( $input ) {
	$types = [];
	preg_match_all( '~\{\{FFN/T\|([^|]+)~', $input, $matches );
	foreach ( $matches[1] as $match ) {
		foreach ( explode( ';', $match ) as $type ) {
			$types[$type] = true;
		}
	}

	$uniqueTypes = array_keys( $types );
	sort( $uniqueTypes );
	return $uniqueTypes;
}

function process( $IN, $OUT ) {
	is_dir( $OUT ) || mkdir( $OUT );
	$data = json_decode( file_get_contents( $IN ), true );

	$pages = [];
	foreach ( $data as $index => $rawEntry ) {
		foreach ( parseEntry( $rawEntry ) as $page => $value ) {
			$pages[$page][] = $value;
		}
	}

	foreach ( $pages as $page => $entries ) {
		$sections = [];
		foreach ( $entries as $wrapper ) {
			foreach ( $wrapper as $section => $entry ) {
				$sections[$section][] = $entry;
			}
		}

		ksort( $sections );
		$pages[$page] = $sections;

	}

	$longpage = $pages['TransFrameNet:Self_motion'];
	unset( $pages['TransFrameNet:Self_motion'] );
	$newpages = array_chunk( $longpage, 44, true );
	foreach ( $newpages as $i => $newpage ) {
		$j = $i + 1;
		$pages["TransFrameNet:Self_motion ($j)"] = $newpage;
	}

	foreach ( $pages as $page => $sections ) {
		$contents = '';
		foreach ( $sections as $section => $entries ) {
			$contents .= "== [[FrameNet:Has lexical unit::$section]] ==\n";
			foreach ( $entries as $entry ) {
				$contents .= "* $entry\n";
			}
		}

		$types = implode( ';', collectTypes( $contents ) );
		$contents = "{{FFN\n|types=\n$types\n|contents=\n$contents}}";

		$page = strtr( $page, '_', ' ' );
		file_put_contents( "$OUT/$page", $contents );
	}
}

<?php

ini_set( 'memory_limit', '2G' );

$IN = isset( $argv[1] ) ? $argv[1] : 'finnframenet.json';
$OUT = isset( $argv[2] ) ? $argv[2] : 'entrypages';

process( $IN, $OUT );

function parseEntry( array $entry ) {
	// LU = Lexical Unit
	list( $frame, , $fiLU ) = $entry[0];

	$sentence = splitMultiPart( $entry[1] );
	$linearDoc = linearize( $sentence );
	$linearDoc = mergeSegments( $linearDoc );

	if ( trim( $fiLU ) === '' ) {
		$fiLU = '???';
	}

	if ( trim( $frame ) === '' ) {
		$frame = '???';
	}

	$text = stringify( $linearDoc );
	$frame = trim( $frame, ' _' );

	return [ "FinnFrameNet:$frame" => [ $fiLU => $text ] ];
}

function stringify( array $linearDoc ) {
	$string = '';

	foreach ( $linearDoc as $segment ) {
		$numLayers = count( $segment );
		if ( $numLayers === 1 ) {
			$string .= $segment[0];
		} else {
			$text = array_shift( $segment );
			$classes = implode( ';', $segment );
			$string .= "{{FFN/E|$classes|$text}}";
		}
	}
	return $string;
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

			$segment[] = $sentence[$c][$r];
		}

		$linearDoc[] = $segment;
	}

	return $linearDoc;
}

function mergeSegments( array $linearDoc ) {
	$mergedLinearDoc = [];
	$mergeStart = 0;
	$mergeEnd = 0;
	foreach ( $linearDoc as $index => $segment ) {
		if ( $segment[0] === ' ' ) {
			continue;
		}

		if ( areCompatible( $linearDoc[ $mergeStart ], $segment ) ) {
			$mergeEnd = $index;
		} else {
			$mergedLinearDoc[] = composeMergeSegment( $linearDoc, $mergeStart, $mergeEnd );

			$mergeStart = $index;

			// Handle space, first check if there is one unprocessed
			if ( $index - $mergeEnd === 2 ) {
				if ( areCompatible( $linearDoc[ $index - 1 ], $segment ) ) {
					// Move mergeStart backwards to include space
					$mergeStart = $index - 1;
				} else {
					// Space is between to incompatible segments, insert it now
					$mergedLinearDoc[] = $linearDoc[ $index - 1 ];
				}
			}

			$mergeEnd = $index;
		}
	}

	$mergedLinearDoc[] = composeMergeSegment( $linearDoc, $mergeStart, $mergeEnd );

	return $mergedLinearDoc;
}

function composeMergeSegment( $doc, $start, $end ) {
	$text = '';
	for ( $i = $start; $i <= $end; $i++ ) {
		$text .= $doc[ $i ][ 0 ];
	}

	$segment = $doc[ $start ];
	$segment[0] = $text;
	return $segment;
}

function areCompatible( array $a, array $b ) {
	// Ignore text layer
	array_shift( $a );
	array_shift( $b );
	return $a == $b;
}

function splitMultiPart( $sentence ) {
	// Split multi-part words such as "Tuomitse|t|ko"
	$copy = [];
	$copyIndex = 0;

	$columns = count( $sentence );
	$rows = count( $sentence[0] );

	for ( $r = 0; $r < $rows; $r++ ) {

		$delta = 0;
		for ( $c = 0; $c < $columns; $c++ ) {
			// Only the first column is a proper list, rest
			// are shallow lists where some indexes are missing
			if ( !isset( $sentence[$c][$r] ) ) {
				continue;
			}

			$word = $sentence[$c][$r];
			$segments = explode( '|', $word );
			// Only update the index after processing the row
			if ( $c === 0 ) {
				$delta = count( $segments );
				// For punctuation, overwrite previous space
				if ( preg_match( '/^[.,?!:;]$/', $word ) ) {
					$copyIndex = max( 0, $copyIndex - 1 );
				}
			}

			foreach ( $segments as $index => $value ) {
				if ( $value !== '' ) {
					$copy[ $c ][ $copyIndex + $index ] = $value;
				}
			}

		}

		$copyIndex += $delta;
		$copy[ 0 ][ $copyIndex++ ] = ' ';
	}

	// Remove last superfluous space
	unset( $copy[ 0 ][ $copyIndex - 1 ] );

	return $copy;
}

function collectTypes( $input ) {
	$types = [];
	preg_match_all( '~\{\{FFN/E\|([^|]+)~', $input, $matches );
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

		$contents = '';

		ksort( $sections );
		if ( isset( $sections['???'] ) ) {
			$temp = $sections['???'];
			unset( $sections['???'] );
			$sections['???'] = $temp;
		}

		foreach ( $sections as $section => $items ) {
			$contents .= "== [[FrameNet:Has lexical unit::$section]] ==\n";
			foreach ( $items as $item ) {
				$contents .= "* $item\n";
			}
		}

		$types = implode( ';', collectTypes( $contents ) );
		$contents = "{{FFN\n|types=\n$types\n|contents=\n$contents}}";

		$page = strtr( $page, '_', ' ' );
		file_put_contents( "$OUT/$page", $contents );
	}
}

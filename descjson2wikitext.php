<?php

ini_set( 'memory_limit', '2G' );

$IN = isset( $argv[1] ) ? $argv[1] : 'descriptions.json';
$OUT = isset( $argv[2] ) ? $argv[2] : 'entrypages';

process( $IN, $OUT );

function parseEntry( $frame, array $entry ) {
	$output = [
		'name' => $frame,
		'type' => $entry['description']['type'],
		'frames' => implode( ';', $entry['frames'] ),
	];

	return [ "FrameNet/$frame" => $output ];
}

function formatEntry( array $templateData ) {
	$fmt = "{{FFN/Element page\n";
	foreach ( $templateData as $k => $v ) {
		$fmt .= "|$k=$v\n";
	}
	$fmt .= "}}\n";

	return $fmt;
}

function process( $IN, $OUT ) {
	is_dir( $OUT ) || mkdir( $OUT );
	$data = json_decode( file_get_contents( $IN ), true );

	$pages = [];
	foreach ( $data as $index => $rawEntry ) {
		foreach ( parseEntry( $index, $rawEntry ) as $page => $value ) {
			$pages[$page] = $value;
		}
	}

	foreach ( $pages as $page => $templateData ) {
		$contents = formatEntry( $templateData );

		$page = strtr( $page, '_', ' ' );
		$page = strtr( $page, '/', '_' );
		file_put_contents( "$OUT/$page", $contents );
	}
}

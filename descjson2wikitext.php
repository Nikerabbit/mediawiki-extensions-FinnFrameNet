<?php

$IN = $argv[1] ?? 'descriptions.json';
$OUT = $argv[2] ?? 'entrypages';

process( $IN, $OUT );

function parseDescriptionEntry( string $frame, array $entry ): array {
	$output = [
		'name' => $frame,
		'type' => $entry['description']['type'],
		'frames' => implode( ';', $entry['frames'] ),
	];

	return [ "FrameNet/$frame" => $output ];
}

function formatEntry( array $templateData ): string {
	$fmt = "{{FFN/Element page\n";
	foreach ( $templateData as $k => $v ) {
		$fmt .= "|$k=$v\n";
	}

	return $fmt . "}}\n";
}

function process( string $IN, string $OUT ): void {
	if ( !is_dir( $OUT ) ) {
		mkdir( $OUT );
	}
	$data = json_decode( file_get_contents( $IN ), true );

	$pages = [];
	foreach ( $data as $index => $rawEntry ) {
		foreach ( parseDescriptionEntry( $index, $rawEntry ) as $page => $value ) {
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

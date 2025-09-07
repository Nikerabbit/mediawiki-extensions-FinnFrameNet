<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;

function main() {
	$elements = getElements();
	downloadDescriptions( $elements );
}

function getElements(): array {
	return parseElements( downloadElements() );
}

function downloadElements(): string {
	$client = new Client();
	$response = $client->get( 'https://kitwiki.csc.fi/twiki/bin/view/KitWiki/FrameNetElements' );
	return $response->getBody()->getContents();
}

function parseElements( $html ): array {
	// Consume all html attributes.
	$c = '[^<>]+';
	$re = "~<td$c>\s*<a href=\"([^\"]+)\"$c>($c)</a>\s*</td>\s*<td$c>($c)</td>~";
	preg_match_all( $re, (string)$html, $matches, PREG_SET_ORDER );

	$elements = [];
	foreach ( $matches as $m ) {
		$frames = array_map( 'strtolower', array_map( 'trim', explode( ',', $m[3] ) ) );
		sort( $frames );

		$elements[$m[2]] = [
			'url' => $m[1],
			'frames' => $frames,
		];
	}

	return $elements;
}

function downloadDescriptions( array $elements ) {
	$client = new Client( [ 'base_uri' => 'https://kitwiki.csc.fi' ] );
	$requests = [];

	foreach ( $elements as $name => $element ) {
		$requests[$name] = new Request( 'GET', $element['url'] );
	}

	$pool = new Pool( $client, $requests, [
		'concurrency' => 5,
		'fulfilled' => static function ( $response, $index ) use ( &$elements ): void {
			# echo [ 'O', 'o' ][ mt_rand( 0, 1 ) ];
			$body = $response->getBody()->getContents();
			$elements[$index]['description'] = parseDescriptionPage( $body );
			if ( $elements[$index]['frames'] !== $elements[$index]['description']['frames'] ) {
				var_dump( [
					'element' => $index,
					'+++' => array_diff( $elements[$index]['frames'], $elements[$index]['description']['frames'] ),
					'---' => array_diff( $elements[$index]['description']['frames'], $elements[$index]['frames'] ),
				] );
			}
		},
		'rejected' => static function ( $reason, $index ) use ( $elements ): void {
			$url = $elements[$index]['url'];
			echo "\nFailed to download $url: $reason\n";
		},
	] );

	// Initiate the transfers and create a promise
	$promise = $pool->promise();

	// Force the pool of requests to complete.
	$promise->wait();

	echo "\n";

	$json = json_encode(
		$elements,
		JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);
	file_put_contents( 'descriptions.json', $json );
}

function parseDescriptionPage( $html ): array {
	$parsed = [];

	$html = mb_convert_encoding( $html, 'UTF-8', 'ISO-8859-1' );

	preg_match( '~TYYPPI: (.*)~', $html, $type );
	$parsed['type'] = $type[1];

	preg_match( '~(<h2>.*)</div><!-- /patternTopic--~s', $html, $match );
	$content = $match[1];
	$content = preg_replace( '~-- <a.*~s', '', $content );
	$content = preg_replace( '~<p />|</?ul>~', '', (string)$content );
	$content = preg_replace( '~<li>\s+(.*)\s+</li>~sU', "\\1\n", (string)$content );
	$content = str_replace( 'Esiintyy kehyksissä:', '', $content );

	$sectionMap = [
		'Määritelmä' => 'definition',
		'Määritelmä 1' => 'definition1',
		'Määritelmä 2' => 'definition2',
		'Määritelmä 3' => 'definition3',
		'Esiintyy kehyksissä' => 'frames',
		'Muu käyttö' => 'other',
		'Muita huomioita' => 'notes'
	];

	$subsectionMap = [
		'KUVAUS' => 'description',
		'ESIM' => 'examples',
	];

	$c = '[^<>]+';
	foreach ( explode( "\n", $content ) as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		} elseif ( preg_match( "~<h2><a$c></a>(.*)</h2>~", $line, $match ) ) {
			$section = $sectionMap[ trim( $match[1] ) ];
		} elseif ( $section === 'frames' ) {
			$line = strip_tags( $line );
			$frames = array_map( 'strtolower', array_map( 'trim', explode( ',', $line ) ) );
			sort( $frames );
			$parsed[$section] = $frames;
			break;
		} elseif ( preg_match( '~^([A-Z]+): (.*)~', $line, $match ) ) {
			$subsection = $subsectionMap[ trim( $match[1] ) ];

			$contents = $match[2];
			if ( $subsection === 'examples' ) {
				$contents = explode( '<br /> ', $contents );
			}

			$parsed[$section][$subsection] = $contents;
		}
	}

	if ( !isset( $parsed['frames'] ) ) {
		$parsed['frames'] = [];
	}

	return $parsed;
}

main();

<?php

declare( strict_types=1 );

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths( [
		__DIR__
	] )
	->withSkip( [
		__DIR__ . '/vendor',
		__DIR__ . '/node_modules',
	] )
	->withPhpSets()
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		typeDeclarations: true,
		privatization: true,
	);

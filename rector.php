<?php

declare( strict_types=1 );

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths( [
		__DIR__
	] )
	->withSkipPath( 'vendor' )
	->withPhpSets()
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		typeDeclarations: true,
		privatization: true,
	);

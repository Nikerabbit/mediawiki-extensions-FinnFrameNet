<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['minimum_target_php_version'] = '8.4';
$cfg['suppress_issue_types'] = array_values( array_filter(
	$cfg['suppress_issue_types'],
	static fn ( string $issue ): bool => !str_starts_with( $issue, 'PhanDeprecated' )
) );

// The standalone converters run separately and are covered by lint and Rector.
$cfg['file_list'][] = 'Hooks.php';

return $cfg;

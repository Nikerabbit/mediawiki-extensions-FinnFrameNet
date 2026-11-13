'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );

module.exports = defineConfig( [
	globalIgnores( [ 'vendor/**', 'node_modules/**' ] ),
	{
		ignores: [ 'modules/**' ],
		extends: [ wikimedia( 'server', 'mediawiki' ) ]
	},
	{
		files: [ 'modules/**' ],
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki', 'jsduck' ) ],
		rules: {
			'security/detect-non-literal-regexp': 'off',
			'no-jquery/no-class-state': 'off',
			'no-jquery/no-global-selector': 'off'
		}
	}
] );

/* eslint-env node */
module.exports = function ( grunt ) {
	'use strict';

	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );

	grunt.initConfig( {
		eslint: {
			fix: {
				options: {
					fix: true
				},
				src: '<%= eslint.main %>'
			},
			main: [
				'**/*.js',
				'!node_modules/**',
				'!vendor/**',
				'!libs/**'
			]
		},
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**',
				'!libs/**'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint:main', 'jsonlint' ] );
	grunt.registerTask( 'default', 'test' );
};

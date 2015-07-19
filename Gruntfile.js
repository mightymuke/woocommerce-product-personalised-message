/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		po2mo: {
			files: {
				src: 'languages/*.po',
				expand: true,
			},
		},

		pot: {
			options: {
				text_domain: 'woocommerce-product-personalised-message',
				dest: 'languages/',
				keywords: [
					'__:1',
					'_e:1',
					'_x:1,2c',
					'esc_html__:1',
					'esc_html_e:1',
					'esc_html_x:1,2c',
					'esc_attr__:1', 
					'esc_attr_e:1', 
					'esc_attr_x:1,2c', 
					'_ex:1,2c',
					'_n:1,2', 
					'_nx:1,2,4c',
					'_n_noop:1,2',
					'_nx_noop:1,2,3c'
				],
			},
			files: {
				src: [
					'**/*.php',
					'!node_modules/**',
					'!templates/**'
				],
			    expand: true
			}
		},
	});

	grunt.registerTask( 'build', [ 'pot', 'newer:po2mo' ] );

};

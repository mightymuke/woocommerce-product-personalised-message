/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	require('load-grunt-tasks')(grunt);

	grunt.initConfig({

		po2mo: {
			files: {
				src: 'languages/*.po',
				expand: true
			}
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
				]
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

		checktextdomain: {
			options:{
				text_domain: 'woocommerce-product-personalised-message',
				correct_domain: true,
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d', 
					'esc_attr_e:1,2d', 
					'esc_attr_x:1,2c,3d', 
					'_ex:1,2c,3d',
					'_n:1,2,4d', 
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [
					'**/*.php',
					'!node_modules/**'
				],
				expand: true
			}
		}
	});

	grunt.registerTask( 'build', [ 'checktextdomain', 'pot', 'newer:po2mo' ] );

};

<?php
/*
Plugin Name: QQWorld Speed for China
Plugin URI: http://www.qqworld.org
Description: QQWorld's plugin that using for WP speed up for China
Version: 1.0.0
Author: Michael Wang
Author URI: http://www.qqworld.org
*/

define('QQWORLD_SPEED4CHINA_DIR', __DIR__);
define('QQWORLD_SPEED4CHINA_URL', plugin_dir_url(__FILE__));

class qqworld_speed4china {
	public function __construct() {
		add_action( 'wp_default_styles', array($this, 'wp_default_styles') );
	}

	public function wp_default_styles(&$styles) {
		// http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext
		$styles->remove('open-sans');
		$styles->add( 'open-sans', QQWORLD_SPEED4CHINA_URL . 'opensans.css' );
	}

}
new qqworld_speed4china;
?>
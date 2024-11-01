<?php
	/*
	Plugin Name: WF Weather
	Plugin URI: http://www.wunderfarm.com/plugins/wf-weather
	Description: WF Weather is the `wunderfarm-way` to show Weather information on your website.
	Version: 0.9.1
	License: GNU General Public License v2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Author: wunderfarm
	Text Domain: wf-weather
	Domain Path: /languages
	Author URI: http://www.wunderfarm.com
	*/

	defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

	function wf_weather_load_translations() {
		load_plugin_textdomain('wf-weather', FALSE, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	add_action('init', 'wf_weather_load_translations');

	include('includes/utils.php'); // Load utils.
	include('includes/wf_weather_api.php'); // Load api.
	include('includes/wf_weather_settings.php'); // Load settings page.
	include('includes/wf_weather_shortcodes.php'); // Load shortcodes.

/*
* Enqueue JS and CSS
*/
function wf_weather_scripts() {
	// Load CSS
	wp_enqueue_style( 'wf_weather_custom', plugins_url( '/css/wf-weather.css', __FILE__ ) );
}

add_action( 'wp_enqueue_scripts', 'wf_weather_scripts' );


?>

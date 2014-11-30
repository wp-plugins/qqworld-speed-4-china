<?php
/*
Plugin Name: QQWorld Speed for China
Plugin URI: http://www.qqworld.org
Description: If your host is in china, you might need this plugin to make your website that running faster.
Version: 1.3
Author: Michael Wang
Author URI: http://www.qqworld.org
Text Domain: qqworld-speed-4-china
*/

define('QQWORLD_SPEED4CHINA_DIR', __DIR__);
define('QQWORLD_SPEED4CHINA_URL', plugin_dir_url(__FILE__));

class qqworld_speed4china {
	var $value;
	var $using_google_fonts;
	var $auto_update_code;
	var $auto_update_plugins;
	var $auto_update_themes;
	public function __construct() {
		add_action( 'admin_menu', array($this, 'create_menu') );
		add_action( 'admin_init', array($this, 'register_setting') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );
		add_action( 'plugins_loaded', array($this, 'load_language') );
		$this->get_value();
		$this->speed_up();
	}

	public function get_value() {
		$this->values = get_option('qqworld-speed-4-china');
		$this->using_google_fonts = isset($this->values['using-google-fonts']) ? $this->values['using-google-fonts'] : 'disabled';
		$this->enable_zxcvbn_async = isset($this->values['enable-zxcvbn-async']) ? $this->values['enable-zxcvbn-async'] : 'disabled';
		$this->auto_update_code = isset($this->values['auto-update-core']) ? $this->values['auto-update-core'] : 'disabled';
		$this->auto_update_plugins = isset($this->values['auto-plugins-plugins']) ? $this->values['auto-plugins-plugins'] : 'disabled';
		$this->auto_update_themes = isset($this->values['auto-update-themes']) ? $this->values['auto-update-themes'] : 'disabled';
	}

	public function speed_up() {
		if ($this->using_google_fonts == 'disabled') {
			add_action( 'wp_default_styles', array($this, 'wp_default_styles') );
		}

		if ($this->enable_zxcvbn_async == 'disabled') {
			add_action( 'wp_default_scripts', array($this, 'wp_default_scripts') );
		}

		if ($this->auto_update_code == 'disabled') {
			add_filter( 'pre_site_transient_update_core', create_function('$a', "return null;"));
			remove_action( 'admin_init', '_maybe_update_core');
			remove_action( 'wp_version_check', 'wp_version_check' );
			remove_action( 'upgrader_process_complete', 'wp_version_check', 10, 0 );
			$this->remove_auto_update();
		}

		if ($this->auto_update_plugins == 'disabled') {
			add_filter( 'pre_site_transient_update_plugins', create_function('$a', "return null;"));
			remove_action( 'admin_init', '_maybe_update_plugins');
			remove_action( 'load-plugins.php', 'wp_update_plugins' );
			remove_action( 'load-update.php', 'wp_update_plugins' );
			remove_action( 'load-update-core.php', 'wp_update_plugins' );
			remove_action( 'admin_init', '_maybe_update_plugins' );
			remove_action( 'wp_update_plugins', 'wp_update_plugins' );
			remove_action( 'upgrader_process_complete', 'wp_update_plugins', 10, 0 );
			$this->remove_auto_update();
		}

		if ($this->auto_update_themes == 'disabled') {
			add_filter( 'pre_site_transient_update_themes', create_function('$a', "return null;"));
			remove_action( 'admin_init', '_maybe_update_themes');
			remove_action( 'load-themes.php', 'wp_update_themes' );
			remove_action( 'load-update.php', 'wp_update_themes' );
			remove_action( 'load-update-core.php', 'wp_update_themes' );
			remove_action( 'wp_update_themes', 'wp_update_themes' );
			remove_action( 'upgrader_process_complete', 'wp_update_themes', 10, 0 );
			$this->remove_auto_update();
		}
	}

	public function remove_auto_update() {
		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
		remove_action( 'init', 'wp_schedule_update_checks' );
	}

	public function wp_default_scripts(&$scripts) {
		$scripts->remove('zxcvbn-async');
	}

	public function wp_default_styles(&$styles) {
		$styles->remove('open-sans');
		$open_sans_font_url = '';

		/* translators: If there are characters in your language that are not supported
		 * by Open Sans, translate this to 'off'. Do not translate into your own language.
		 */
		if ( 'off' !== _x( 'on', 'Open Sans font: on or off' ) ) {
			$subsets = 'latin,latin-ext';

			/* translators: To add an additional Open Sans character subset specific to your language,
			 * translate this to 'greek', 'cyrillic' or 'vietnamese'. Do not translate into your own language.
			 */
			$subset = _x( 'no-subset', 'Open Sans font: add new subset (greek, cyrillic, vietnamese)' );

			if ( 'cyrillic' == $subset ) {
				$subsets .= ',cyrillic,cyrillic-ext';
			} elseif ( 'greek' == $subset ) {
				$subsets .= ',greek,greek-ext';
			} elseif ( 'vietnamese' == $subset ) {
				$subsets .= ',vietnamese';
			}

			// Hotlink Open Sans, for now
			$open_sans_font_url = "//fonts.useso.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=$subsets";
		}
		$styles->add( 'open-sans', $open_sans_font_url );
	}

	public function load_language() {
		load_plugin_textdomain( 'qqworld-speed-4-china', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	public function registerPluginLinks($links, $file) {
		$base = plugin_basename(__FILE__);
		if ($file == $base) {
			$links[] = '<a href="' . menu_page_url( 'qqworld-speed-4-china', 0 ) . '">' . __('Settings') . '</a>';
		}
		return $links;
	}

	function register_setting() {
		register_setting('qqworld-speed-4-china', 'qqworld-speed-4-china');
	}

	public function create_menu() {
		add_submenu_page('options-general.php', __('QQWorld Speed for China', 'qqworld-speed-4-china'), __('QQWorld Speed for China', 'qqworld-speed-4-china'), 'administrator', 'qqworld-speed-4-china', array($this, 'fn') );
	}

	function fn() {
?>
	<style>
	#banner {
		max-width: 100%;
		display: block;
		margin: 20px 0;
		border: 10px solid #fff;
		box-sizing: border-box;
		box-shadow: 3px 3px 5px rgba(0,0,0,.1);
	}
	@media screen and ( max-width: 1000px ) {
		#banner {
			height: auto;
		}
	}
	@media screen and ( max-width: 640px ) {
		#banner {
			border-width: 5px;
		}
	}
	</style>
	<div class="wrap">
		<h2><?php _e('QQWorld Speed for China', 'qqworld-speed-4-china'); ?></h2>
		<p><?php _e('If your host is in china, you might need this plugin to make your website that running faster.', 'qqworld-speed-4-china'); ?></p>
		<p><img src="https://ps.w.org/qqworld-speed-4-china/assets/banner-772x250.jpg?rev=982686" id="banner" /></p>
		<p><?php _e("If you want to update, don't forget temporarily enable these options.", 'qqworld-speed-4-china'); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields('qqworld-speed-4-china'); ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-core"><?php _e('Using Google Fonts', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-core-yes" name="qqworld-speed-4-china[using-google-fonts]" value="enabled" <?php checked($this->using_google_fonts, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-core-no" name="qqworld-speed-4-china[using-google-fonts]" value="disabled" <?php checked($this->using_google_fonts, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-core"><?php _e('Enable zxcvbn-async', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-core-yes" name="qqworld-speed-4-china[enable-zxcvbn-async]" value="enabled" <?php checked($this->enable_zxcvbn_async, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-core-no" name="qqworld-speed-4-china[enable-zxcvbn-async]" value="disabled" <?php checked($this->enable_zxcvbn_async, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-core"><?php _e('Auto Update Core', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-core-yes" name="qqworld-speed-4-china[auto-update-core]" value="enabled" <?php checked($this->auto_update_code, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-core-no" name="qqworld-speed-4-china[auto-update-core]" value="disabled" <?php checked($this->auto_update_code, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-plugins"><?php _e('Auto Update Plugins', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-plugins-yes" name="qqworld-speed-4-china[auto-plugins-plugins]" value="enabled" <?php checked($this->auto_update_plugins, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-plugins-no" name="qqworld-speed-4-china[auto-plugins-plugins]" value="disabled" <?php checked($this->auto_update_plugins, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-themes"><?php _e('Auto Update Themes', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-themes-yes" name="qqworld-speed-4-china[auto-update-themes]" value="enabled" <?php checked($this->auto_update_themes, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-themes-no" name="qqworld-speed-4-china[auto-update-themes]" value="disabled" <?php checked($this->auto_update_themes, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
<?php
	}
}
new qqworld_speed4china;
?>
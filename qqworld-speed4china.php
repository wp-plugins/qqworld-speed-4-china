<?php
/*
Plugin Name: QQWorld Speed for China
Plugin URI: https://wordpress.org/plugins/qqworld-speed-4-china/
Description: If your host is in china, you might need this plugin to make your website that running faster.
Version: 1.5.3
Author: Michael Wang
Author URI: http://www.qqworld.org
Text Domain: qqworld-speed-4-china
*/

define('QQWORLD_SPEED4CHINA_DIR', __DIR__);
define('QQWORLD_SPEED4CHINA_URL', plugin_dir_url(__FILE__));

class qqworld_speed4china {
	var $value;
	var $using_google_fonts;
	var $using_gravatar;
	var $default_avatar;
	var $local_avatar;
	var $auto_update_core;
	var $auto_update_plugins;
	var $auto_update_themes;
	var $advanced_speed_up;
	public function __construct() {
		add_action( 'admin_menu', array($this, 'create_menu') );
		add_action( 'admin_init', array($this, 'register_setting') );
		add_filter( 'plugin_row_meta', array($this, 'registerPluginLinks'),10,2 );
		add_action( 'plugins_loaded', array($this, 'load_language') );
		add_action( 'admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
		$this->get_value();
		$this->speed_up();
	}

	public function outside_language() {
		__('Michael Wang', 'qqworld-speed-4-china');
	}

	public function get_value() {
		$this->default_avatar = QQWORLD_SPEED4CHINA_URL . 'images/avatar_256x256.png';
		$this->values = get_option('qqworld-speed-4-china');
		$this->using_google_fonts = isset($this->values['using-google-fonts']) ? $this->values['using-google-fonts'] : 'disabled';
		$this->using_gravatar = isset($this->values['using-gravatar']) ? $this->values['using-gravatar'] : 'enabled';
		$this->local_avatar = isset($this->values['local-avatar']) && !empty($this->values['local-avatar']) ? $this->values['local-avatar'] : $this->default_avatar;
		$this->enable_zxcvbn_async = isset($this->values['enable-zxcvbn-async']) ? $this->values['enable-zxcvbn-async'] : 'disabled';
		$this->auto_update_core = isset($this->values['auto-update-core']) ? $this->values['auto-update-core'] : 'disabled';
		$this->auto_update_plugins = isset($this->values['auto-update-plugins']) ? $this->values['auto-update-plugins'] : 'disabled';
		$this->auto_update_themes = isset($this->values['auto-update-themes']) ? $this->values['auto-update-themes'] : 'disabled';
		$this->advanced_speed_up = isset($this->values['advanced-speed-up']) ? $this->values['advanced-speed-up'] : 'disabled';
		$this->update_plugins_ids = isset($this->values['update_plugins_ids']) ? $this->values['update_plugins_ids'] : array();
		$this->update_themes_ids = isset($this->values['update_themes_ids']) ? $this->values['update_themes_ids'] : array();
		$this->update_core_ids = isset($this->values['update_core_ids']) ? $this->values['update_core_ids'] : array();
		$this->update_plugins_roles_ids = isset($this->values['update_plugins_roles_ids']) ? $this->values['update_plugins_roles_ids'] : array();
		$this->update_themes_roles_ids = isset($this->values['update_themes_roles_ids']) ? $this->values['update_themes_roles_ids'] : array();
		$this->update_core_roles_ids = isset($this->values['update_core_roles_ids']) ? $this->values['update_core_roles_ids'] : array();
	}

	public function admin_enqueue_scripts() {
		//for 3.5+ uploader
		wp_enqueue_media();
	}

	public function speed_up() {
		if ($this->using_google_fonts == 'disabled') {
			add_action( 'wp_default_styles', array($this, 'wp_default_styles') );
		}

		if ($this->using_gravatar == 'disabled') {
			add_filter( 'get_avatar', array($this, 'get_avatar'), 11, 5 );
		}

		if ($this->enable_zxcvbn_async == 'disabled') {
			add_action( 'wp_default_scripts', array($this, 'wp_default_scripts') );
		}

		if ($this->auto_update_core == 'disabled') {
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
			remove_action( 'upgrader_process_complete', 'wp_update_plugins' );

			$timestamp = wp_next_scheduled( 'wp_update_plugins' );
			wp_unschedule_event( $timestamp, 'wp_update_plugins');

			$this->remove_auto_update();
		}

		if ($this->auto_update_themes == 'disabled') {
			add_filter( 'pre_site_transient_update_themes', create_function('$a', "return null;"));
			remove_action( 'admin_init', '_maybe_update_themes');
			remove_action( 'load-themes.php', 'wp_update_themes' );
			remove_action( 'load-update.php', 'wp_update_themes' );
			remove_action( 'load-update-core.php', 'wp_update_themes' );
			remove_action( 'wp_update_themes', 'wp_update_themes' );
			remove_action( 'upgrader_process_complete', 'wp_update_themes' );

			$timestamp = wp_next_scheduled( 'wp_update_themes' );
			wp_unschedule_event( $timestamp, 'wp_update_themes');

			$this->remove_auto_update();
		}

		if ($this->advanced_speed_up == 'enabled') {
			add_filter( 'user_has_cap', array($this, 'user_has_cap') );
		}
	}

	public function user_has_cap($allcaps) {
		if (isset($allcaps['update_plugins'])) unset($allcaps['update_plugins']);
		if (isset($allcaps['update_themes'])) unset($allcaps['update_themes']);
		if (isset($allcaps['update_core'])) unset($allcaps['update_core']);
		return $allcaps;
	}

	public function get_avatar($avatar, $id_or_email, $size, $default, $alt) {
		$url = is_numeric( $this->local_avatar ) ? wp_get_attachment_url( $this->local_avatar ) : $this->local_avatar;
		return '<img src="'.$url.'" class="avatar avatar-'.$size.' height="'.$size.'" width="'.$size.'" alt="'.$alt.'" />';
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
		$styles->add( 'open-sans', QQWORLD_SPEED4CHINA_URL . 'opensans.css' );
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

	public function register_setting() {
		register_setting('qqworld-speed-4-china', 'qqworld-speed-4-china');
	}

	public function create_menu() {
		add_submenu_page('options-general.php', __('QQWorld Speed for China', 'qqworld-speed-4-china'), __('QQWorld Speed for China', 'qqworld-speed-4-china'), 'administrator', 'qqworld-speed-4-china', array($this, 'page') );
	}

	public function page() {
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
	#local-avatar {
		cursor: pointer;
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
		<p><img src="<?php echo QQWORLD_SPEED4CHINA_URL; ?>images/banner-772x250.jpg" width="772" height="250" id="banner" /></p>
		<p><?php _e("If you want to update, don't forget temporarily enable these options.", 'qqworld-speed-4-china'); ?></p>
		<form method="post" action="options.php">
			<input type="hidden" name="qqworld-speed-4-china[update_plugins_roles_ids]" value="" />
			<input type="hidden" name="qqworld-speed-4-china[update_themes_roles_ids]" value="" />
			<input type="hidden" name="qqworld-speed-4-china[update_core_roles_ids]" value="" />
			<?php settings_fields('qqworld-speed-4-china'); ?>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row">
							<label for="use-google-fonts"><?php _e('Using Google Fonts', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="use-google-fonts-yes" name="qqworld-speed-4-china[using-google-fonts]" value="enabled" <?php checked($this->using_google_fonts, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="use-google-fonts-no" name="qqworld-speed-4-china[using-google-fonts]" value="disabled" <?php checked($this->using_google_fonts, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="using-gravatar"><?php _e('Using Gravatar', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="using-gravatar-yes" name="qqworld-speed-4-china[using-gravatar]" value="enabled" <?php checked($this->using_gravatar, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="using-gravatar-no" name="qqworld-speed-4-china[using-gravatar]" value="disabled" <?php checked($this->using_gravatar, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top" id="local-avatar-row"<?php if ($this->using_gravatar == 'enabled') echo ' class="hidden"'; ?>>
						<th scope="row">
							<label for="local-avatar"><?php _e('Local Avatar', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
							<?php
							if ( is_numeric($this->local_avatar) ) {
								$id = $this->local_avatar;
								$url = wp_get_attachment_url( $id );
							} else {
								$id = '';
								$url = $this->local_avatar;
							}
							?>
							<div id="local-avatar"><img src="<?php echo $url; ?>" width="80" height="80" default-avatar="<?php echo $this->default_avatar; ?>" title="<?php _e('Insert Avatar', 'qqworld-speed-4-china');?>" /></div>
							<input type="hidden" id="upload-avatar" name="qqworld-speed-4-china[local-avatar]" value="<?php echo $this->local_avatar; ?>" />
							<input type="button" class="button<?php if ( !is_numeric($this->local_avatar) ) echo ' hidden'; ?>" id="using-default-avatar" value="<?php _e('Using Default Avatar', 'qqworld-speed-4-china'); ?>" />
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="zxcvbn-async"><?php _e('Enable zxcvbn-async', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="zxcvbn-async-yes" name="qqworld-speed-4-china[enable-zxcvbn-async]" value="enabled" <?php checked($this->enable_zxcvbn_async, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="zxcvbn-async-no" name="qqworld-speed-4-china[enable-zxcvbn-async]" value="disabled" <?php checked($this->enable_zxcvbn_async, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-core"><?php _e('Auto Update Core', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-core-yes" name="qqworld-speed-4-china[auto-update-core]" value="enabled" <?php checked($this->auto_update_core, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-core-no" name="qqworld-speed-4-china[auto-update-core]" value="disabled" <?php checked($this->auto_update_core, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
							</aside>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-plugins"><?php _e('Auto Update Plugins', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="auto-update-plugins-yes" name="qqworld-speed-4-china[auto-update-plugins]" value="enabled" <?php checked($this->auto_update_plugins, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="auto-update-plugins-no" name="qqworld-speed-4-china[auto-update-plugins]" value="disabled" <?php checked($this->auto_update_plugins, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label>
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
					<tr valign="top">
						<th scope="row">
							<label for="auto-update-themes"><?php _e('Advanced Speed Up', 'qqworld-speed-4-china'); ?></label>
						</th>
						<td>
							<aside class="admin_box_unit">
								<label><input type="radio" id="advanced-speed-up-yes" name="qqworld-speed-4-china[advanced-speed-up]" value="enabled" <?php checked($this->advanced_speed_up, 'enabled'); ?> /> <?php _e('Enabled', 'qqworld-speed-4-china');_e('(Speed up)', 'qqworld-speed-4-china'); ?></label><br />
								<label><input type="radio" id="advanced-speed-up-no" name="qqworld-speed-4-china[advanced-speed-up]" value="disabled" <?php checked($this->advanced_speed_up, 'disabled'); ?> /> <?php _e('Disabled', 'qqworld-speed-4-china'); ?></label>
							</aside>
							<p class="description"><?php _e('If enabled this option, all update action will be disabled.', 'qqworld-speed-4-china'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
		<script>
		var wpqs4c = {};
		wpqs4c.speed4china = function() {
			var $ = jQuery, _this = this;
			$(document).on('click', '#using-gravatar-yes', function() {
				$('#local-avatar-row').fadeOut('normal');
			}).on('click', '#using-gravatar-no', function() {
				$('#local-avatar-row').fadeIn('normal');
			}).on('click', '#local-avatar-row label, #local-avatar', function() {
				event.preventDefault();
				var title = $('#local-avatar img').attr('title');
				if ( typeof _this.file_frame == 'object' ) {
					_this.file_frame.open();
					return;
				}
				_this.file_frame = wp.media.frames.file_frame = wp.media({
					title: title,
					button: {
						text: title,
					},
					multiple: false
				});
				_this.file_frame.on( 'open', function() {
					var selection = _this.file_frame.state().get('selection');
					var attachment_id = $('#upload-avatar').val();
					if (attachment_id) {
						var attachment = wp.media.attachment(attachment_id);
						attachment.fetch();
						selection.add( attachment ? [ attachment ] : [] );
					}
				});
				_this.file_frame.on('select', function() {
					var attachment = _this.file_frame.state().get('selection').first().toJSON();
					var id = attachment.id;
					var url = attachment.url;
					$('#local-avatar img').attr('src', url);
					$('#upload-avatar').val(id).attr({
						'type': 'hidden',
						'name': 'qqworld-speed-4-china[local-avatar]'
					});
					$('#using-default-avatar').slideDown('normal');
				});
				_this.file_frame.open();
			}).on('click', '#using-default-avatar', function() {
				$('#upload-avatar').val('');
				$('#local-avatar img').attr('src', $('#local-avatar img').attr('default-avatar'));
				$(this).slideUp('normal');
			});
		}
		wpqs4c.speed4china();
		</script>
	</div>
<?php
	}
}
new qqworld_speed4china;
?>
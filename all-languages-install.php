<?php
/**
 *
 * @package   AllLanguagesInstall
 * @author    1shiharaT <akeome1369@gmail.com>
 * @license   GPL-2.0+
 * @link      http://web-layman.com
 * @copyright 2014 ishihara takashi
 *
 * @wordpress-plugin
 * Plugin Name:       all-languages-install
 * Plugin URI:        http://web-layman.com
 * Description:       言語ファイルインストーラー
 * Version:           1.0.0
 * Author:            1shiharaT
 * Author URI:        http://web-layman.com
 * Text Domain:       all-languages-install-locale
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/1shiharaT/all-languages-install
 * WordPress-Plugin-Boilerplate: v2.6.1
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	add_action( 'plugins_loaded', array( 'WP_All_Languages_Install', 'get_instance' ) );

}

class WP_All_Languages_Install {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	public $available_languages = null;

	public $installed_languages = null;

	public $plugin_slug = 'wpali';

	private function __construct()
	{


		$this->installed_languages = $this->get_already_installed_languages();

		$this->available_languages = wp_get_available_translations_from_api();

		add_action( 'admin_init', array( $this, 'all_languages_installer' ) );
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

	}


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		/*
		 * @TODO :
		 *
		 * - Uncomment following lines if the admin class should only be available for super admins
		 */
		/* if( ! is_super_admin() ) {
			return;
		} */

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'All Languages Install', $this->plugin_slug ),
			__( 'All Languages Install', $this->plugin_slug ),
			'manage_options',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */

	public function display_plugin_admin_page() {
		?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	<p><?php _e( 'If you want to install all languages​​, please click the button below.', $this->plugin_slug ) ?></p>
	<?php $this->installer_form(); ?>
</div>

		<?php
		// if ( $success ) {
		// 	echo $this->get_success_message();
		// }
	}

	public function get_success_message()
	{
		$message = '';
		$message = '';
		return $message;
	}

	/**
	 * Install form HTML.
	 * @return void
	 */
	public function installer_form()
	{

		$form = '';
		$nonce = wp_nonce_field( __FILE__, '_wpnonce', true, false );

		$selectbox = '<select name="wpali_selected_install[]" multiple size="30">';
			foreach ( $this->available_languages as $lang_key => $lang ) {
				$selectbox .= '<option value="' . $lang_key . '">' . $lang['english_name'] . '</option>';
			}
		$selectbox .= '</select>';

		$form .= '<form action="" method="post">';
		$form .= $nonce;

		$form .= '<button type="submit name="wpali_install" value="all" class="button button-primary">' . __( 'Install All Languages', $this->plugin_slug ) . '</button>
		<hr />
		<p>Or you can select from among the select box next, please install.</p>
		' . $selectbox . '
		<br>
		<br>
		<button type="submit" class="button button-primary">' . __( 'Install the language of your choice', $this->plugin_slug )  . '</button>
		</form>';

		echo $form;

	}

	/**
	 * Check nonce field.
	 */

	public function check_nonce_referer( $wp_nonce, $referer )
	{

		$check_nonce = wp_verify_nonce( $wp_nonce, __FILE__ );
		$check_referer = check_admin_referer( __FILE__, '_wpnonce' );

		if ( $check_nonce && $check_referer ) {
			return true;
		}

		return false;

	}

	/**
	 * languages installer
	 * @since    1.0.0
	 */
	public function all_languages_installer()
	{

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wp_nonce         = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
		$http_referer     = isset( $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : false;
		$all_install_lang = isset( $_POST['wpali_install'] ) ? $_POST['wpali_install'] : array();
		$selected_lang    = isset( $_POST['wpali_selected_install'] ) ? $_POST['wpali_selected_install'] : array();

		if ( ! $selected_lang
				&& ! $all_install_lang ) {
			return false;
		}

		$check = $this->check_nonce_referer( $wp_nonce, $http_referer );

		if ( ! $check ) {
			return false;
		}

		// all language
		if ( $all_install_lang ) {
			$install_languages = $this->available_languages;
			if ( $install_languages ) {
				foreach ( $install_languages as $lang_key => $lang ) {
					wp_install_download_language_pack( $lang['language'] );
				}
			}
		}

		// selected language
		if ( $selected_lang  ) {
			$install_languages = array_map( 'esc_attr', $selected_lang );
			if ( $install_languages ) {
				foreach ( $install_languages as $lang_key => $lang ) {
					wp_install_download_language_pack( $lang );
				}
			}
		}

		add_query_arg( 'lang_install', 'true', $_SERVER['REQUEST_URI'] );

		return true;

	}

	/**
	 * Get available languages
	 * @return object
	 */
	private function get_already_installed_languages(){

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$installed_languages = get_available_languages();

		return apply_filters( 'wpali_installed_lang', $installed_languages );

	}

	private function get_available_translations_from_api()
	{
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$available_languages = wp_get_available_translations_from_api();

		foreach ( $available_languages as $lang_key => $lang ) {
			if ( in_array( $lang['language'], $this->installed_languages ) ) {
				unset( $lang['language'] );
			}
		}
		return apply_filters( 'wpali_available_lang', $available_languages );

	}

}

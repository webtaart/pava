<?php
/*
Plugin Name: SiteOrigin Premium
Description: Advanced functionality for SiteOrigin themes and plugins.
Version: 1.0.1
Author: SiteOrigin
Author URI: https://siteorigin.com
Plugin URI: https://siteorigin.com/premium/
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.txt
*/

define( 'SITEORIGIN_PREMIUM_VERSION', '1.0.1' );
define( 'SITEORIGIN_PREMIUM_JS_SUFFIX', '.min' );
define( 'SITEORIGIN_PREMIUM_BASE_FILE', __FILE__ );

include plugin_dir_path( __FILE__ ) . '/inc/edd_updater.php';
include plugin_dir_path( __FILE__ ) . '/inc/admin-notices.php';

include plugin_dir_path( __FILE__ ) . '/admin/options.php';

if( !class_exists( 'SiteOrigin_Subversion_Theme_Updater' ) ) {
	include plugin_dir_path( __FILE__ ) . '/inc/theme-updater.php';
}

class SiteOrigin_Premium_Manager {

	const STORE_URL = 'https://siteorigin.com/';
	const STORE_ITEM_ID = 23323;
	const REPLACE_TEASERS = true;

	static $js_suffix;

	static $default_active = array(
	);

	/**
	 * @var SO_Premium_EDD_SL_Plugin_Updater
	 */
	private $updater;

	function __construct(){
		add_action( 'plugins_loaded', array( $this, 'load_plugin_addons' ), 15 );
		add_action( 'after_setup_theme', array( $this, 'load_theme_addons' ), 15 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_common_scripts' ), 6 );

		if( self::REPLACE_TEASERS  ) {
			// This removes teaser fields from the settings
			add_filter( 'siteorigin_settings_display_teaser', '__return_false' );

			// And we create a new handler to add the field in the case of teasers
			add_action( 'siteorigin_settings_add_teaser_field', array($this, 'handle_teaser_field'), 10, 6 );
		}

		// Setup the EDD updater
		$key = get_option( 'siteorigin_premium_key' );
		$this->updater = new SO_Premium_EDD_SL_Plugin_Updater( self::STORE_URL, __FILE__, array(
			'version' => SITEORIGIN_PREMIUM_VERSION,
			'license' => !empty( $key ) ? trim( $key ) : false,
			'item_id' => self::STORE_ITEM_ID,
			'author' => 'SiteOrigin',
			'url' => home_url()
		) );

		self::$js_suffix = $this->is_license_active() ?  SITEORIGIN_PREMIUM_JS_SUFFIX : '';

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_action_links' ) );
	}

	/**
	 * Create the singleton of SiteOrigin Premium
	 *
	 * @return SiteOrigin_Premium_Manager
	 */
	static function single(){
		static $single;

		if( empty($single) ) {
			$single = new SiteOrigin_Premium_Manager();
		}

		return $single;
	}

	/**
	 * Load any addons for the widgets bundle.
	 */
	function load_plugin_addons(){
		$active = $this->get_active_addons();

		foreach( $active as $id => $status ) {
			if( !$status ) continue;

			$filename = plugin_dir_path(__FILE__) . 'addons/' . preg_replace('/(.*)\/(.*)/', '$1/$2/$2.php', $id);

			if( file_exists(  $filename ) ) {
				include $filename;
			}
		}
	}

	/**
	 * Load supported and activated addons for themes
	 */
	function load_theme_addons(){
		global $_wp_theme_features;
		if( empty( $_wp_theme_features ) || !is_array( $_wp_theme_features ) ) return;

		foreach( array_keys( $_wp_theme_features ) as $feature ) {

			if( !preg_match( '/siteorigin-premium-(.+)/', $feature, $matches ) ) continue;
			if( ! isset( $_wp_theme_features[$feature][0] ) ) continue;

			$feature_args = $_wp_theme_features[$feature][0];
			if( empty( $feature_args['enabled'] ) ) continue;

			$feature_name = $matches[1];

			$filename = plugin_dir_path(__FILE__) . 'addons/theme/' . $feature_name . '/' . $feature_name . '.php';
			if( file_exists( $filename ) ) {
				include $filename;
			}
		}
	}

	/**
	 * Handle the teaser field
	 *
	 * @param SiteOrigin_Settings $settings
	 * @param string $section
	 * @param string $id
	 * @param string $type
	 * @param string $label
	 * @param array $args
	 */
	function handle_teaser_field( $settings, $section, $id, $type, $label, $args ){
		if( method_exists( $settings, 'add_field' ) ) {
			$settings->add_field( $section, $id, $type, $label, $args );
		}
	}

	/**
	 * Send a request to the SiteOrigin Premium servers to activate this site.
	 *
	 * @param string $license_key The license key.
	 *
	 * @return string The status
	 */
	function activate_license( $license_key ){
		$license_key = trim( $license_key );
		if( empty($license_key) ) return false;

		// Request to send over to the activation servers
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license_key,
			'item_id' => intval( self::STORE_ITEM_ID ),
			'url'       => home_url()
		);

		// lets send the request to the server
		$response = wp_remote_post( self::STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		if ( is_wp_error( $response ) ) return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		return $license_data;
	}

	/**
	 * Check if a license key is valid.
	 *
	 * @param $license_key
	 *
	 * @return bool Is the key valid.
	 */
	function check_license_key( $license_key ){

		// Request to send over to the activation servers
		$api_params = array(
			'edd_action'=> 'check_license',
			'license' 	=> $license_key,
			'item_name' => urlencode( self::STORE_ITEM_ID ),
			'url'       => home_url( )
		);

		// lets send the request to the server
		$response = wp_remote_post( self::STORE_URL, array(
			'timeout' => 15,
			'sslverify' => false,
			'body' => $api_params
		) );

		if ( is_wp_error( $response ) ) return 'inactive';
		$license_data = @ json_decode( wp_remote_retrieve_body( $response ) );

		return isset( $license_data->license ) ? $license_data->license : 'invalid';
	}

	/**
	 * Get the mode we're using. Either development or production.
	 */
	function get_mode( ){
		return get_option( 'siteorigin_premium_license_status' == 'active' ) ? 'production' : 'development';
	}

	/**
	 * Do a database check to see if the license has been activated.
	 *
	 * @param bool|false $license_key
	 *
	 * @return bool
	 */
	static function is_license_active( $license_key = false ){
		if( empty($license_key) ) $license_key = get_option( 'siteorigin_premium_key' );
		return get_option( 'siteorigin_premium_license_status' ) == 'active' && get_option( 'siteorigin_premium_key' ) == $license_key;
	}

	/**
	 * Get all the active addons
	 *
	 * @return mixed|void
	 */
	function get_active_addons(){
		$active_addons = get_option( 'siteorigin_premium_active', array() );
		$active_addons = wp_parse_args( $active_addons, self::$default_active );
		return $active_addons;
	}

	/**
	 * Set the addon active state
	 *
	 * @param $id
	 * @param bool|true $active
	 */
	function set_addon_active( $id, $active = true ){
		// Check that the addon exists
		list( $addon_section, $addon_id ) = explode( '/', $id, 2 );

		$active_addons = $this->get_active_addons();
		$filename = plugin_dir_path(__FILE__) . 'addons/' . $addon_section . '/' . $addon_id . '/' . $addon_id . '.php';

		if( $addon_section !== 'theme' && file_exists( $filename ) ) {
			$active_addons[ $id ] = $active;
		} else {
			unset( $active_addons[ $id ] );
		}

		update_option( 'siteorigin_premium_active', $active_addons );
	}

	/**
	 * Check if the addon is active
	 *
	 * @param $addon_id
	 *
	 * @return bool
	 */
	function is_addon_active( $addon_id ){
		$active_addons = $this->get_active_addons();
		return !empty( $active_addons[$addon_id] );
	}

	function register_common_scripts(){

		if( ! wp_script_is( 'siteorigin-parallax', 'registered' ) ) {
			// Page Builder and SiteOrigin Premium use the same parallax library.
			wp_register_script(
				'siteorigin-parallax',
				plugin_dir_url( __FILE__ ) . 'js/siteorigin-parallax' . self::$js_suffix . '.js',
				array( 'jquery' ),
				SITEORIGIN_PREMIUM_VERSION
			);
		}

		wp_register_style(
			'siteorigin-premium-animate',
			plugin_dir_url( __FILE__ ) . 'css/animate' . self::$js_suffix . '.css',
			array( ),
			SITEORIGIN_PREMIUM_VERSION
		);
	}

	/**
	 * Get a form instance.
	 *
	 * @param $name_prefix
	 * @param $form_options
	 *
	 * @return SiteOrigin_Premium_Form
	 */
	function get_form( $name_prefix, $form_options ){
		if( ! class_exists( 'SiteOrigin_Premium_Form' ) ) {
			include plugin_dir_path( SITEORIGIN_PREMIUM_BASE_FILE ) . 'inc/form.php';
		}

		return new SiteOrigin_Premium_Form(
			$name_prefix,
			$form_options
		);
	}

	/**
	 * @param $links
	 *
	 * @return $links
	 */
	function add_action_links( $links ){
		unset( $links['edit'] );
		$links['addons'] = '<a href="' . esc_url( admin_url( 'admin.php?page=siteorigin-premium-addons' ) ) . '">' . __( 'Addons', 'siteorigin-premium' ) . '</a>';
		$links['license'] = '<a href="' . esc_url( admin_url( 'admin.php?page=siteorigin-premium-license' ) ) . '">' . __( 'License', 'siteorigin-premium' ) . '</a>';

		return $links;
	}

}

SiteOrigin_Premium_Manager::single();
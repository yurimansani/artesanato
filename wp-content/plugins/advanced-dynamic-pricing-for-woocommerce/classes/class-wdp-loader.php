<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WDP_Loader {
	const db_version = "wdp_db_version";
	
	public static function install() {
		WDP_Database::create_database();
	}
	
	public static function deactivate() {
		delete_option( WDP_Settings::$activation_notice_option );
	}
	
	public static function uninstall() {
		// delete tables  only if have value in settings
		$options = get_option( 'wdp_settings', array() );
		if( isset($options['uninstall_remove_data']) AND $options['uninstall_remove_data'])
			WDP_Database::delete_database();
	}
	
	public function __construct() {
		//should wait a bit
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
	}
	
	public function init_plugin() {
		load_plugin_textdomain( 'advanced-dynamic-pricing-for-woocommerce', FALSE, basename( dirname( dirname( __FILE__ ) ) ) . '/languages/' );

		if ( ! self::check_requirements() ) {
			return;
		}
		
		self::check_db_version();

		include_once WC_ADP_PLUGIN_PATH . 'classes/common/class-wdp-helpers.php';
		include_once WC_ADP_PLUGIN_PATH . 'classes/admin/class-wdp-customizer.php';

		if ( is_admin() ) {
			include_once WC_ADP_PLUGIN_PATH . 'classes/admin/class-wdp-settings.php';
			new WDP_Settings();// it will load core on demand
		}

		$options = WDP_Helpers::get_settings();
		include_once WC_ADP_PLUGIN_PATH . 'classes/class-wdp-frontend.php';
		if ( ! is_admin() || $options['load_in_backend'] || WDP_Frontend::is_nopriv_ajax_processing() ) {
			new WDP_Frontend(); // it will load core on demand
		}
	}
	
	public static function check_db_version() {
		$version = get_option( self::db_version, "" );
		if( $version != WC_ADP_VERSION ) {
			//upgrade db
			WDP_Database::create_database();
			update_option( self::db_version, WC_ADP_VERSION, false );
		}
	}

	public static function check_requirements() {
		$state = true;
		if ( version_compare( phpversion(), WC_ADP_MIN_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( '<strong>Advanced Dynamic Pricing for WooCommerce</strong> requires PHP version %s or later.',
						'advanced-dynamic-pricing-for-woocommerce' ), WC_ADP_MIN_PHP_VERSION ) . '</p></div>';
			} );
			$state = false;
		} elseif ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . __( '<strong>Advanced Dynamic Pricing for WooCommerce</strong> requires active WooCommerce!',
						'advanced-dynamic-pricing-for-woocommerce' ) . '</p></div>';
			} );
			$state = false;
		} elseif ( version_compare( WC_VERSION, WC_ADP_MIN_WC_VERSION, '<' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( '<strong>Advanced Dynamic Pricing for WooCommerce</strong> requires WooCommerce version %s or later.',
						'advanced-dynamic-pricing-for-woocommerce' ), WC_ADP_MIN_WC_VERSION ) . '</p></div>';
			} );
			$state = false;
		}

		return $state;
	}

	public static function load_core() {
		//Advanced classes
		$extension_file = WC_ADP_PLUGIN_PATH . 'pro_version/loader.php';
		if ( file_exists( $extension_file ) ) {
			include_once $extension_file;
		}

		//Contracts
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/contracts/contract-*.php' ) as $filename ) {
			include_once $filename;
		}

		//Traits
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/traits/trait-*.php' ) as $filename ) {
			include_once $filename;
		}

		// Engine
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/engine/class-*.php' ) as $filename ) {
			include_once $filename;
		}

		do_action( 'wdp_include_core_classes' );

		//Limits
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/limits/class-*.php' ) as $filename ) {
			include_once $filename;
		}
		do_action( 'wdp_include_limits' );

		//Conditions
		include_once WC_ADP_PLUGIN_PATH . 'classes/conditions/abstract-wdp-condition.php';
		include_once WC_ADP_PLUGIN_PATH . 'classes/conditions/abstract-wdp-condition-cart-items.php';
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/conditions/class-*.php' ) as $filename ) {
			include_once $filename;
		}
		do_action( 'wdp_include_conditions' );

		//Cart adjustments
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/cart_adjustments/class-*.php' ) as $filename ) {
			include_once $filename;
		}
		do_action( 'wdp_include_cart_adjustments' );

		//Registries
		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/registries/class-*.php' ) as $filename ) {
			include_once $filename;
		}

		//Rules
		include_once WC_ADP_PLUGIN_PATH . 'classes/rules/class-wdp-rule-product-package.php';
//		foreach ( glob( WC_ADP_PLUGIN_PATH . 'classes/rules/class-wdp-rule-*.php' ) as $filename )
//			include_once $filename;
	}
	
	public static function is_pro_version() {
		return defined('WC_ADP_PRO_VERSION_PATH');
	}
}
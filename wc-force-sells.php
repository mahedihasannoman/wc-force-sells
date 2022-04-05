<?php
/**
 * Plugin Name: WC Force Sells
 * Plugin URI:  https://www.braintum.com
 * Description: WC Force Sells is an Addon plugin for Woocommerce that allows you to select products which will be used as force-sells items which get added to the cart along with other items.
 * Version:     1.0.0
 * Author:      Md. Mahedi Hasan
 * Author URI:  https://www.braintum.com
 * Donate link: https://www.braintum.com
 * License:     GPLv2+
 * Text Domain: wc-force-sells
 * Domain Path: /i18n/languages/
 * Tested up to: 5.4
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.1
 */

/**
 * Copyright (c) 2019 Braintum (email : mahedi@braintum.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main initiation class
 *
 * @since 1.0.0
 */

/**
 * Main BT_WC_Force_Sells Class.
 *
 * @class BT_WC_Force_Sells
 */
final class BT_WC_Force_Sells {
	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @var BT_WC_Force_Sells
	 */
	protected static $instance = null;

	/**
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_name = 'WC Force Sells';
	/**
	 * BT_WC_Force_Sells version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * admin notices
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Meta data for synced products.
	 * 
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $synced_types = array(
		'normal' => array(
			'field_name' => 'wc_force_sell_ids',
			'meta_name'  => '_wc_force_sell_ids',
		),
		'synced' => array(
			'field_name' => 'wc_force_sell_synced_ids',
			'meta_name'  => '_wc_force_sell_synced_ids',
		),
	);

	/**
	 * Main BT_WC_Force_Sells Instance.
	 *
	 * Ensures only one instance of BT_WC_Force_Sells is loaded or can be loaded.
	 *
	 * @return BT_WC_Force_Sells - Main instance.
	 *
	 * @since 1.0.0
	 *
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * EverProjects Constructor.
	 */
	public function setup() {
		$this->define_constants();
		add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );
		add_action( 'admin_notices', array( $this, 'woocommerce_admin_notices' ) );
        add_action( 'init', array( $this, 'localization_setup' ) );
    	register_activation_hook( __FILE__, [ $this, 'activate' ] );
	    register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	/**
	 * Define EverProjects Constants.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function define_constants() {
		define( 'BT_WC_FORCE_SELLS_VERSION', $this->version );
		define( 'BT_WC_FORCE_SELLS_FILE', __FILE__ );
		define( 'BT_WC_FORCE_SELLS_PATH', dirname( BT_WC_FORCE_SELLS_FILE ) );
		define( 'BT_WC_FORCE_SELLS_INCLUDES', BT_WC_FORCE_SELLS_PATH . '/includes' );
		define( 'BT_WC_FORCE_SELLS_URL', plugins_url( '', BT_WC_FORCE_SELLS_INCLUDES ) );
		define( 'BT_WC_FORCE_SELLS_ASSETS_URL', BT_WC_FORCE_SELLS_URL . '/assets' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( ! $this->is_wc_installed() ) {
			return;
		}
		//core
		include_once BT_WC_FORCE_SELLS_INCLUDES . '/class-actions.php';

		do_action( 'bt_wc_force_sells_loaded' );
	}

	/**
	 * What type of request is this?
	 *
	 * @param string $type admin, ajax, cron or frontend.
	 *
	 * @return string
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}

	/**
     * Trigger when plugin activate
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public static function activate() {
		delete_option( 'wc_force_sells_version' );
		add_option( 'wc_force_sells_version', bt_wc_force_sells()->version );
    }

    /**
     * Trigger when plugin deactivate
     * 
     * @since 1.0.0
     *
     * @return void
     */
    public static function deactivate() {
	}

	/**
	 * Initialize plugin for localization
	 *
	 * @return void
     * 
	 * @since 1.0.0
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-force-sells', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages/' );
	}

	/**
	 * Determines if the woocommerce installed.
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_wc_installed() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active( 'woocommerce/woocommerce.php' ) == true;
	}

	/**
	 * Adds notices if the wocoomerce is not activated
	 *
	 * @return null
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_admin_notices() {
		if ( false === $this->is_wc_installed() ) {
			?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'Woocommerce is not installed or inactive. Please install and active woocommerce plugin.', 'wc-force-sells' ); ?></p>
            </div>
			<?php
		}
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
     * 
	 * @since 1.0.0
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', BT_WC_FORCE_SELLS_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
     * 
	 * @since 1.0.0
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( BT_WC_FORCE_SELLS_FILE ) );
	}

    /**
     * Include necessary files
     * If Woocommerce is activated
     * Callback for woocommerce_loaded hook
     * 
     * @since 1.0.0
     *
     * @return void
     */
	public function init_plugin() {
		//Include necessary files
        $this->includes();
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 * 
	 * @since 1.0.0
	 *
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-force-sells' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 * 
	 * @since 1.0.0
	 *
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wc-force-sells' ), '1.0.0' );
	}

}

/**
 * The main function responsible for returning the one true WC Force Sells
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return BT_WC_Force_Sells
 * @since 1.0.0
 */
function bt_wc_force_sells() {
	return BT_WC_Force_Sells::instance();
}

//lets go.
bt_wc_force_sells();

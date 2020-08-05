<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://postapanduri.ro
 * @since      1.0.0
 *
 * @package    PostaPanduri
 * @subpackage PostaPanduri/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PostaPanduri
 * @subpackage PostaPanduri/includes
 * @author     Adrian Lado <adrian@plationline.eu>
 */

namespace PostaPanduri\Inc\Core;

use PostaPanduri as NS;
use PostaPanduri\Inc\Admin as Admin;
use PostaPanduri\Inc\Front as Front;
use PostaPanduri\Inc\Admin\SettingsPage as SettingsPage;
use PostaPanduri\Inc\Core\WC_PostaPanduri as WC_PostaPanduri;
use PostaPanduri\Inc\Core\Internationalization_I18n as Internationalization_I18n;


class Init
{
	protected $plugin_basename;
	protected $plugin_name;
	protected $version;
	protected $plugin_text_domain;

	public function __construct()
	{
		$this->plugin_name = NS\PLUGIN_NAME;
		$this->version = NS\PLUGIN_VERSION;
		$this->plugin_basename = NS\PLUGIN_BASENAME;
		$this->plugin_text_domain = NS\PLUGIN_TEXT_DOMAIN;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->load_woocommerce_class();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PostaPanduri_Loader. Orchestrates the hooks of the plugin.
	 * - PostaPanduri_i18n. Defines internationalization functionality.
	 * - PostaPanduri_Admin. Defines all hooks for the admin area.
	 * - PostaPanduri_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		$this->loader = new Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PostaPanduri_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		$plugin_i18n = new Internationalization_I18n($this->plugin_text_domain);
		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	private function load_woocommerce_class()
	{
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			$this->loader->add_action('woocommerce_shipping_init', $this, 'postapanduri_init');
			$this->loader->add_action('init', $this, 'postapanduri_init_order_status');
		}
	}

	public function postapanduri_init_order_status()
	{
		if (\class_exists('WC_Shipping_Method')) {
			$pp = new WC_PostaPanduri();
			$pp::pp_register_shipment_status();
		}
	}

	public function postapanduri_init()
	{
		add_filter('woocommerce_shipping_methods', array($this, 'add_postapanduri'));
	}

	public function add_postapanduri($methods)
	{
		$methods['postapanduri'] = 'PostaPanduri\Inc\Core\WC_PostaPanduri';
		return $methods;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Admin\Admin($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

		if (is_admin()) {
			$postapanduri_settings_page = new SettingsPage($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());
		}

		$this->loader->add_action('wp_ajax_genereaza_awb', $plugin_admin, 'genereaza_awb');
		$this->loader->add_action('wp_ajax_cancel_awb', $plugin_admin, 'cancel_awb');
		$this->loader->add_action('wp_ajax_tracking_awb', $plugin_admin, 'tracking_awb');
		$this->loader->add_action('woocommerce_order_status_changed', $plugin_admin, 'order_status_changed', 10, 3);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Front\Front($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
		$this->loader->add_action('wp_ajax_ajax_get_localitati', $plugin_public, 'ajax_get_localitati');
		$this->loader->add_action('wp_ajax_nopriv_ajax_get_localitati', $plugin_public, 'ajax_get_localitati');
		$this->loader->add_action('wp_ajax_ajax_get_pachetomate', $plugin_public, 'ajax_get_pachetomate');
		$this->loader->add_action('wp_ajax_nopriv_ajax_get_pachetomate', $plugin_public, 'ajax_get_pachetomate');
		$this->loader->add_action('wp_ajax_ajax_get_pachetomat', $plugin_public, 'ajax_get_pachetomat');
		$this->loader->add_action('wp_ajax_nopriv_ajax_get_pachetomat', $plugin_public, 'ajax_get_pachetomat');

		$this->loader->add_action('woocommerce_after_shipping_rate', $plugin_public, 'pp_action_woocommerce_after_shipping_rate', 10, 2);
		$this->loader->add_action('woocommerce_checkout_update_order_review', $plugin_public, 'pp_action_woocommerce_checkout_update_order_review');
		$this->loader->add_action('woocommerce_checkout_update_order_review', $plugin_public, 'pp_clear_wc_shipping_rates_cache');
		$this->loader->add_action('woocommerce_view_order', $plugin_public, 'postapanduri_tracking_awb');
		$this->loader->add_filter('woocommerce_cart_ready_to_calc_shipping', $plugin_public, 'pp_disable_shipping_calc_on_cart');

		$this->loader->add_filter('woocommerce_api_wc_postapanduri_issn', $plugin_public, 'process_issn');

		$this->loader->add_action('woocommerce_after_checkout_validation', $plugin_public, 'postapanduri_validate_order', 10);
		$this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_public, 'pp_add_delivery_point_id', 10, 2);
		$this->loader->add_filter('woocommerce_thankyou_order_received_text', $plugin_public, 'pp_filter_woocommerce_thankyou_order_received_text', 10, 2);
		$this->loader->add_action('before_woocommerce_pay', $plugin_public, 'pp_action_before_woocommerce_pay', 10, 2);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    PostaPanduri_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version()
	{
		return $this->version;
	}

	public function get_plugin_text_domain()
	{
		return $this->plugin_text_domain;
	}
}

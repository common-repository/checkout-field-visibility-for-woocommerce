<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/includes
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
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/includes
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Wp_Woo_Checkout_Vis_Fields
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Woo_Checkout_Vis_Fields_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier for Zamartz admin to identify between different input fields.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $plugin_input_prefix    The string used to uniquely identify input fields in Zamartz admin.
	 */
	public $plugin_input_prefix;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public $plugin_name;

	/**
	 * The unique display name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $plugin_display_name    The string used to store the display name of this plugin.
	 */
	public $plugin_display_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The purchase URL of the current plugin on Zamartz marketplace
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $zamartz_plugin_url    Stores the Zamartz marketplace plugin URL
	 */
	public $zamartz_plugin_url;

	/**
	 * Stores all path information of the current plugin.
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $plugin_url    Plugin url data.
	 */
	public $plugin_url;

	/**
	 * Variable that stores all error messages during plugin activation
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $error_message    Stores all plugin activation base error message.
	 */
	protected $error_message;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		global $zamartz_admin_version;
		if (defined('WP_WOO_CHECKOUT_VIS_FIELDS_VERSION')) {
			$this->version = WP_WOO_CHECKOUT_VIS_FIELDS_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		if (defined('WP_WOO_CHECKOUT_VIS_FIELDS_DIR_SLUG')) {
			$this->plugin_name = WP_WOO_CHECKOUT_VIS_FIELDS_DIR_SLUG;
		} else {
			$this->plugin_name = plugin_basename(dirname(__DIR__));
		}

		$this->plugin_input_prefix = 'woo_checkout_';
		$zamartz_admin_version[$this->plugin_input_prefix] = array();
		$this->plugin_display_name = 'Woo Checkout Field Visibility';
		$this->legacy_plugin_path = 'woohide-hide-billing-fields/woocommerce-hide-billing-fields.php';
		$this->zamartz_plugin_url = 'https://zamartz.com/product/woocommerce-checkout-field-visibility/';
		$this->woocommerce_plugin_path = 'woocommerce/woocommerce.php';
		$this->set_plugin_url();

		$this->check_dependencies();
		$this->load_dependencies();

		//Add filter on plugins loaded to define Zamartz Admin version
		add_filter('plugins_loaded', array($this, 'set_zamartz_admin_version'), 11);
		add_filter('plugins_loaded', array($this, 'load_addon_dependencies'), 13);

		if (!empty($this->error_message)) {
			if (is_network_admin()) {
				add_action('network_admin_notices', array($this, 'render_error_message'));
			} else {
				add_action('admin_notices', array($this, 'render_error_message'));
			}
			return;
		}

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$plugin = $this->plugin_url['base_plugin_name'];
		add_filter("plugin_action_links_$plugin", array($this, 'plugin_settings_link'), 10, 1);
		add_filter("network_admin_plugin_action_links_$plugin", array($this, 'plugin_settings_link'), 10, 1);
	}

	/**
	 * The function stores the information of all paths within the plugin
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_plugin_url()
	{

		//Base plugin name
		if (defined('WP_WOO_CHECKOUT_VIS_FIELDS_DIR_FILE_SLUG')) {
			$this->plugin_url['base_plugin_name'] = WP_WOO_CHECKOUT_VIS_FIELDS_DIR_FILE_SLUG;
		} else {
			$this->plugin_url['base_plugin_name'] = $this->plugin_name . '/' . $this->plugin_name . '.php';
		}
		//Base plugin path
		$this->plugin_url['base_plugin_path'] = plugin_dir_path(dirname(__FILE__));
		//Base plugin url
		$this->plugin_url['base_url'] = plugins_url($this->get_plugin_name());
		//Base public path
		$this->plugin_url['public_path'] = $this->plugin_url['base_plugin_path'] . 'public';
		//Base admin path
		$this->plugin_url['admin_path'] = $this->plugin_url['base_plugin_path'] . 'admin';
		//Base admin > images path
		$this->plugin_url['admin_image_path'] = $this->plugin_url['admin_path'] . '/images';
		//Base admin > images url
		$this->plugin_url['image_url'] = $this->plugin_url['base_url'] . '/admin/images';
		//Base include path
		$this->plugin_url['includes_path'] = $this->plugin_url['base_plugin_path'] . 'includes';
	}

	/**
	 * Display plugin settings link
	 * 
	 * @params	array	$links	Current links displaying on the plugin page
	 */
	public function plugin_settings_link($links)
	{
		$section = str_replace('-', '_', $this->plugin_name);
		$add_link = is_network_admin() ? 'page=zamartz-network-settings&tab=addons' : 'page=zamartz-settings&tab=addons&section=' . $section;
		$settings_link = '<a href="admin.php?' . $add_link . '">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Woo_Checkout_Vis_Fields_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Woo_Checkout_Vis_Fields_i18n. Defines internationalization functionality.
	 * - Wp_Woo_Checkout_Vis_Fields_Admin. Defines all hooks for the admin area.
	 * - Wp_Woo_Checkout_Vis_Fields_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once $this->plugin_url['includes_path'] . '/class-wp-checkout-vis-fields-woo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once $this->plugin_url['includes_path'] . '/class-wp-checkout-vis-fields-woo-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once $this->plugin_url['admin_path'] . '/class-wp-checkout-vis-fields-woo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once $this->plugin_url['public_path'] . '/class-wp-checkout-vis-fields-woo-public.php';

		$this->loader = new Wp_Woo_Checkout_Vis_Fields_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Woo_Checkout_Vis_Fields_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Wp_Woo_Checkout_Vis_Fields_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
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

		$plugin_admin = new Wp_Woo_Checkout_Vis_Fields_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 11);
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 11);
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

		$plugin_public = new Wp_Woo_Checkout_Vis_Fields_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		if (empty($this->error_message)) {
			$this->loader->run();
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Woo_Checkout_Vis_Fields_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Check if plugin is active for the current blog
	 * 
	 * @param   string  $plugin_to_test		The plugin path to test
	 */
	public function is_plugin_active_for_blog($plugin_to_test)
	{

		if (is_multisite() && is_network_admin()) {
			$plugins = get_site_option('active_sitewide_plugins');
			return isset($plugins[$plugin_to_test]);
		} elseif (is_multisite() && !is_network_admin()) {
			$plugins = get_site_option('active_sitewide_plugins');
			$is_network_plugin_exists = isset($plugins[$plugin_to_test]);
			//Get active plugin list for current blog
			if (!$is_network_plugin_exists) {
				$blog_id = get_current_blog_id();
				$active_plugins = get_blog_option($blog_id, 'active_plugins');
				return in_array($plugin_to_test, $active_plugins);
			} else {
				return true;
			}
		} else {
			$active_plugins = get_option('active_plugins');
			return in_array($plugin_to_test, $active_plugins);
		}
	}

	/**
	 * Check plugin dependencies and update error message (if any) accordingly
	 * 
	 * Plugin requires WooCommerce to be enable. 
	 * Plugin requires WooCommerce Hide Billing Fields to be disabled.
	 * Update admin notice and with respective error message if conditions fail.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function check_dependencies()
	{
		$is_deactivate  = false;

		if (!is_network_admin() && !$this->is_plugin_active_for_blog($this->woocommerce_plugin_path)) {
			$is_deactivate = true;
			$this->error_message[] = $this->plugin_display_name . ' requires <a href="https://wordpress.org/plugins/woocommerce/">WooCommerce</a> 3.6 or greater to be installed and active.';
		}

		if ($this->is_plugin_active_for_blog($this->legacy_plugin_path)) {
			$is_deactivate = true;
			$this->error_message[] = 'WooCommerce Hide Billing Fields plugin is currently active, please deactivate to activate ' . $this->plugin_display_name . ' plugin.';
		}

		if ($is_deactivate) {
			add_action('admin_init', array($this, 'deactivate_self'));
		}
	}

	/**
	 * Render error messages in admin notices (if any)
	 *
	 * @since    1.0.0
	 */
	public function render_error_message()
	{
		if (!empty($this->error_message) && count($this->error_message) > 0) {
?>
			<div class="error notice">
				<?php foreach ($this->error_message as $message) { ?>
					<p><?php _e($message, 'wp-checkout-vis-fields-woo'); ?></p>
				<?php } ?>
			</div>
<?php
		}
	}

	/**
	 * Deactivates this plugin.
	 * 
	 * @since    1.0.0
	 */
	public function deactivate_self()
	{
		deactivate_plugins($this->plugin_url['base_plugin_name'], false, is_network_admin());
	}

	/**
	 * Set Zamartz Admin version
	 */
	public function set_zamartz_admin_version()
	{
		global $zamartz_admin_version;
		$path = $this->plugin_url['admin_path'] . '/zamartz/class-wp-woo-main-zamartz-admin.php';
		$zamartz_admin_version[$this->plugin_input_prefix] =  array(
			'version' => '2.2.1',
			'path' => $path
		);
	}

	/**
	 * Load plugin specific dependencies including Zamartz Admin
	 */
	public function load_addon_dependencies()
	{
		/**
		 * The class responsible for adding a common Zamartz admin.
		 */
		require_once $this->get_zamartz_admin_path();
		$plugin_data['plugin_url'] = $this->plugin_url;
		$plugin_data['plugin_name'] = $this->plugin_name;
		new Wp_Woo_Main_Zamartz_Admin($plugin_data);

		/**
		 * The class responsible for adding shipping and billing sections for field visibility.
		 */
		require_once $this->plugin_url['admin_path'] . '/class-wp-checkout-vis-fields-woo-settings.php';
		if (class_exists('Woo_Checkout_Visibility_Settings')) {
			new Woo_Checkout_Visibility_Settings($this);
		}

		/**
		 * The class responsible for applying admin ruleset settings to checkout page fields.
		 */
		if (!class_exists('Woo_Checkout_Visibility_Front')) {
			require_once $this->plugin_url['public_path'] . '/class-woo-checkout-vis-fields-front.php';
			new Woo_Checkout_Visibility_Front($this);
		}
	}

	/**
	 * Load plugin specific dependencies including Zamartz Admin
	 */
	public function get_zamartz_admin_path()
	{
		$path = '';
		$version = '0.0.0';
		global $zamartz_admin_version;
		foreach ($zamartz_admin_version as $plugin_prefix => $plugin_data) {
			if ($version < $plugin_data['version']) {
				$version = $plugin_data['version'];
				$path = $plugin_data['path'];
			}
		}
		return $path;
	}
}

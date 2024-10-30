<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://zamartz.com
 * @since             1.0.0
 * @package           Wp_Woo_Checkout_Vis_Fields
 *
 * @wordpress-plugin
 * Plugin Name:       Checkout Field Visibility for WooCommerce
 * Plugin URI:        https://zamartz.com/product/woocommerce-checkout-field-visibility/
 * Description:       The plugin is responsible for hiding billing and shipping fields based on the relevant conditional rule set defined.
 * Version:           1.2.3
 * Author:            Zachary Martz
 * Author URI:        https://zamartz.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-checkout-vis-fields-woo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 */
define('WP_WOO_CHECKOUT_VIS_FIELDS_VERSION', '1.2.2');

/**
 * Current plugin directory slug
 */
define('WP_WOO_CHECKOUT_VIS_FIELDS_DIR_SLUG', plugin_basename(dirname(__FILE__)));

/**
 * Current plugin file path with directory slug
 */
define('WP_WOO_CHECKOUT_VIS_FIELDS_DIR_FILE_SLUG', plugin_basename( __FILE__ ));

if (!isset($zamartz_admin_version)){
	$zamartz_admin_version = array();
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-checkout-vis-fields-woo-activator.php
 */
function activate_wp_woo_checkout_vis_fields()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wp-checkout-vis-fields-woo-activator.php';
	Wp_Woo_Checkout_Vis_Fields_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-checkout-vis-fields-woo-deactivator.php
 */
function deactivate_wp_woo_checkout_vis_fields()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wp-checkout-vis-fields-woo-deactivator.php';
	Wp_Woo_Checkout_Vis_Fields_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_woo_checkout_vis_fields');
register_deactivation_hook(__FILE__, 'deactivate_wp_woo_checkout_vis_fields');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-checkout-vis-fields-woo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_woo_checkout_vis_fields()
{

	$plugin = new Wp_Woo_Checkout_Vis_Fields();
	$plugin->run();
}
run_wp_woo_checkout_vis_fields();

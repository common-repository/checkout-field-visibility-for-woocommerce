<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

$zamartz_admin_event_tracker = get_option('wp_zamartz_admin_event_tracker');
$plugin_input_prefix = 'woo_checkout_';	//Initialize input prefix

$option_list = array(
	//Api credentials
	$plugin_input_prefix . 'cron_log',
	$plugin_input_prefix . 'api_license_key',
	$plugin_input_prefix . 'api_password',
	$plugin_input_prefix . 'api_product_id',
	$plugin_input_prefix . 'api_purchase_emails',
	$plugin_input_prefix . 'api_get_response',
	$plugin_input_prefix . 'zamartz_api_admin_notice_data',
	$plugin_input_prefix . 'network_admin_api_status',
	//Shipping section
	$plugin_input_prefix . 'shipping_first_name',
	$plugin_input_prefix . 'shipping_last_name',
	$plugin_input_prefix . 'shipping_email_address',
	$plugin_input_prefix . 'shipping_phone_number',
	$plugin_input_prefix . 'shipping_company',
	$plugin_input_prefix . 'shipping_address_line_1',
	$plugin_input_prefix . 'shipping_address_line_2',
	$plugin_input_prefix . 'shipping_country',
	$plugin_input_prefix . 'shipping_state',
	$plugin_input_prefix . 'shipping_city',
	$plugin_input_prefix . 'shipping_postal_code',
	$plugin_input_prefix . 'shipping_conditions',
	$plugin_input_prefix . 'shipping_operator',
	$plugin_input_prefix . 'shipping_condition_subfield',
	$plugin_input_prefix . 'shipping_rule_set_priority',
	$plugin_input_prefix . 'shipping_rule_toggle',
	//Billing section
	$plugin_input_prefix . 'billing_first_name',
	$plugin_input_prefix . 'billing_last_name',
	$plugin_input_prefix . 'billing_email_address',
	$plugin_input_prefix . 'billing_phone_number',
	$plugin_input_prefix . 'billing_company',
	$plugin_input_prefix . 'billing_address_line_1',
	$plugin_input_prefix . 'billing_address_line_2',
	$plugin_input_prefix . 'billing_country',
	$plugin_input_prefix . 'billing_state',
	$plugin_input_prefix . 'billing_city',
	$plugin_input_prefix . 'billing_postal_code',
	$plugin_input_prefix . 'billing_conditions',
	$plugin_input_prefix . 'billing_operator',
	$plugin_input_prefix . 'billing_condition_subfield',
	$plugin_input_prefix . 'billing_rule_set_priority',
	$plugin_input_prefix . 'billing_rule_toggle',
	//Global Add-on settings	
	$plugin_input_prefix . 'hide_shipping_fields_toggle',
	$plugin_input_prefix . 'hide_billing_fields_toggle',
	$plugin_input_prefix . 'results_returned',
);

if (!is_multisite()) {
	//Clear all options
	foreach ($option_list as $option_name) {
		delete_option($plugin_input_prefix . $option_name);
	}
} else {
	// get database of multisites
	global $wpdb;
	// get blog id list
	$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
	// store original id list
	$original_blog_id = get_current_blog_id();
	// cycle through blog ids
	foreach ($blog_ids as $blog_id) {
		switch_to_blog($blog_id);
		//cycle through options
		foreach ($option_list as $option_name) {
			delete_option($plugin_input_prefix . $option_name);
		}
	}
	// Set Back to Current Blog
	restore_current_blog($original_blog_id);
}

if ($zamartz_admin_event_tracker === 'yes') {
	$cache_string = time();
    $tracker_url =  'https://zamartz.com/?api-secure-refrence&nocache='.$cache_string;

	$site_url = get_site_url();
	$site_hash_url = hash('sha256', $site_url);

	$tracker_data = array(
		'v'    => '1',
		'cid' => $site_hash_url,
		't' => 'event',
		'ec' => 'wp-checkout-vis-fields-woo',
		'ea' => 'delete',
		'el' => 'plugin options deleted',
		'ev' => '1',
	);

	wp_remote_request(
		$tracker_url,
		array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'application/json'
			),
			'body'        => wp_json_encode($tracker_data),
			'cookies'     => array(),
		)
	);
}

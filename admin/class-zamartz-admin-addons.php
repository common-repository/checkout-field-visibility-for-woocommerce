<?php

/**
 * The class is responsible for adding sections inside the WooCommerce settings page.
 *
 * @link       https://zamartz.com
 * @since      1.0.0
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/admin
 */

/**
 * WooCommerce settings specific functionality of the plugin.
 *
 * Defines the settings for Zamartz admin settings, add-on tab
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Woo_Checkout_Admin_Settings_Addons
{

    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     * 
     * Incorporate the trait functionalities for HTML template in this class
     * @see     zamartz/helper/trait-zamartz-html-template.php
     * 
     * Incorporate the trait functionalities for API methods in this class
     * @see     zamartz/helper/trait-zamartz-api-methods.php
     */
    use Zamartz_General, Zamartz_HTML_Template, Zamartz_API_Methods;

    /**
     * Loop order defining which accordion should be given priority with open/close state
     * 
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $loop_order    The loop number of each section.
     */
    protected $loop_order;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct($settings_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($settings_instance);

        if (is_multisite()) {
            $blog_id = get_current_blog_id();
            $this->is_cron_log = get_blog_option($blog_id,  $settings_instance->plugin_input_prefix . 'cron_log');
        } else {
            $this->is_cron_log = get_option($settings_instance->plugin_input_prefix . 'cron_log');
        }
        $this->api_license_key = get_option($settings_instance->plugin_input_prefix . 'api_license_key');

        //Set accordion loop number
        $this->set_accordion_loop_order();

        //Set valid product IDs for API integration
        $product_id_array = array(25625, 25626, 25627, 25628);
        $this->set_valid_product_id($product_id_array);

        //Add filter to add/remove sub-navigation for each tab
        add_filter('zamartz_dashboard_accordion_information', array($this, 'get_dashboard_information'), 10, 1);

        //Add filter to add/remove sub-navigation for each tab
        add_filter('zamartz_dashboard_accordion_settings', array($this, 'get_dashboard_settings'), 10, 1);

        //Add filter to add/remove sub-navigation for each tab - Zamartz Admin (HTML Template trait class)
        add_filter('zamartz_settings_subnav', array($this, 'get_section_tab_settings'), 10, 1);

        //Content display settings for add-ons page
        add_action('zamartz_admin_addon_information', array($this, 'get_addon_information'), 10, 1);

        //Content display settings for add-ons page
        add_action('zamartz_admin_addon_settings', array($this, 'get_addon_settings'), 10, 1);

        //Add ajax action to save form data - Zamartz Admin (General trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'form_data_ajax', array($this, 'save_form_data_ajax'));

        //Add ajax action to activate/deactivate plugin - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'activate_ajax', array($this, 'set_api_license_key_ajax'));

        //Add ajax action to activate/deactivate plugin - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'clear_api_credentials_ajax', array($this, 'clear_api_credentials_ajax'));

        //Add ajax to get plugin status - Zamartz Admin (API trait class)
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'get_api_status_ajax', array($this, 'get_api_status_ajax'));

        //Create twice monthly cron schedule - Zamartz Admin (API trait class)
        add_filter('cron_schedules', array($this, 'zamartz_interval_twice_monthly'));

        //Run the API cron scheduler handler twice a month to check for API handshake - Zamartz Admin (API trait class)
        add_action('zamartz_api_cron_schedule_twice_monthly', array($this, 'zamartz_api_cron_schedule_handler'));

        //Create weekly cron schedule - Zamartz Admin (API trait class)
        add_filter('cron_schedules', array($this, 'zamartz_interval_weekly'));

        //Run the API cron scheduler handler weekly for disabling API paid features (if needed) - Zamartz Admin (API trait class)
        add_action('zamartz_api_cron_schedule_admin_notice', array($this, 'zamartz_disable_paid_features'));

        //Add admin notice if any - Zamartz Admin (API trait class)
        add_action('admin_notices', array($this, 'zamartz_api_admin_notice'));

        //Add ajax action to activate/deactivate plugin
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'import_settings_ajax', array($this, 'set_import_settings_ajax'));
    }

    /**
     * Get zamartz dasboard add-on accordion settings
     * 
     * @since    1.0.0
     */
    public function get_dashboard_information($dashboard_information)
    {
        if (!empty($dashboard_information) && $dashboard_information != null) {
            return $dashboard_information;
        }
        $dashboard_information = array(
            'title' => __('Dashboard', "wp-checkout-vis-fields-woo"),
            'description' => __("This dashboard will show all of the most recent update and activity for the ZAMARTZ family of Wordpress extensions.", "wp-checkout-vis-fields-woo")
        );
        return $dashboard_information;
    }

    /**
     * Get zamartz dasboard add-on accordion settings
     * 
     * @since    1.0.0
     */
    public function get_dashboard_settings($table_row_data)
    {
        $plugin_info = $this->get_plugin_info();
        $addon_settings_link = admin_url() . 'admin.php?page=zamartz-settings&tab=addons&section=' . $this->get_plugin_section_slug();
        $image_url = '<a href="' . $addon_settings_link . '">
                        <img title="' . $this->plugin_display_name . '" alt="Thumbnail for ' . $this->plugin_display_name . ', click for settings" src="' . $this->plugin_url['image_url'] . '/dashboard-default.png">
                        </a>';
        $feed_title = '<a alt="Title for ' . $this->plugin_display_name . ', click for settings" href="' . $addon_settings_link . '">' . $this->plugin_display_name . '</a>';
        $table_row_data[] = array(
            'data' => array(
                $image_url,
                '<p class="feed-item-title">' . $feed_title . '</p>
                 <p tabindex="0">' . $plugin_info['Description'] . '</p>',
            ),
            'row_class' => 'feed-row-content',
        );

        return $table_row_data;
    }

    /**
     * Add-on information for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_addon_information($addon_information)
    {
        $addon_information[$this->get_plugin_section_slug()] = array(
            'title' => $this->plugin_display_name,
            'description' => __("These Add-Ons provide functionality to existing Wordpress functionality or other extensions and plugins", "wp-checkout-vis-fields-woo"),
            'input_prefix' => $this->plugin_input_prefix
        );
        return $addon_information;
    }

    /**
     * Add-on settings for zamartz admin
     * 
     * @since   1.0.0
     */
    public function get_addon_settings($addon_settings)
    {
        //Get get_functionality settings
        $content_array['column_array'][] = $this->get_functionality_settings();

        //Get license settings
        $content_array['column_array'][] = $this->get_license_settings();

        //Check if woo checkout plugin is paid
        if ($this->plugin_api_version !== 'Free') {
            //Get advanced settings
            $description = "Use this button to import settings from WooCommerce Hide Billing Fields. This will always override Rule Set #1.";
            $content_array['column_array'][] = $this->get_advanced_settings($description);
        }

        //Define page structure
        $content_array['page_structure'] = array(
            'desktop_span' => '75',
            'mobile_span' => '100',
        );

        $plugin_section_slug = $this->get_plugin_section_slug();
        $addon_settings[$plugin_section_slug][] = $content_array;

        //Get sidebar settings
        $addon_settings[$plugin_section_slug]['sidebar-settings'] = $this->get_sidebar_settings();

        return $addon_settings;
    }

    /**
     * Functionality settings inside the add-on tab
     */
    public function get_functionality_settings()
    {
        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => false,
            'accordion_class' => 'zamartz-addon-settings',
            'accordion_loop' => $this->loop_order['zamartz_functionality_settings'],
            'form_section_data' => array(
                'linked_class' => 'zamartz-addon-settings'
            ),
            'title' => __("Functionality", "wp-checkout-vis-fields-woo")
        );

        $hide_shipping_fields_toggle = get_option($this->plugin_input_prefix . 'hide_shipping_fields_toggle');
        $hide_billing_fields_toggle = get_option($this->plugin_input_prefix . 'hide_billing_fields_toggle');
        $results_returned = get_option($this->plugin_input_prefix . 'results_returned');

        //Define table data
        $table_section_array = array(
            array(
                'title' =>  __("Hide Shipping Fields", "wp-checkout-vis-fields-woo"),
                'tooltip_desc' =>  __("This option will enable or disable the 'Hide Shipping Fields' section of the Add-On in the WooCommerce Settings", "wp-checkout-vis-fields-woo"),
                'type' => 'toggle_switch',
                'option_settings' => array(
                    'name' => "woo_checkout_hide_shipping_fields_toggle",
                ),
                'input_value' => $hide_shipping_fields_toggle,
                'additional_content' => '
                    <div class="additional-content">
                        <a href="' . admin_url() . 'admin.php?page=wc-settings&tab=shipping&section=shipping_field_visibility' . '">
                        ' . __("Configure shipping fields", "wp-checkout-vis-fields-woo") . '
                        </a>
                    </div>'
            ),
            array(
                'title' =>  __("Hide Billing Fields", "wp-checkout-vis-fields-woo"),
                'tooltip_desc' =>  __("This option will enable or disable the 'Hide Billing Fields' section of the Add-On in the WooCommerce Settings", "wp-checkout-vis-fields-woo"),
                'type' => 'toggle_switch',
                'option_settings' => array(
                    'name' => "woo_checkout_hide_billing_fields_toggle",
                ),
                'input_value' => $hide_billing_fields_toggle,
                'additional_content' => '
                    <div class="additional-content">
                        <a href="' . admin_url() . 'admin.php?page=wc-settings&tab=billing_field_visibility' . '">
                        ' . __("Configure billing fields", "wp-checkout-vis-fields-woo") . '
                        </a>
                    </div>'
            ),
            array(
                'title' =>  __("Results returned", "wp-checkout-vis-fields-woo"),
                'tooltip_desc' => __("This will set the number of results returned in any dynamic search fields before the first pagination takes effect. Such as a category dropdown.", "wp-checkout-vis-fields-woo"),
                'type' => 'input_number',
                'option_settings' => array(
                    'name' => "woo_checkout_results_returned",
                    'min' => 7
                ),
                'input_value' => $results_returned
            ),
        );

        //Define table parameters
        $table_params = array(
            'form_data' => [],
            'section_type' => 'zamartz_functionality_settings',
        );

        return array(
            'accordion_settings' => $accordion_settings,
            'table_section_array' => $table_section_array,
            'table_params' => $table_params,
        );
    }

    /**
     * Functionality to import settings from legacy plugin and override Rule Set #1
     * 
     * @since   1.0.0
     */
    public function set_import_settings_ajax()
    {
        //Verify nonce
        $settings_nonce = filter_input(INPUT_POST, 'settings_nonce', FILTER_SANITIZE_STRING);
        if (!wp_verify_nonce(wp_unslash($settings_nonce), 'zamartz-settings')) {
            echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
            die();
        }

        //Check if data exists in database for old and new plugin
        $is_woo_checkout_exists = get_option("{$this->plugin_input_prefix}billing_first_name");
        $is_hide_billing_exists = get_option("wcbilling_hide_fn");

        //Check if legacy plugin data exists
        if ($is_hide_billing_exists === false) {
            echo json_encode(
                array(
                    'status' => false,
                    'message' => __('Legacy plugin data does not exist. No ruleset changes applied.', "wp-checkout-vis-fields-woo")
                )
            );
            die();
        }

        //Get legacy plugin values
        $legacy_plugin_key = array(
            $this->plugin_input_prefix . 'billing_first_name' => 'wcbilling_hide_fn',
            $this->plugin_input_prefix . 'billing_last_name' => 'wcbilling_hide_ln',
            $this->plugin_input_prefix . 'billing_email_address' => 'wcbilling_hide_email',
            $this->plugin_input_prefix . 'billing_company' => 'wcbilling_hide_company',
            $this->plugin_input_prefix . 'billing_country' => 'wcbilling_hide_country',
            $this->plugin_input_prefix . 'billing_address_line_1' => 'wcbilling_hide_address1',
            $this->plugin_input_prefix . 'billing_address_line_2' => 'wcbilling_hide_address2',
            $this->plugin_input_prefix . 'billing_city' => 'wcbilling_hide_city',
            $this->plugin_input_prefix . 'billing_state' => 'wcbilling_hide_state',
            $this->plugin_input_prefix . 'billing_postal_code' => 'wcbilling_hide_postcode',
            $this->plugin_input_prefix . 'billing_phone_number' => 'wcbilling_hide_phone'
        );

        //Check if woo checkout plugin has data in database
        if ($is_woo_checkout_exists !== false && !empty($is_woo_checkout_exists) && is_array($is_woo_checkout_exists)) {
            global $wpdb;

            //Get first integer key of our defined rulesets
            $first_ruleset_key = key($is_woo_checkout_exists);

            //Override Ruleset #1
            foreach ($legacy_plugin_key as $new_key => $legacy_key) {
                $woo_checkout_data = get_option($new_key);
                $legacy_data_by_key = get_option($legacy_key);

                //Check if Woo Checkout plugin 
                $woo_checkout_data[$first_ruleset_key] = $legacy_data_by_key;
                update_option($new_key, $woo_checkout_data);
                if (!empty($wpdb->last_error)) {
                    echo json_encode(
                        array(
                            'status' => false,
                            'message' => __('Error: There was a problem while overriding ruleset #1', "wp-checkout-vis-fields-woo")
                        )
                    );
                    die();
                }
            }

            //Import Zero Order Total checkbox for ruleset #1
            $this->woo_checkout_set_zero_total($first_ruleset_key);

            echo json_encode(
                array(
                    'status' => true,
                    'message' => __('Success: Settings have been imported. Ruleset #1 overridden.', "wp-checkout-vis-fields-woo")
                )
            );
            die();
        } elseif ($is_woo_checkout_exists === false) {
            //Create Ruleset #1
            foreach ($legacy_plugin_key as $new_key => $legacy_key) {
                $legacy_data_by_key = get_option($legacy_key);

                //$legacy_data_by_key
                $woo_checkout_data = array(1 => $legacy_data_by_key);
                update_option($new_key, $woo_checkout_data);
            }

            //Import Zero Order Total checkbox for ruleset #1
            $this->woo_checkout_set_zero_total(1);

            echo json_encode(
                array(
                    'status' => true,
                    'message' => __('Success: Settings have been imported. Ruleset #1 created.', "wp-checkout-vis-fields-woo")
                )
            );
            die();
        }

        echo json_encode(
            array(
                'status' => false,
                'message' => __('Error: Settings could not be imported', "wp-checkout-vis-fields-woo")
            )
        );
        die();
    }

    /**
     * Define the condition, operator and sub condition value if zero total checkbox is checked in 
     * legacy plugin
     * 
     * @since   1.0.0
     */
    public function woo_checkout_set_zero_total($first_ruleset_key)
    {
        //Import Zero Order Total checkbox for ruleset #1
        $legacy_data_by_key = get_option('wcbilling_hide_zerototal');
        if ($legacy_data_by_key === 'yes') {
            //Checked
            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_conditions');
            $woo_checkout_data[$first_ruleset_key] = 'order_total';
            update_option($this->plugin_input_prefix . 'billing_conditions', $woo_checkout_data);

            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_operator');
            $woo_checkout_data[$first_ruleset_key] = 'greater_than_equal';
            update_option($this->plugin_input_prefix . 'billing_operator', $woo_checkout_data);

            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_condition_subfield');
            $woo_checkout_data[$first_ruleset_key] = '0.00';
            update_option($this->plugin_input_prefix . 'billing_condition_subfield', $woo_checkout_data);
        } else {
            //Unchecked
            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_conditions');
            $woo_checkout_data[$first_ruleset_key] = 'order_total';
            update_option($this->plugin_input_prefix . 'billing_conditions', $woo_checkout_data);

            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_operator');
            $woo_checkout_data[$first_ruleset_key] = 'equal';
            update_option($this->plugin_input_prefix . 'billing_operator', $woo_checkout_data);

            $woo_checkout_data = get_option($this->plugin_input_prefix . 'billing_condition_subfield');
            $woo_checkout_data[$first_ruleset_key] = '0.00';
            update_option($this->plugin_input_prefix . 'billing_condition_subfield', $woo_checkout_data);
        }
    }
}

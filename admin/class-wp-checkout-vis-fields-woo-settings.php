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
 * Defines the settings for billing and shipping
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Woo_Checkout_Visibility_Settings
{

    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     * 
     * Incorporate the trait functionalities for HTML template in this class
     * @see     zamartz/helper/trait-zamartz-html-template.php
     */
    use Zamartz_General, Zamartz_HTML_Template;

    /**
     * Form settings data
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $form_data    Saves the data for our respective section form (shipping|billing).
     */
    private $form_data;

    /**
     * Current WooCommerce settings section
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $section_type    Stores the section currently accessed (shipping|billing).
     */
    public $section_type;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      object    $core_instance     The instance of Wp_Woo_Dis_Comments_And_Ratings class
     */
    public function __construct($core_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($core_instance);

        //Set plugin paid vs free information
        $this->set_plugin_api_data();

        if (class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-admin-addons.php';
            new Woo_Checkout_Admin_Settings_Addons($this);
        }

        if (class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-admin-status.php';
            new Woo_Checkout_Admin_Status($this);
        }

        //Content display settings for Network Admin add-ons page
        if ((is_network_admin() || wp_doing_ajax()) && class_exists('Wp_Woo_Main_Zamartz_Admin')) {
            require_once $this->plugin_url['admin_path'] . '/class-zamartz-network-admin-addons.php';
            new Woo_Checkout_Network_Admin_Settings_Addons($this);
        }


        //Add modal to plugin page
        add_action('admin_footer', array($this, 'get_deactivation_plugin_modal'));

        //Add modal to plugin page
        add_action('wp_ajax_' . $this->plugin_input_prefix . 'deactitvate_plugin', array($this, 'zamartz_deactitvate_plugin'));

        //Customize WP query
        add_filter('posts_where', array($this, "{$this->plugin_input_prefix}set_post_where_wp_query"), 10, 2);

        // Shipping section
        $enable_shipping_section = get_option("{$this->plugin_input_prefix}hide_shipping_fields_toggle");
        if ($enable_shipping_section === false || $enable_shipping_section == 'yes') {
            // Add menu item to WooCommerce settings
            add_filter('woocommerce_get_sections_shipping', array($this, 'add_shipping_field_visibility_tab'));    //Add filter to define a shipping tab under Shipping section
            add_filter('woocommerce_get_settings_shipping', array($this, 'woo_checkout_field_visibility_get_settings'), 10, 2); //Add filter to define custom settings for our added shipping and billing field visibility sections
        }

        // Billing Section
        $enable_billing_section = get_option("{$this->plugin_input_prefix}hide_billing_fields_toggle");
        if ($enable_billing_section === false || $enable_billing_section == 'yes') {
            // Add menu tab to WooCommerce settings
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_billing_field_visibility_tab'), 50);
            add_action('woocommerce_settings_tabs_billing_field_visibility', array($this, "get_woo_checkout_admin_fields_billing"));
        }

        // Add ajax functionality
        add_action("wp_ajax_get_form_operator_dropdown_ajax", array($this, 'get_form_operator_dropdown_ajax'));
        add_action("wp_ajax_woo_checkout_vis_field_form_data_ajax", array($this, "woo_checkout_save_form_data_ajax"));
        add_action("wp_ajax_woo_checkout_get_form_section_ajax", array($this, "woo_checkout_get_form_section_ajax"));
        add_action("wp_ajax_woo_checkout_product_variation", array($this, "woo_checkout_get_select2_dropdown_ajax"));
        add_action("wp_ajax_woo_checkout_coupon_is_applied", array($this, "woo_checkout_get_select2_dropdown_ajax"));
    }

    /**
     * Add a shipping tab in WooCommerce Settings > Shippings > Shipping Options called Shipping Field Visibility.
     *
     * @since   1.0.0
     * @param   string  $sections   The name of the current WooCommerce section.
     * @return  string
     */
    public function add_shipping_field_visibility_tab($sections)
    {
        $sections['shipping_field_visibility'] = __("Shipping Field Visibility", "wp-checkout-vis-fields-woo");
        return $sections;
    }

    /**
     * Define the settings that needs to be displayed for shipping and billing section.
     * Shipping section: WooCommerce Settings > Shipping > Shipping field visibility
     * Billing Section: WooCommerce Settings > Billing
     *
     * @since   1.0.0
     * @param   array   $settings           Array of data for form settings to be generated by WooCommerce.
     * @param   string  $current_section    Current WooCommerce section
     * @return  array
     */
    public function woo_checkout_field_visibility_get_settings($settings, $current_section)
    {
        //Check the current section
        if ($current_section == 'shipping_field_visibility') {
            $settings_shipping = array();

            $this->section_type = 'shipping';
            $this->woo_checkout_set_form_data();

            //Display shipping settings html template
            require_once $this->plugin_url['admin_path'] . '/partials/wp-checkout-vis-fields-woo-admin-html-form.php';

            return $settings_shipping;

            /**
             * If not, return the standard settings
             **/
        } else {
            return $settings;
        }
    }

    /**
     * Retrieves the current section type (shipping|billing) and sets the form data.
     *
     * @since   1.0.0
     */
    private function woo_checkout_set_form_data()
    {
        //Set setting ignore list for paid vs free versions
        $this->woo_checkout_set_ignore_list();

        $section_type = $this->section_type;
        //Form account fields
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name"] = get_option("{$this->plugin_input_prefix}{$section_type}_first_name");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name"] = get_option("{$this->plugin_input_prefix}{$section_type}_last_name");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address"] = get_option("{$this->plugin_input_prefix}{$section_type}_email_address");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number"] = get_option("{$this->plugin_input_prefix}{$section_type}_phone_number");

        //Form account field
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_first_name_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_last_name_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_email_address_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_phone_number_switch");

        //Other checkout function
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_term_conditions"] = get_option("{$this->plugin_input_prefix}{$section_type}_term_conditions");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_user_account_combination"] = get_option("{$this->plugin_input_prefix}{$section_type}_user_account_combination");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account"] = get_option("{$this->plugin_input_prefix}{$section_type}_create_account");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_create_account_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_customer_logged_in"] = get_option("{$this->plugin_input_prefix}{$section_type}_customer_logged_in");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_customer_logged_in_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_customer_logged_in_switch");

        //Form address fields
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_company"] = get_option("{$this->plugin_input_prefix}{$section_type}_company");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_1");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_2");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_country"] = get_option("{$this->plugin_input_prefix}{$section_type}_country");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_state"] = get_option("{$this->plugin_input_prefix}{$section_type}_state");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_city"] = get_option("{$this->plugin_input_prefix}{$section_type}_city");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code"] = get_option("{$this->plugin_input_prefix}{$section_type}_postal_code");

        //Form address fields switch
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_company_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_company_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_1_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_2_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_country_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_country_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_state_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_state_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_city_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_city_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_postal_code_switch");

        //Form conditions
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_conditions"] = get_option("{$this->plugin_input_prefix}{$section_type}_conditions");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_operator"] = get_option("{$this->plugin_input_prefix}{$section_type}_operator");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_condition_subfield"] = get_option("{$this->plugin_input_prefix}{$section_type}_condition_subfield");

        //Form rule settings
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_set_priority"] = get_option("{$this->plugin_input_prefix}{$section_type}_rule_set_priority");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_toggle"] = get_option("{$this->plugin_input_prefix}{$section_type}_rule_toggle");

        //Form rule settings
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_ruleset_type"] = get_option("{$this->plugin_input_prefix}{$section_type}_ruleset_type");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_ruleset_message"] = get_option("{$this->plugin_input_prefix}{$section_type}_ruleset_message");
    }

    /**
     * Generates the html to display on shipping|billing section based on the defined form data.
     *
     * @since   1.0.0
     */
    public function woo_checkout_generate_shipping_billing_html()
    {
        if (!empty($this->form_data) && !empty($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_first_name"])) {
            $loop = 1;
            foreach ($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_first_name"] as $key => $value) {
                $this->woo_checkout_get_form_section($key, $loop);
                $loop++;
            }
        } else {
            $this->woo_checkout_get_form_section(1);
        }

        $settings = array(
            'type' => 'button',
            'input_value' => __("Add rule set", "wp-checkout-vis-fields-woo"),
            'option_settings' => array(
                'class' => 'woo-checkout-add-rule-set',
                'is_spinner_dashicon' => true,
                'wrapper' => array(
                    'class' => 'woo-checkout-add-rule-set-wrapper'
                ),
            )
        );
        $this->get_field_settings($settings);
    }

    /**
     * Generates the form section to be displayed in the respective tab
     *
     * @since   1.0.0
     * @param   integer     $key    The index of the array
     * @param   integer     $loop   Variable to define rule number
     */
    public function woo_checkout_get_sidebar_section()
    {
        $table_section_array =
            array(
                'row_data' => array(
                    array(
                        'data' => array(
                            __("Version", "wp-checkout-vis-fields-woo"),
                            $this->plugin_api_version
                        ),
                        'tabindex' => 0
                    ),
                    array(
                        'data' => array(
                            __("Authorization", "wp-checkout-vis-fields-woo"),
                            $this->plugin_api_authorization
                        ),
                        'tabindex' => 0
                    ),
                ),
                'row_footer' => array(
                    'is_link' => array(
                        'link' => admin_url() . 'admin.php?page=zamartz-settings&tab=addons&section=' . $this->get_plugin_section_slug(),
                        'title' => __("Settings", "wp-checkout-vis-fields-woo"),
                        'class' => ''
                    ),
                    'is_button' => array(
                        'name' => 'save',
                        'type' => 'submit',
                        'action' => 'woo_checkout_vis_field_form_data_ajax',
                        'class' => 'button button-primary button-large',
                        'value' => __("Save changes", "wp-checkout-vis-fields-woo"),
                    )
                ),
                'nonce' => wp_nonce_field('zamartz-settings', 'zamartz_settings_nonce', true, false)
            );
        $accordion_settings = array(
            'title' => __(ucfirst($this->section_type) . " Field Visibility Updates", "wp-checkout-vis-fields-woo"),
            'type' => 'save_footer',
            'accordion_class' => 'zamartz-accordion-sidebar',
            'form_section_data' => array(
                'toggle' => 'affix',
                'custom-affix-height' => '88'
            ),
        );

        $this->generate_accordion_html($accordion_settings, $table_section_array);
    }

    /**
     * Generates the form section to be displayed in the respective tab
     *
     * @since   1.0.0
     * @param   integer     $key    The index of the array
     * @param   integer     $loop   Variable to define rule number
     */
    public function woo_checkout_get_form_section($key = 0, $loop = 1)
    {
        $table_params = array(
            'form_data' => $this->form_data,
            'section_type' => $this->section_type,
            'input_prefix' => $this->plugin_input_prefix,
            'is_tristate_button' => ($this->plugin_api_version === 'Paid') ? true : false,
            'key' => $key,
        );

        //Shipping/billing checkbox section
        $table_section_array = $this->woo_checkout_set_form_settings($key);

        //Other checkout section
        $table_section_array = array_merge($table_section_array, $this->get_other_checkout_functions($key));

        //Condition section
        $table_section_array[] = $this->get_form_conditions($key); //Get condition dropdown
        $operator_dropdown = $this->get_form_operator_dropdown($key); //Get operator dropdown
        $operator_dropdown['section_class'] = 'woo-checkout-form-operator';
        $table_section_array[] = $operator_dropdown;

        $condition_subfield = $this->get_form_condition_subfield($key); //Get condition subfield
        $condition_subfield['section_class'] = 'woo-checkout-condition-subfield';
        $table_section_array[] = $condition_subfield;

        //Get rule set section
        $rule_set_priority = $this->get_form_rule_set_priority($key);
        $rule_set_priority['section_class'] = 'zamartz-bordered';
        $table_section_array[] = $rule_set_priority;

        $table_section_array[] = $this->get_form_stop_other_rules($key);

        //Get message section
        $rule_set_message_array = $this->get_form_rule_set_message($key);
        $table_section_array = array_merge($table_section_array, $rule_set_message_array);

        //Define accordion settings
        $accordion_settings = array(
            'type' => 'form_table',
            'is_delete' => true,
            'accordion_class' => 'woo-checkout-form-rule-section',
            'accordion_loop' => $loop,
            'form_section_data' => array(
                'current_key' => $key,
                'linked_class' => 'woo-checkout-form-rule-section'
            ),
            'title' => __('Rule Set #') . '<span class="zamartz-loop-number">' . $loop . '</span>'
        );
        $this->generate_accordion_html($accordion_settings, $table_section_array, $table_params);
    }

    /**
     * Function defines the settings to populate account and address field sections
     * 
     * @since   1.0.0
     */
    public function woo_checkout_set_form_settings($key)
    {
        $table_section_array = array(
            array(
                'title' => __("Hide {$this->section_type} account fields", "wp-checkout-vis-fields-woo"),
                'type' => 'checkbox',
                'field_options' => array(
                    '_first_name' => array(
                        'label' => __(ucfirst($this->section_type) . " First Name", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " First Name may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'First Name' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_last_name' => array(
                        'label' => __(ucfirst($this->section_type) . " Last Name", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Last Name may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Last Name' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_email_address' => array(
                        'label' => __(ucfirst($this->section_type) . " Email Address", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Email Address may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Email Address' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_phone_number' => array(
                        'label' => __(ucfirst($this->section_type) . " Phone Number", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Phone Number may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Phone Number' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    )
                ),
            ),
            array(
                'title' => __("Hide {$this->section_type} address fields", "wp-checkout-vis-fields-woo"),
                'type' => 'checkbox',
                'field_options' => array(
                    '_company' => array(
                        'label' => __(ucfirst($this->section_type) . " Company", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Company may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Company' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_address_line_1' => array(
                        'label' => __(ucfirst($this->section_type) . " Address Line 1", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Address Line 1 may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'First Address Line' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_address_line_2' => array(
                        'label' => __(ucfirst($this->section_type) . " Address Line 2", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Address Line 2 may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'First Address Line 2' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_country' => array(
                        'label' => __(ucfirst($this->section_type) . " Country", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Country may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Country' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_state' => array(
                        'label' => __(ucfirst($this->section_type) . " State / Region", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " State / Region may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'State / Region' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_city' => array(
                        'label' => __(ucfirst($this->section_type) . " City", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " City may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'City' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                    '_postal_code' => array(
                        'label' => __(ucfirst($this->section_type) . " Postal Code", "wp-checkout-vis-fields-woo"),
                        'desc' => __("Warning: " . ucfirst($this->section_type) . " Postal Code may be required for delivery", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("This field will hide the " . ucfirst($this->section_type) . " 'Postal Code' field from the wooCommerce checkout", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true
                    ),
                ),
            ),
        );
        return $table_section_array;
    }

    /**
     * Function defines the settings to populate other checkout function section
     * 
     * @since   1.0.0
     */
    public function get_other_checkout_functions($key)
    {
        $table_section_array = array(
            array(
                'title' => __("Hide Other Checkout Fields", "wp-checkout-vis-fields-woo"),
                'type' => 'checkbox',
                'section_class' => 'zamartz-bordered',
                'field_options' => array(
                    '_term_conditions' => array(
                        'label' => __("Order Terms & Conditons", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("Will update the requirements of the Terms and Conditions checkbox before billing can be submitted.", "wp-checkout-vis-fields-woo"),
                        'class' => 'woo-checkout-paid-feature'
                    ),
                    '_create_account' => array(
                        'label' => __("'Create Account'", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("Will require the customer to have add “Create Account” checked when processing an order", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true,
                        'class' => 'woo-checkout-paid-feature',
                    ),
                    '_customer_logged_in' => array(
                        'label' => __("Customer to be Logged-In", "wp-checkout-vis-fields-woo"),
                        'tooltip_desc' => __("Will require the customer to be logged-in prior to finishing the process of their order", "wp-checkout-vis-fields-woo"),
                        'is_tristate_active' => true,
                        'class' => 'woo-checkout-paid-feature',
                        'is_checkbox_enabled' => false
                    ),
                ),
            ),
        );
        return $table_section_array;
    }

    /**
     * Generates the condition dropdown
     *
     * @since    1.0.0
     * @param   integer     $key                The index of the array
     * @return  string      $row_html  The form HTML containing the respective condition dropdown
     */
    public function get_form_conditions($key)
    {
        $selected = '';
        if (isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key])) {
            $selected = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key];
        }

        $add_text = '';
        if ($this->plugin_api_version === 'Free') {
            $add_text = ' (Paid Condition)';
        }

        return array(
            'title' => __("Conditions", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => 'The condition determines what will be evaluated in checkout to apply the above rules.',
            'type' => 'select',
            'is_multi' => false,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_conditions",
                'class' => 'wc-enhanced-select woo-checkout-form-conditions',
            ),
            'field_options' => array(
                'order_total' => __("Order Total" . $add_text, "wp-checkout-vis-fields-woo"),
                'order_subtotal' => __("Order SubTotal" . $add_text, "wp-checkout-vis-fields-woo"),
                'shipping_amount' => __("Shipping Amount" . $add_text, "wp-checkout-vis-fields-woo"),
                'tax_amount' => __("Tax Amount" . $add_text, "wp-checkout-vis-fields-woo"),
                'customer_roles' => __("Customer Roles" . $add_text, "wp-checkout-vis-fields-woo"),
                'product_in_cart' => __("Product In Cart" . $add_text, "wp-checkout-vis-fields-woo"),
                'product_variations' => __("Product Variations in Cart" . $add_text, "wp-checkout-vis-fields-woo"),
                'product_categories' => __("Product Categories in Cart" . $add_text, "wp-checkout-vis-fields-woo"),
                'coupon_applied' => __("Coupon is Applied" . $add_text, "wp-checkout-vis-fields-woo"),
            ),
            'input_value' => $selected,
            'section_class' => 'zamartz-bordered'
        );
    }

    /**
     * Generates the operator dropdown
     *
     * @since    1.0.0
     * @param   integer     $key                The index of the array
     * @param   string      $form_condition Existing value of the condition dropdown
     * @return  string      $row_html  The form HTML of the generated operator dropdown
     */
    public function get_form_operator_dropdown($key = 0, $form_condition = '')
    {
        //Get form condition and operator information
        $selected = '';
        if (empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_operator"][$key])) {
            $form_condition = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key];
            $selected = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_operator"][$key];
        }
        if ($form_condition == 'order_total' || $form_condition == 'order_subtotal' || $form_condition == 'shipping_amount' || $form_condition == 'tax_amount') {
            return $this->get_form_operator_field_operators($selected, $key);
        } elseif ($form_condition == 'customer_roles') {
            return $this->get_form_operator_field_is_isnot($selected, $key);
        } elseif ($form_condition == 'product_in_cart' || $form_condition == 'product_variations' || $form_condition == 'product_categories' || $form_condition == 'coupon_applied') {
            return $this->get_form_operator_field_contains($selected, $key);
        } else {
            return $this->get_form_operator_field_operators('', $key);
        }
    }

    /**
     * An AJAX function to generate the operator dropdown after the condition 
     * dropdown option is changed.
     *
     * @since    1.0.0
     */
    public function get_form_operator_dropdown_ajax()
    {
        if ($this->section_type == null) {
            $section_type = filter_input(INPUT_POST, 'section_type', FILTER_SANITIZE_STRING);
            $this->section_type = $section_type;
        }
        $selected_condition = filter_input(INPUT_POST, 'selected_condition', FILTER_SANITIZE_STRING);
        $key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_NUMBER_INT);

        $settings_operator = $this->get_form_operator_dropdown($key, $selected_condition);
        ob_start();
        $this->get_field_settings($settings_operator, true);
        $form_operator_dropdown = ob_get_clean();

        $settings_subfield = $this->get_form_condition_subfield($key, $selected_condition);
        ob_start();
        $this->get_field_settings($settings_subfield, true);
        $form_condition_subfield = ob_get_clean();

        echo json_encode(array(
            'form_operator_dropdown' => $form_operator_dropdown,
            'form_condition_subfield' => $form_condition_subfield
        ));
        die();
    }

    /**
     * Generates a dropdown if the condition selected is for "operator" field
     * 
     * @since   1.0.0
     * @param   string      $selected   The current selected option value.
     * @param   integer     $key        The index of the array.
     * @return  string      $row_html  The form HTML for the operator dropdown
     */
    public function get_form_operator_field_operators($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'input_value' => $selected,
            'key' => $key,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'less_than' => __("Less Than", "wp-checkout-vis-fields-woo"),
                'greater_than' => __("Greater Than", "wp-checkout-vis-fields-woo"),
                'less_than_equal' => __("Less Than Equal To", "wp-checkout-vis-fields-woo"),
                'greater_than_equal' => __("Greater Than Equal To", "wp-checkout-vis-fields-woo"),
                'equal' => __("Equal To", "wp-checkout-vis-fields-woo"),
            )
        );
    }

    /**
     * Generates the "Is"/"Is not" dropdown based on selected condition
     *
     * @since    1.0.0
     * @param   string  $selected           The pre-defined value of the operator.
     * @param   integer $key                The index of the array.
     * @return  string  $row_html  The html of the dropdown
     */
    public function get_form_operator_field_is_isnot($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'key' => $key,
            'input_value' => $selected,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'is' => __("Is", "wp-checkout-vis-fields-woo"),
                'is_not' => __("Is NOT", "wp-checkout-vis-fields-woo"),
            )
        );
    }

    /**
     * Generates the "Contain"/"Does not contain" dropdown based on the selected condition
     *
     * @since    1.0.0
     * @param   string  $selected   The pre-defined value of the operator.
     * @param   integer $key        The index of the array.
     * @return  string  $row_html  The html of the dropdown.
     */
    public function get_form_operator_field_contains($selected = '', $key = 0)
    {
        return array(
            'title' => __("Operator", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => 'The operator determines how the Condition will be compared to selected or entered value.',
            'type' => 'select',
            'key' => $key,
            'input_value' => $selected,
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_operator",
                'class' => 'wc-enhanced-select',
            ),
            'field_options' => array(
                'contains' => __("Contains", "wp-checkout-vis-fields-woo"),
                'not_contain' => __("Does NOT contain", "wp-checkout-vis-fields-woo"),
            )
        );
    }

    /**
     * Generates the subfield option based on the selected condition and operator
     *
     * @since    1.0.0
     * @param   integer   $key                  The index of the array.
     * @param   string    $form_condition   The currently selected condition.
     * @return  string    $row_html    The html of the dropdown.
     */
    public function get_form_condition_subfield($key = 0, $form_condition = '')
    {

        $subfield_value = '';
        if (empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_operator"][$key])) {
            $form_condition = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_conditions"][$key];
        }
        if (!empty($form_condition) && !empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_condition_subfield"][$key]) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_operator"][$key])) {
            $subfield_value = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_condition_subfield"][$key];
        }
        switch ($form_condition) {
            case 'order_total':
                $subfield_text = 'Order Total';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the Orders Grand Total Amount", "wp-checkout-vis-fields-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'order_subtotal':
                $subfield_text = 'Order Subtotal';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the Orders Subtotal before taxes, shipping and fees are applied.", "wp-checkout-vis-fields-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'shipping_amount':
                $subfield_text = 'Shipping Amount';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the shipping amount that is applied to the order", "wp-checkout-vis-fields-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'tax_amount':
                $subfield_text = 'Tax Amount';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the tax amount that is applied to the order", "wp-checkout-vis-fields-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'min' => 0
                    ),
                    'input_value' => $subfield_value
                );
                break;
            case 'customer_roles':
                $subfield_text = 'Customer Roles';
                global $wp_roles;
                $roles = $wp_roles->get_names();
                $settings = array(
                    'type' => 'select',
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the customer role that is applied to the customer or user. This is using OR logic.", "wp-checkout-vis-fields-woo"),
                    'is_multi' => true,
                    'is_select2' => false,
                    'input_value' => $subfield_value,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'class' => 'wc-enhanced-select',
                    ),
                    'field_options' => array()
                );
                foreach ($roles as $role_slug => $role_name) {
                    $settings['field_options'][$role_slug] = $role_name;
                }
                break;
            case 'product_in_cart':
                $search_limit = $this->get_select_dropdown_limit();
                $subfield_text = 'Product in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Parent Level product added to the cart. This is using OR logic.", "wp-checkout-vis-fields-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'class' => 'wc-product-search',
                        'data-params' => array(
                            'action'  => 'woocommerce_json_search_products',
                            'minimum_input_length'  => 4,
                            'limit' => $search_limit
                        )
                    ),
                    'field_options' => array()
                );
                if (!empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        $title = get_the_title($subfield_id);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            case 'product_variations':
                $search_limit = $this->get_select_dropdown_limit();
                $subfield_text = 'Product Variations in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Child Level product added to the cart. This is using OR logic.", "wp-checkout-vis-fields-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'class' => 'zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}product_variation",
                            'type'  => 'product_variation',
                            'minimum_input_length'  => 4,
                            'limit' => $search_limit
                        ),
                    ),
                    'field_options' => array()
                );
                if (is_array($subfield_value) && !empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        $product = get_post($subfield_id); //get_product_variation_title
                        $title = $this->get_product_variation_title($product);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            case 'product_categories':
                $subfield_text = 'Product Categories in Cart';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Product Categories in cart. This is using OR logic.", "wp-checkout-vis-fields-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => false,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'class' => 'wc-enhanced-select',
                    ),
                    'field_options' => array(),
                    'input_value' => $subfield_value
                );
                $categories = get_terms(['taxonomy' => 'product_cat']);
                foreach ($categories as $category) {
                    $settings['field_options'][$category->term_id] = $category->name;
                }
                break;
            case 'coupon_applied':
                $search_limit = $this->get_select_dropdown_limit();
                $subfield_text = 'Coupon is Applied';
                $settings = array(
                    'type' => 'select',
                    'tooltip_desc' => __("Conditional logic is applied based on the Coupon applied to the order. This is using OR logic.", "wp-checkout-vis-fields-woo"),
                    'key' => $key,
                    'is_multi' => true,
                    'is_select2' => true,
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                        'class' => 'zamartz-select2-search-dropdown',
                        'data-params' => array(
                            'action'  => "{$this->plugin_input_prefix}coupon_is_applied",
                            'type'  => 'shop_coupon',
                            'minimum_input_length'  => 4,
                            'limit' => $search_limit
                        ),
                    ),
                    'field_options' => array()
                );
                if (is_array($subfield_value) && !empty($subfield_value)) {
                    foreach ($subfield_value as $subfield_id) {
                        if (empty($subfield_id)) {
                            continue;
                        }
                        $title = get_the_title($subfield_id);
                        $settings['field_options'][$subfield_id] = $title . ' (#' . $subfield_id . ')';
                    }
                }
                break;
            default:
                $subfield_text = 'Order Total';
                $settings = array(
                    'key' => $key,
                    'tooltip_desc' => __("Conditional logic is applied based on the Orders Grand Total Amount", "wp-checkout-vis-fields-woo"),
                    'type' => 'input_number',
                    'option_settings' => array(
                        'name' => "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield",
                    ),
                    'input_value' => $subfield_value
                );
                break;
        }
        $settings['title'] = __($subfield_text, "wp-checkout-vis-fields-woo");
        // $settings['tooltip_desc'] = __('The rule value determines what should be evaluated in the checkout to meet the condition set and understand if the configuration above is applicable.', "wp-checkout-vis-fields-woo");
        return $settings;
    }

    /**
     * Generates the rule set priority section and field
     *
     * @since    1.0.0
     * @param   integer  $key                 The index of the array.
     * @return  string   $row_html   HTML of the current input field row.
     */
    public function get_form_rule_set_priority($key)
    {

        $ruleset_value = '';
        if (!empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_rule_set_priority"][$key])) {
            $ruleset_value = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_rule_set_priority"][$key];
        }
        return array(
            'key' => $key,
            'title' =>  __("Rule Set Priority", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => "When use the Rule Set Priority will allow you to reorder the Rules you have created. Rule set priority must be set to a numeric value, the first used being '0' all rules not set will follow the order they were created.",
            'type' => 'input_number',
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_rule_set_priority",
                'class' => 'woo-checkout-rule-set-priority',
                'min' => 0
            ),
            'input_value' => $ruleset_value,
        );
    }

    /**
     * Generates the rule set toggle switch
     *
     * @since    1.0.0
     * @param   integer  $key                 The index of the array.
     * @return  string   $row_html   HTML of the current button row.
     */
    public function get_form_stop_other_rules($key)
    {
        $rulebtn_value = 'no';
        if (!empty($this->form_data) && isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_rule_toggle"][$key])) {
            $rulebtn_value = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_rule_toggle"][$key];
        }
        return array(
            'key' => $key,
            'title' =>  __("Stop other rules", "wp-checkout-vis-fields-woo"),
            'tooltip_desc' => 'When selected this will prevent any other rule-sets from applying if this one is applicable. If this is set on multiple rule-sets the one higher in the list will take precedence.',
            'type' => 'toggle_switch',
            'option_settings' => array(
                'name' => "{$this->plugin_input_prefix}{$this->section_type}_rule_toggle",
            ),
            'input_value' => $rulebtn_value
        );
    }

    /**
     * Ajax functionality to validate form nonce and saving the form into the options table. 
     * Return respective status, message and class
     *
     * @since    1.0.0
     */
    public function woo_checkout_save_form_data_ajax()
    {
        $form_data = filter_input(INPUT_POST, 'form_data', FILTER_SANITIZE_STRING);
        parse_str($form_data, $postArray);

        if (!wp_verify_nonce(wp_unslash($postArray['zamartz_settings_nonce']), 'zamartz-settings')) {
            echo json_encode(array('status' => false, 'message' => __('Nonce could not be verified!')));
            die();
        }
        global $wpdb;

        $error = false;
        $message = 'Your settings have been saved.';
        $class = 'updated inline';
        if ($this->section_type == null) {
            $section_type = filter_input(INPUT_POST, 'section_type', FILTER_SANITIZE_STRING);
            $this->section_type = $section_type;
        }

        //Set setting ignore list for paid vs free versions
        $this->woo_checkout_set_ignore_list();

        foreach ($postArray as $key => $data) {
            if (empty($key) || strpos($key, $this->plugin_input_prefix) === false || (!empty($this->ignore_list) && in_array($key, $this->ignore_list))) {
                continue;
            }

            //Logic to clear ruleset priorities if duplicate exists
            if (strpos($key, 'rule_set_priority') !== false) {
                $unique_ruleset_array = array();
                foreach ($data as $index => $priority) {
                    if (in_array($priority, $unique_ruleset_array)) {
                        $searched_index = array_search($priority, $unique_ruleset_array);
                        unset($unique_ruleset_array[$searched_index]);
                        $data[$searched_index] = '';
                    }
                    $unique_ruleset_array[$index] = $priority;
                }
            }

            update_option($key, $data);
            if (!empty($wpdb->last_error)) {
                $error = true;
                $key_name = ucfirst(str_replace(array("{$this->plugin_input_prefix}{$this->section_type}_", '_'), array('', ' '), $key));
                $message = 'There was a problem while updating the option for "' . $key_name . '"';
                $class = 'error inline';
                break;
            }
        }

        echo json_encode(
            array(
                'status' => !$error,
                'message' => '<p><strong>' . $message . '</strong></p>',
                'class' => $class
            )
        );
        die();
    }

    /**
     * Ajax functionality to populate the an accordion section after the rule set
     * button is clicked.
     * 
     * @since    1.0.0
     */
    public function woo_checkout_get_form_section_ajax()
    {

        //Add logic to check license key
        if ($this->plugin_api_version === 'Free') {
            echo json_encode(
                array(
                    'status' => false,
                    'message' => __("Error: Use with Paid Version Only", "wp-checkout-vis-fields-woo"),
                )
            );
            die();
        }

        if ($this->section_type == null) {
            $section_type = filter_input(INPUT_POST, 'section_type', FILTER_SANITIZE_STRING);
            $this->section_type = $section_type;
        }
        $key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);

        $key = !empty($key) ? ($key + 1) : 1;
        ob_start();
        $this->woo_checkout_get_form_section($key, 2);
        $html = ob_get_clean();
        echo json_encode(
            array(
                'status' => true,
                'message' => $html
            )
        );
        die();
    }

    /**
     * Function retrieves the select2 dropdown list via ajax search for the selected form condition type
     * 
     * @since    1.0.0
     */
    public function woo_checkout_get_select2_dropdown_ajax()
    {

        $post_type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);
        $term = filter_input(INPUT_GET, 'term', FILTER_SANITIZE_STRING);

        if (empty($term)) {
            wp_die();
        }

        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
        if (empty($limit)) {
            $limit = 5;
        }

        $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
        if (empty($page)) {
            $page = 1;
        }

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'offset' => ($page - 1) * $limit,
            'orderby' => 'title',
            'order' => 'ASC',
        );

        $args['search_prod_title'] = $term;

        $search_results = new WP_Query($args);
        $query_data = [];
        foreach ($search_results->posts as $key => $product) {

            if ($post_type == 'product_variation') {
                $query_data[$key]['text'] = $this->get_product_variation_title($product);
            } else {
                $query_data[$key]['text'] = $product->post_title;
            }

            // Get children product variation IDs in an array
            $query_data[$key]['id'] = $product->ID;
        }

        $total = $search_results->found_posts;
        $more = $page * $limit < $total;

        echo json_encode(array('query_data' => $query_data, 'pagination' => array("more" => $more)));
        die();
    }

    /**
     * Generate the title of product variation (single|multi).
     * 
     * @since    1.0.0
     * @param    object   $product    The post data of the current form condition selection.
     * @return   string   Return the generated title of the product variation
     */
    private function get_product_variation_title($product)
    {
        $wc_product = wc_get_product($product->ID);
        if (empty($wc_product)) {
            return;
        }
        $available_variations = $wc_product->get_attributes();
        //If product has multiple attributes for each variation
        if (count($available_variations) > 1) {
            $formatted_variation = [];
            foreach ($available_variations as $name => $value) {
                $term = get_term_by('slug', $value, $name);
                $formatted_variation[] = $term->name;
            }
            return $product->post_title . ' - ' . implode(', ', $formatted_variation) . ' (#' . $product->ID . ')';
        } else {
            return $product->post_title . ' (#' . $product->ID . ')';
        }
    }

    /**
     * Add to post query based on the selected WP Query criteria.
     * See woo_checkout_get_select2_dropdown_ajax().
     * 
     * @since    1.0.0
     * @param   string  $where      The WHERE clause of the query
     * @param   object  $wp_query   The instance of WP_Query class
     * @return  string  $where      The updated WHERE clause of the query
     */
    public function woo_checkout_set_post_where_wp_query($where, $wp_query)
    {
        global $wpdb;
        if ($search_term = $wp_query->get('search_prod_title')) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql($wpdb->esc_like($search_term)) . '%\'';
        } elseif ($search_term = $wp_query->get('search_prod_excerpt')) {
            $where .= ' AND ' . $wpdb->posts . '.post_excerpt LIKE \'%' . esc_sql($wpdb->esc_like($search_term)) . '%\'';
        }
        return $where;
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     * Add a billing tab in WooCommerce Settings called Billing.
     *
     * @since    1.0.0
     */
    public function add_billing_field_visibility_tab($settings_tabs)
    {
        if (!isset($settings_tabs['billing_field_visibility'])) {
            $settings_tabs['billing_field_visibility'] = __("Billing", "wp-checkout-vis-fields-woo");
        } else {
            //Generate an admin notice
            $settings_tabs['billing_field_visibility'] = __("Billing", "wp-checkout-vis-fields-woo");
        }
        return $settings_tabs;
    }

    /**
     * Define WooCommerce admin fields for our billing section
     *
     * @since    1.0.0
     */
    public function get_woo_checkout_admin_fields_billing()
    {
        $this->section_type = 'billing';

        $this->woo_checkout_set_form_data();

        //Display shipping settings html template
        require_once $this->plugin_url['admin_path'] . '/partials/wp-checkout-vis-fields-woo-admin-html-form.php';
    }

    /**
     * Get the returned result limit on select2 dropdown
     *
     * @since    1.0.0
     */
    public function get_select_dropdown_limit()
    {
        $limit = get_option("{$this->plugin_input_prefix}results_returned");
        if (!empty($limit) && $limit != 0) {
            return $limit;
        } else {
            return 7;
        }
    }

    /**
     * Define ignore list to restrict users from updating paid feature settings
     */
    public function woo_checkout_set_ignore_list()
    {
        //Set ignore list for paid features
        if ($this->plugin_api_version === 'Free') {
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_term_conditions";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_user_account_combination";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_create_account";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_customer_logged_in";

            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_conditions";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_operator";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_condition_subfield";

            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_rule_set_priority";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_rule_toggle";

            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_ruleset_type";
            $this->ignore_list[] = "{$this->plugin_input_prefix}{$this->section_type}_ruleset_message";
        }
    }

    /**
     * Generates the rule-set message section.
     * This section generates a dropdown for rule-set message type and rule-set message text.
     *
     * @since    1.0.0
     * @param   integer  $key        The index of the array.
     * @return  string   $row_html   HTML of the current input field row.
     */
    public function get_form_rule_set_message($key)
    {
        $ruleset_type = '';
        if (isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_ruleset_type"][$key])) {
            $ruleset_type = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_ruleset_type"][$key];
        }

        $add_text = '';
        if ($this->plugin_api_version === 'Free') {
            $add_text = ' (Paid Condition)';
        }

        $ruleset_message = '';
        if (isset($this->form_data["{$this->plugin_input_prefix}{$this->section_type}_ruleset_message"][$key])) {
            $ruleset_message = $this->form_data["{$this->plugin_input_prefix}{$this->section_type}_ruleset_message"][$key];
        }
        $table_section_array = array(
            array(
                'title' => __("Rule Set Message Type", "wp-checkout-vis-fields-woo"),
                'tooltip_desc' => "Changes the display type of the message shown to the customer in checkout.",
                'type' => 'select',
                'is_multi' => false,
                'option_settings' => array(
                    'name' => "{$this->plugin_input_prefix}{$this->section_type}_ruleset_type",
                    'class' => 'woo-checkout-rule-set-message-type',
                ),
                'field_options' => array(
                    'information' => __("Information" . $add_text, "wp-checkout-vis-fields-woo"),
                    'success' => __("Success" . $add_text, "wp-checkout-vis-fields-woo"),
                    'warning' => __("Warning" . $add_text, "wp-checkout-vis-fields-woo"),
                    'error' => __("Error" . $add_text, "wp-checkout-vis-fields-woo"),
                ),
                'input_value' => $ruleset_type,
                'section_class' => 'zamartz-bordered'
            ),
            array(
                'key' => $key,
                'title' =>  __("Rule Set Message", "wp-checkout-vis-fields-woo"),
                'tooltip_desc' => 'Will show this message on the checkout page if qualifies for the ruleset.',
                'type' => 'textarea',
                'option_settings' => array(
                    'name' => "{$this->plugin_input_prefix}{$this->section_type}_ruleset_message",
                    'class' => 'woo-checkout-rule-set-message',
                    'row' => 3
                ),
                'input_value' => $ruleset_message,
            )
        );
        return $table_section_array;
    }
}

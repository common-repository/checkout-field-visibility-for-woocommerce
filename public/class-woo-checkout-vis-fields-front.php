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
 * Functionality for front-end checkout page to hide/display fields based on defined
 * admin ruleset settings
 *
 *
 * @package    Wp_Woo_Checkout_Vis_Fields
 * @subpackage Wp_Woo_Checkout_Vis_Fields/admin
 * @author     Zachary Martz <zam@zamartz.com>
 */
class Woo_Checkout_Visibility_Front
{
    /**
     * Incorporate the trait functionalities for Zamartz General in this class
     * @see     zamartz/helper/trait-zamartz-general.php
     */
    use Zamartz_General;

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
     * Define the show/hide state of 'Create account' checkbox
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $create_account_hide
     */
    public $create_account_hide = 'no';

    /**
     * Define the required/not-required state of 'Create account' checkbox
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $create_account_required
     */
    public $create_account_required;

    /**
     * Define the required/not-required state of 'Customer to be Logged-In'
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $create_account_required
     */
    public $customer_logged_in_required;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct($core_instance)
    {

        //Set plugin parameter information
        $this->set_plugin_data($core_instance);

        //Set plugin paid vs free information
        $this->set_plugin_api_data();

        $hide_billing_fields_toggle = get_option("{$this->plugin_input_prefix}hide_billing_fields_toggle");
        if ($hide_billing_fields_toggle === 'yes') {
            add_filter('woocommerce_billing_fields', array($this, "{$this->plugin_input_prefix}hide_fields"), 20);
        }

        $hide_shipping_fields_toggle = get_option("{$this->plugin_input_prefix}hide_shipping_fields_toggle");
        if ($hide_shipping_fields_toggle === 'yes') {
            add_filter('woocommerce_shipping_fields', array($this, "{$this->plugin_input_prefix}hide_fields"), 20);
        }

        add_action('wp_footer', array($this, 'set_default_address_fields_optional'), 4);

        if ($hide_billing_fields_toggle === 'yes' || $hide_shipping_fields_toggle === 'yes') {
            add_filter('woocommerce_shipping_fields', array($this, "set_other_checkout_fields_state"), 30);
        }

        // Show/hide terms and conditions checkbox
        add_filter('woocommerce_checkout_show_terms', array($this, 'set_terms_and_conditions_state'), 9);

        // Hide notices from checkout page
        add_action('woocommerce_after_checkout_validation', array($this, 'hide_notices_on_place_order'), 10);
    }

    /**
     * Retrieves the current section type (shipping|billing) and sets the form data.
     *
     * @since   1.0.0
     */
    private function woo_checkout_set_form_data()
    {

        $section_type = $this->section_type;

        $this->form_data = []; //clear data

        //Form account fields
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name"] = get_option("{$this->plugin_input_prefix}{$section_type}_first_name");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_first_name_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name"] = get_option("{$this->plugin_input_prefix}{$section_type}_last_name");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_last_name_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address"] = get_option("{$this->plugin_input_prefix}{$section_type}_email_address");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_email_address_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number"] = get_option("{$this->plugin_input_prefix}{$section_type}_phone_number");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_phone_number_switch");

        //Form address fields
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_company"] = get_option("{$this->plugin_input_prefix}{$section_type}_company");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_company_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_company_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_1");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_1_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_2");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_address_line_2_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_country"] = get_option("{$this->plugin_input_prefix}{$section_type}_country");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_country_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_country_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_state"] = get_option("{$this->plugin_input_prefix}{$section_type}_state");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_state_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_state_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_city"] = get_option("{$this->plugin_input_prefix}{$section_type}_city");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_city_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_city_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code"] = get_option("{$this->plugin_input_prefix}{$section_type}_postal_code");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_postal_code_switch");

        //Other checkout functions
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_term_conditions"] = get_option("{$this->plugin_input_prefix}{$section_type}_term_conditions");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account"] = get_option("{$this->plugin_input_prefix}{$section_type}_create_account");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_create_account_switch");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_customer_logged_in"] = get_option("{$this->plugin_input_prefix}{$section_type}_customer_logged_in");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_customer_logged_in_switch"] = get_option("{$this->plugin_input_prefix}{$section_type}_customer_logged_in_switch");

        //Form conditions
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_conditions"] = get_option("{$this->plugin_input_prefix}{$section_type}_conditions");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_operator"] = get_option("{$this->plugin_input_prefix}{$section_type}_operator");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_condition_subfield"] = get_option("{$this->plugin_input_prefix}{$section_type}_condition_subfield");

        //Form rule settings
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_set_priority"] = get_option("{$this->plugin_input_prefix}{$section_type}_rule_set_priority");
        $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_toggle"] = get_option("{$this->plugin_input_prefix}{$section_type}_rule_toggle");
    }

    /**
     * Unset checkout billing/shipping fields based on defined ruleset settings in admin
     * 
     * @since    1.0.0
     */
    public function woo_checkout_hide_fields($fields)
    {
        //Get option if section is enabled/disabled
        $key_name = key($fields);
        if (strpos($key_name, 'shipping') !== false) {
            $section_type = 'shipping';
        } elseif (strpos($key_name, 'billing') !== false) {
            $section_type = 'billing';
        } else {
            return $fields;
        }

        $this->section_type = $section_type;
        $this->woo_checkout_set_form_data();

        $plugin_status = $this->get_plugin_status();

        if ($plugin_status === false) {

            $first_name = $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name"];

            if ($first_name == false) {
                return $fields;
            }

            $array_key_list = array_keys($first_name);

            $section_data = $this->get_account_address_array($section_type, $array_key_list[0]);

            //Unset field based on current condition
            return $this->woo_checkout_unset_field($section_data, $fields);
        }

        $_rule_set_priority =  $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_set_priority"];

        if ($_rule_set_priority == false) {
            return $fields;
        }

        //Define order in which the ruleset accordion are run - works in paid version of the plugin
        $rule_set_priority = $this->sort_key_by_value_ascending($_rule_set_priority);

        foreach ($rule_set_priority as $loop_index => $value) {
            $break_loop = false;
            $rule_toggle = $this->form_data["{$this->plugin_input_prefix}{$section_type}_rule_toggle"][$loop_index];
            $condition_subfield = $this->form_data["{$this->plugin_input_prefix}{$section_type}_condition_subfield"][$loop_index];

            if ($rule_toggle == 'yes' && $condition_subfield != '') {
                // Stop other rules if current is toggled as yes
                $break_loop = true;
            } elseif ($condition_subfield == '') {
                continue;
            }

            //Check if current condition matches
            $condition =  $this->form_data["{$this->plugin_input_prefix}{$section_type}_conditions"][$loop_index];
            $operator =  $this->form_data["{$this->plugin_input_prefix}{$section_type}_operator"][$loop_index];

            $is_condition_true = $this->woo_checkout_condition_check($condition, $operator, $condition_subfield);

            if ($is_condition_true === false && $break_loop !== true) {
                continue;
            } elseif ($is_condition_true === false && $break_loop === true) {
                break;
            }

            //Get account and address section data
            $section_data = $this->get_account_address_array($section_type, $loop_index);

            // Unset field based on current condition
            $fields = $this->woo_checkout_unset_field($section_data, $fields);

            $this->woo_checkout_other_checkout_settings($section_type, $loop_index);

            // Show notices on the checkout page
            if (is_checkout()) {
                $this->woo_checkout_notices($section_data, $section_type);
            }

            if ($break_loop) {
                break;
            }
        }

        $this->checkout_fields[$section_type] = $fields;

        //Return fields
        return $fields;
    }

    /**
     * Get the stauts of the plugin. Check if plugin is paid or free.
     */
    public function get_plugin_status()
    {
        $api_license_key = get_option("{$this->plugin_input_prefix}api_license_key");
        $api_get_response = get_option("{$this->plugin_input_prefix}api_get_response");
        if (!empty($api_license_key) && !empty($api_get_response) && isset($api_get_response->activated) && $api_get_response->activated === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Generate the section data array for unsetting the field based on selected conditions
     */
    public function get_account_address_array($section_type, $loop_index)
    {
        $plugin_status = $this->get_plugin_status();

        //Account fields
        $section_data["{$section_type}_first_name"][$section_type . '_first_name_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name"][$loop_index];
        $section_data["{$section_type}_last_name"][$section_type . '_last_name_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name"][$loop_index];
        $section_data["{$section_type}_email"][$section_type . '_email_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address"][$loop_index];
        $section_data["{$section_type}_phone"][$section_type . '_phone_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number"][$loop_index];

        //Address fields
        $section_data["{$section_type}_company"][$section_type . '_company_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_company"][$loop_index];
        $section_data["{$section_type}_address_1"][$section_type . '_address_1_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1"][$loop_index];
        $section_data["{$section_type}_address_2"][$section_type . '_address_2_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2"][$loop_index];
        $section_data["{$section_type}_country"][$section_type . '_country_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_country"][$loop_index];
        $section_data["{$section_type}_state"][$section_type . '_state_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_state"][$loop_index];
        $section_data["{$section_type}_city"][$section_type . '_city_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_city"][$loop_index];
        $section_data["{$section_type}_postcode"][$section_type . '_postcode_hide'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code"][$loop_index];

        //Plugin is paid
        if ($plugin_status != false) {
            //Account fields
            $section_data["{$section_type}_first_name"][$section_type . '_first_name_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_first_name_switch"][$loop_index];
            $section_data["{$section_type}_last_name"][$section_type . '_last_name_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_last_name_switch"][$loop_index];
            $section_data["{$section_type}_email"][$section_type . '_email_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_email_address_switch"][$loop_index];
            $section_data["{$section_type}_phone"][$section_type . '_phone_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_phone_number_switch"][$loop_index];

            //Address fields
            $section_data["{$section_type}_company"][$section_type . '_company_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_company_switch"][$loop_index];
            $section_data["{$section_type}_address_1"][$section_type . '_address_1_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_1_switch"][$loop_index];
            $section_data["{$section_type}_address_2"][$section_type . '_address_2_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_address_line_2_switch"][$loop_index];
            $section_data["{$section_type}_country"][$section_type . '_country_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_country_switch"][$loop_index];
            $section_data["{$section_type}_state"][$section_type . '_state_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_state_switch"][$loop_index];
            $section_data["{$section_type}_city"][$section_type . '_city_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_city_switch"][$loop_index];
            $section_data["{$section_type}_postcode"][$section_type . '_postcode_required'] = $this->form_data["{$this->plugin_input_prefix}{$section_type}_postal_code_switch"][$loop_index];

            $section_data["{$section_type}_ruleset_message_data"] = array(
                get_option("{$this->plugin_input_prefix}{$section_type}_ruleset_type")[$loop_index] =>  get_option("{$this->plugin_input_prefix}{$section_type}_ruleset_message")[$loop_index]
            );
        }

        return $section_data;
    }

    /**
     * Sort in ascending order of value, move empty value at the end of array
     * 
     * @since    1.0.0
     */
    public function sort_key_by_value_ascending($to_sort)
    {
        if (empty($to_sort)) return $to_sort;
        $empty_value_array = array();
        asort($to_sort); //Sort by value in ascending order
        foreach ($to_sort as $key => $value) {
            if (empty($value) && $value !== '0') {
                $empty_value_array[$key] = $value;
                unset($to_sort[$key]);
            }
        }
        return ($to_sort + $empty_value_array);
    }

    /**
     * Unset field value based on defined section data
     * 
     * @see     $this::woo_checkout_hide_fields
     * @since   1.0.0
     */
    public function woo_checkout_unset_field($section_data, $fields)
    {

        if (empty($section_data)) {
            return $fields;
        }
        foreach ($section_data as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $field_name => $field_value) {
                    // if field is checked for unset, ignore the tri state selection
                    if ($field_name == $name . '_hide' && $field_value == 'yes') {
                        unset($fields[$name]);
                        // if field is checked for required
                    } elseif ($field_name == $name . '_required' && $section_data[$name][$name . '_hide'] == 'no' && isset( $fields[$name] ) ) {
                        // if tri state required is marked 'yes'
                        if ($field_value == 'yes') {
                            $fields[$name]['required'] = true;
                            // if tri state required is marked 'no'
                        } else if ($field_value == 'no') {
                            $fields[$name]['required'] = false;
                        }
                    }
                }
            }
        }
        return $fields;
    }

    public function set_default_address_fields_optional()
    {

        $address_fields = array(
            'address_1',
            'address_2',
            'state',
            'postcode',
            'city'
        );

        $updated_address_fields = array();
        if (isset($this->checkout_fields) && !empty($this->checkout_fields)) {
            foreach ($this->checkout_fields as $section_type => $section_data) {
                foreach ($section_data as $field_name => $field_data) {
                    $name = str_replace(array('shipping_', 'billing_'), '', $field_name);
                    if (in_array($name, $address_fields)) {
                        $updated_address_fields['#' . $field_name . '_field'] = $field_data['required'];
                    }
                }
            }
        }
        wp_localize_script(
            'zamartz-checkout-visibility-front-js',
            'zamartz_address_fields_object',
            array(
                'address_fields' => $updated_address_fields,
            )
        );
    }

    /**
     * Check condition type and value - return true if condition meets else false
     * 
     * @since   1.0.0
     */
    public function woo_checkout_condition_check($condition, $operator, $condition_subfield)
    {
        global $woocommerce;

        $condition_type = 'amount';
        switch ($condition) {
            case 'order_total':
                $amount = $woocommerce->cart->total;
                break;
            case 'order_subtotal':
                $amount = $woocommerce->cart->get_cart_subtotal();
                break;
            case 'shipping_amount':
                $amount = $woocommerce->cart->get_cart_shipping_total();
                break;
            case 'tax_amount':
                $amount = $woocommerce->cart->get_total_tax();
                break;
            default:
                $condition_type = 'dropdown';
                break;
        }
        //Check operator that needs to be applied
        if ($condition_type === 'amount') {
            return $this->woo_checkout_check_amount_condition($amount, $operator, $condition_subfield);
        } elseif ($condition_type === 'dropdown') {
            $cart_data = $woocommerce->cart->get_cart();
            if (!empty($cart_data)) {
                return $this->woo_checkout_is_id_exists($condition, $operator, $condition_subfield);
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Return boolean based on selected operator and value
     */
    public function woo_checkout_check_amount_condition($amount, $operator, $condition_subfield)
    {
        //Remove any symbols prior to actual value
        $amount = floatval(preg_replace('#[^\d.]#', '', $amount));

        //Operator based conditional check
        if ($operator == 'less_than') {
            return ($amount < $condition_subfield);
        } elseif ($operator == 'greater_than') {
            return ($amount > $condition_subfield);
        } elseif ($operator == 'less_than_equal') {
            return ($amount <= $condition_subfield);
        } elseif ($operator == 'greater_than_equal') {
            return ($amount >= $condition_subfield);
        } elseif ($operator == 'equal') {
            return ($amount == $condition_subfield);
        } else {
            return false;
        }
    }

    /**
     * Check if id exists in the cart. ID can be either product, variation, category or coupon
     * 
     * @since   1.0.0
     */
    public function woo_checkout_is_id_exists($condition, $operator, $condition_subfield)
    {
        $ids_in_cart = $this->woo_checkout_get_cart_id_array($condition);
        foreach ($condition_subfield as $id) {
            if (($operator === 'contains' || $operator === 'is') && in_array($id, $ids_in_cart)) {
                return true;
            } elseif (($operator === 'not_contain' || $operator === 'is_not') && in_array($id, $ids_in_cart)) {
                return false;
            }
        }
        if ($operator === 'contains' || $operator === 'is') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks cart and creates an array with list of all existing ids based on condition type
     * 
     * @since   1.0.0
     */
    public function woo_checkout_get_cart_id_array($condition_type)
    {
        global $woocommerce;
        $cart_data = $woocommerce->cart->get_cart();
        $id_array_list = [];
        if ($condition_type === 'product_in_cart') {
            foreach ($cart_data as $cart) {
                $product_id = $cart['product_id'];
                if (!in_array($product_id, $id_array_list)) {
                    $id_array_list[] = $product_id;
                }
            }
        } elseif ($condition_type === 'product_variations') {
            foreach ($cart_data as $cart) {
                $variation_id = $cart['variation_id'];
                if ($variation_id !== 0 && !in_array($variation_id, $id_array_list)) {
                    $id_array_list[] = $variation_id;
                }
            }
        } elseif ($condition_type === 'product_categories') {
            foreach ($cart_data as $cart) {
                $product_id = $cart['product_id'];
                $product_category_array = get_the_terms($product_id, 'product_cat');
                foreach ($product_category_array as $category) {
                    if (!in_array($category->term_id, $id_array_list)) {
                        $id_array_list[] = $category->term_id;
                    }
                }
            }
        } elseif ($condition_type === 'coupon_applied') {
            $coupons = $woocommerce->cart->get_applied_coupons();
            foreach ($coupons as $coupon_code) {
                $id_array_list[] = wc_get_coupon_id_by_code($coupon_code);
            }
        } elseif ($condition_type === 'customer_roles') {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $id_array_list = (array) $user->roles;
            } else {
                $id_array_list = array();
            }
        }
        return $id_array_list;
    }

    /**
     * Show notices on checkout page based on the settings defined on backend
     * 
     * @since   1.0.0
     */

    public function woo_checkout_notices($section_data, $section_type)
    {
        if (empty($section_data[$section_type . '_ruleset_message_data'])) {
            return;
        }

        foreach ($section_data[$section_type . '_ruleset_message_data'] as $name => $value) {
            if (!empty($name) && !empty($value)) {
                echo $this->woo_checkout_generate_notices_html($name, $value);
            }
        }
    }

    /**
     * Set the status for terms and conditions checkbox using filter
     */
    public function set_terms_and_conditions_state()
    {
        $term_condition = get_option("{$this->plugin_input_prefix}term_and_condition_checkout_hide");
        if ($term_condition == 'yes') {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Show/hide or make it required/not-required based on 'other checkout fields' in the admin settings
     * Show/hide the terms and conditions checkbox.
     * Show/hide the 'create account' checkbox.
     * Required/not-required 'create account'
     * Required/not-required 'customer logged-in'
     */
    public function woo_checkout_other_checkout_settings($section_type, $loop_index)
    {

        $plugin_status = $this->get_plugin_status();
        if ($plugin_status == false) {
            return;
        }

        $_terms_and_conditions = $this->form_data["{$this->plugin_input_prefix}{$section_type}_term_conditions"][$loop_index];
        $_create_account_hide = $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account"][$loop_index];
        $_create_account_required = $this->form_data["{$this->plugin_input_prefix}{$section_type}_create_account_switch"][$loop_index];
        $_customer_logged_in_required = $this->form_data["{$this->plugin_input_prefix}{$section_type}_customer_logged_in_switch"][$loop_index];

        /**
         * Set terms and conditions.
         * Update option utilized due to AJAX refresh nature of the checkout order total table
         */
        
        if (!empty($_terms_and_conditions) ) {
            update_option("{$this->plugin_input_prefix}term_and_condition_checkout_hide", $_terms_and_conditions);
        }

        // Show/hide create account checkbox on the checkout page
        if (!empty($_create_account_hide) && $_create_account_hide == 'yes') {
            $this->create_account_hide = $_create_account_hide;
        }

        // Required/not-required 'create account' checkbox on the checkout page
        if (!empty($_create_account_required) && $_create_account_required != 'default') {
            $this->create_account_required = $_create_account_required;
        }

        // Required/not-required 'customer to be logged-in' on the checkout page
        if (!empty($_customer_logged_in_required) && $_customer_logged_in_required != 'default') {
            $this->customer_logged_in_required = $_customer_logged_in_required;
        }
    }

    /**
     * Show/Hide the registration on checkout page using woocommerce filters
     */
    public function set_other_checkout_fields_state($fields)
    {

        if ($this->create_account_hide == 'yes') {
            // Hide checkbox based on set value
            add_filter('woocommerce_checkout_registration_enabled', '__return_false');
        } elseif ($this->create_account_hide != 'yes' && $this->create_account_required == 'yes') {
            // Required/not-required state of 'create account'
            add_filter('woocommerce_checkout_registration_enabled', '__return_true');   //Override WooCommerce Settings - 'Allow customers to create an account during checkout'
            add_filter('woocommerce_checkout_registration_required', '__return_false'); //Override WooCommerce Settings - Allow customers to place orders without an account
            add_filter('woocommerce_create_account_default_checked', '__return_true');  //'Create account' checkbox "checked" by default
        }

        if ($this->customer_logged_in_required == 'yes') {
            if (!has_action('woocommerce_after_checkout_validation', array($this, 'customer_logged_in_required'))) {
                add_action('woocommerce_after_checkout_validation', array($this, 'customer_logged_in_required'));
            }
        }
        return $fields;
    }

    /**
     * Generate notice and display placing an order if user is not logged-in
     */
    public function customer_logged_in_required()
    {
        if (!is_user_logged_in()) {
            wc_add_notice('User must be logged in to proceed forward.', 'error');
        }
    }

    /**
     * Generate the notice html on the checkout page
     */
    public function woo_checkout_generate_notices_html($notice_type, $notice_message)
    {
        switch ($notice_type) {
            case 'information':
                $html = wc_print_notice($notice_message, "notice");
                break;
            case 'success':
                $html = wc_print_notice($notice_message, "success");
                break;
            case 'warning':
                $html = wc_print_notice($notice_message, "notice");
                break;
            case 'error':
                $html = wc_print_notice($notice_message, "error");
                break;
            default:
                $html = wc_print_notice($notice_message, "notice");
                break;
        }
        return $html;
    }

    /**
     * Remove notices when place order clicked
     */

    public function hide_notices_on_place_order()
    {

        $notices = WC()->session->get('wc_notices', array());

        if (!empty($notices)) {
            wc_clear_notices();
        }
    }
}

<?php
/**
 * Amazon Login - Login for osCommerce
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2015 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */
/**
 * Class paywithamazon_installer
 * Pay With Amazon Installer
 */
class paywithamazon_installer
{
    private $is_installed;

    /**
     * Check if the plugin has been installed
     *
     * @return bool
     */
    function is_installed()
    {

        if (!isset($this->is_installed)) {
            $this->is_installed = paywithamazon_config::is_set_config_value('MODULE_PAYMENT_PAYWITHAMAZON_STATUS');
        }
        return $this->is_installed;
    }

    /**
     * Install all configuration settings.
     *
     * @return bool
     */
    function install()
    {
        if (!$this->is_installed()) {
            $config_fields = $this->get_config_fields();
            foreach ($config_fields as $field_key => $field_data) {
                if (!isset($field_data['title'])) {
                    $field_data['title'] = '';
                }
                if (!isset($field_data['value'])) {
                    $field_data['value'] = '';
                }
                if (!isset($field_data['description'])) {
                    $field_data['description'] = '';
                }
                if (!isset($field_data['group_id'])) {
                    $field_data['group_id'] = '6';
                }
                if (!isset($field_data['sort_order'])) {
                    $field_data['sort_order'] = '0';
                }
                if (!isset($field_data['use_function'])) {
                    $field_data['use_function'] = '';
                }
                if (!isset($field_data['set_function'])) {
                    $field_data['set_function'] = '';
                }
                paywithamazon_config::create_config_field($field_key, $field_data['title'], $field_data['value'], $field_data['description'], $field_data['group_id'], $field_data['sort_order'], $field_data['use_function'], $field_data['set_function']);
            }
            $this->create_reference_table();
            $this->create_refunds_reference_table();
            $this->create_voids_reference_table();
            $this->create_closes_reference_table();

            $this->is_installed = true;
        }
        return $this->is_installed;
    }

    /**
     * Create payments reference table.
     */
    function create_reference_table()
    {
        $table_name = 'paywithamazon_payments';
        $sql = "CREATE TABLE " . $table_name . " (
                  order_id int(11) unsigned NOT NULL,
                  amazon_order_id varchar(100) NOT NULL,
                  amazon_auth_id varchar(100) NOT NULL,
                  amazon_capture_id varchar(100) NULL,
                  authorized tinyint(1) unsigned NOT NULL,
                  charged tinyint(1) unsigned NOT NULL
                )";
        tep_db_query($sql);
    }

    /**
     * Create refunds reference table.
     */
    function create_refunds_reference_table()
    {
        $table_name = 'paywithamazon_refunds';
        $sql = "CREATE TABLE " . $table_name . " (
                  order_id int(11) unsigned NOT NULL,
                  amazon_refund_id varchar(100) NOT NULL,
                  status tinyint(1) unsigned NOT NULL
                )";
        tep_db_query($sql);
    }

    /**
     * Create voids reference table.
     */
    function create_voids_reference_table()
    {
        $table_name = 'paywithamazon_voids';
        $sql = "CREATE TABLE " . $table_name . " (
                  order_id int(11) unsigned NOT NULL
                )";
        tep_db_query($sql);
    }

    /**
     * Create closes reference table.
     */
    function create_closes_reference_table()
    {
        $table_name = 'paywithamazon_closes';
        $sql = "CREATE TABLE " . $table_name . " (
                  order_id int(11) unsigned NOT NULL
                )";
        tep_db_query($sql);
    }

    /**
     * Remove configuration from database.
     */
    function uninstall()
    {

        if($this->is_installed()) {
            $configFieldKeys = '"' . implode('", "', $this->get_config_keys()) . '"';
            tep_db_query('DELETE FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_key IN (' . $configFieldKeys . ')');
            $tables = array('paywithamazon_payments', 'paywithamazon_refunds', 'paywithamazon_voids', 'paywithamazon_closes');
            foreach($tables as $table) {
                tep_db_query('DROP TABLE ' . $table);
            }
            $this->is_installed = false;
        }
        return $this->is_installed;
    }

    /**
     * Retrieves all configuration fields.
     *
     * @return array
     */
    protected function get_config_fields()
    {
        // Get config field settings
        // group_id defaults to 6
        // sort_order defaults to 0
        return array(
            'MODULE_PAYMENT_PAYWITHAMAZON_STATUS' => array(
                'title' => 'Enable Pay With Amazon',
                'value' => 'False',
                'description' => 'Do you want to enable Pay With Amazon?',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_SORT_ORDER' => array(
                'title' => 'Sort Order',
                'value' => '0',
                'description' => 'Sort Order (lowest is displayed first)',
                'set_function' => ''
            ),
            'MODULE_AMAZON_LOGGING' => array(
                'title' => 'Debug Logging',
                'value' => 'False',
                'description' => 'Do you want to enable logging?',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            'MODULE_AMAZON_STATUS' => array(
                'title' => 'Enable Login With Amazon',
                'value' => 'False',
                'description' => 'Do you want to enable Login With Amazon?',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_SANDBOX' => array(
                'title' => 'Enable Sandbox Mode',
                'value' => 'False',
                'description' => 'Enable Sandbox Mode?',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_ZONE' => array(
                'title' => 'Payment Zone',
                'value' => '0',
                'description' => 'If a zone is selected, only enable this payment method for that zone.',
                'use_function' => 'tep_get_zone_class_title',
                'set_function' => 'tep_cfg_pull_down_zone_classes('
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_CAPTURE' => array(
                'title' => 'Capture On Checkout?',
                'value' => 'False',
                'description' => 'Do you want to capture funds immediately during checkout?',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), '
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_MODE' => array(
                'title' => 'Synchronous or Asynchronous Authorization mode?',
                'value' => 'sync',
                'description' => 'In synchronous mode orders will always be accepted or rejected during the order process. In asynchronous mode users authorizations and declines can take up to 24 hours (although most only take a few seconds).',
                'set_function' => 'tep_cfg_select_option(array(\'sync\', \'async\'), '
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_EXCLUDED_PRODUCTS' => array(
                'title' => 'Excluded Products',
                'value' => '',
                'description' => 'Comma separated list of product ids to exclude. (e.g. 1,2,3)',
                'set_function' => ''
            ),
            'MODULE_PAYMENT_PAYWITHAMAZON_RESTRICTED_IPS' => array(
                'title' => 'Restricted IPs',
                'value' => '',
                'description' => 'Comma separated list of IP Addresses to restrict the Login and Payment buttons to. If not specified the buttons will show to all viewers. (e.g. 10.0.0.1,10.0.0.2)',
                'set_function' => ''
            ),
            'MODULE_AMAZON_REGION' => array(
                'title' => 'Amazon Region',
                'value' => 'US',
                'description' => 'Do you want to capture funds immediately during checkout?',
                'set_function' => 'tep_cfg_select_option(array(\'US\', \'UK\', \'DE\'), '
            ),
            'MODULE_AMAZON_CLIENT_ID' => array(
                'title' => 'Client ID',
                'value' => '',
                'description' => '',
                'use_function' => '',
                'set_function' => ''
            ),
            'MODULE_AMAZON_SELLER_ID' => array(
                'title' => 'Seller ID',
                'value' => '',
                'description' => '',
                'use_function' => '',
                'set_function' => ''
            ),
            'MODULE_AMAZON_MWS_ACCESS_KEY' => array(
                'title' => 'MWS Access Key',
                'value' => '',
                'description' => '',
                'use_function' => '',
                'set_function' => ''
            ),
            'MODULE_AMAZON_MWS_SECRET_KEY' => array(
                'title' => 'MWS Secret Key',
                'value' => '',
                'description' => '',
                'use_function' => '',
                'set_function' => ''
            )
        );
    }

    /**
     * Wrapper for retrieving only array keys from get_config_fields, used by Zen Cart core.
     *
     * @return array
     */
    function get_config_keys()
    {
        $fields = $this->get_config_fields();
        return array_keys($fields);
    }
}
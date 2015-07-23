<?php
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon/db_helper.php');
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon/config.php');
/**
 * Class loginwithamazon_tools
 */
class loginwithamazon_tools
{
    /**
     * @param $first_name
     * @param $last_name
     * @param $email
     * @return integer Customer ID
     */
    public static function create_user($first_name, $last_name, $email) {
        $existing_customer = self::get_customer_id_by_email($email);
        if(!$existing_customer) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $password_string = '';
            for ($i = 0; $i < 20; $i++) {
                $password_string = $characters[rand(0, strlen($characters))];
            }
            // tep_encrypt_password deals with actual hashing, this is simply generating a longer string.
            $password_string = md5($password_string);
            $customer_data = array (
                'customers_firstname' => $first_name,
                'customers_lastname' => $last_name,
                'customers_email_address' => $email,
                'customers_gender' => '',
                'customers_dob' => tep_db_prepare_input('0001-01-01 00:00:00'),
                'customers_telephone' => '',
                'customers_newsletter' => '0',
                'customers_default_address_id' => 0,
                'customers_password' => tep_encrypt_password($password_string)
            );
            tep_db_perform(TABLE_CUSTOMERS, $customer_data);
            return tep_db_insert_id();
        } else {
            return $existing_customer;
        }
    }

    /**
     * @param $email
     * @return integer|bool Customer ID or False
     */
    public static function get_customer_id_by_email($email) {
        $query = "SELECT `customers_id` FROM " . TABLE_CUSTOMERS . " WHERE `customers_email_address` = :email_address";
        $query = bind_vars($query, ':email_address', $email);
        $result = tep_db_fetch_array(tep_db_query($query));
        return (!empty($result['customers_id']) ? $result['customers_id'] : false);
    }

    /**
     * @param $customer_id
     * @return integer|bool Address ID or False
     */
    public static function get_customer_address_id($customer_id) {
        $query = "SELECT `customers_default_address_id` FROM " . TABLE_CUSTOMERS . " WHERE `customers_id` = :customer_id";
        $query = bind_vars($query, ':customer_id', $customer_id);
        $result = tep_db_fetch_array(tep_db_query($query));
        return (!empty($result['customers_default_address_id']) ? $result['customers_default_address_id'] : false);
    }

    public static function create_customer_blank_address($customer_id) {
        $address_id = self::create_address($customer_id);

        $query = "UPDATE " . TABLE_CUSTOMERS . " SET `customers_default_address_id` = :customers_default_address_id WHERE `customers_id`=:customers_id";
        $query = bind_vars($query, ':customers_default_address_id', $address_id);
        $query = bind_vars($query, ':customers_id', $customer_id);
        tep_db_query($query);
        $_SESSION['customer_default_address_id'] = $address_id;
        return $address_id;
    }

    public static function create_address($customer_id, $customer_gender = '', $firstname = '', $lastname = '', $address = '', $address2 = '', $city = '', $postcode = '', $zone_id = 0, $country_id = 0)
    {
        // Check if address already exists
        $query = 'SELECT address_book_id FROM ' . TABLE_ADDRESS_BOOK . ' WHERE customers_id = :customerID: AND entry_street_address = :address: AND entry_suburb = :address2:';
        $query = bind_vars($query, ':customerID:', $customer_id);
        $query = bind_vars($query, ':address:', $address);
        $query = bind_vars($query, ':address2:', $address2);
        $result = tep_db_query($query);
        if(tep_db_num_rows($result) > 0) {
            $result_arr = tep_db_fetch_array($result);
            return $result_arr['address_book_id'];
        } else {
            // Add address.
            $address_data = array(
                'customers_id' => $customer_id,
                'entry_gender' => $customer_gender,
                'entry_firstname' => $firstname,
                'entry_lastname' => $lastname,
                'entry_street_address' => $address,
                'entry_suburb' => $address2,
                'entry_postcode' => $postcode,
                'entry_city' => $city,
                'entry_country_id' => $country_id,
                'entry_zone_id' => $zone_id
            );
            tep_db_perform(TABLE_ADDRESS_BOOK, $address_data);
            return tep_db_insert_id();
        }

    }

    /**
     * @param $customers_id
     * @return bool
     */
    public static function login_user($customers_id)
    {
        $query = "SELECT * FROM " . TABLE_CUSTOMERS . " WHERE `customers_id` = :customersID";
        $query = bind_vars($query, ':customersID', $customers_id);
        $customer = tep_db_fetch_array(tep_db_query($query));
        if(!empty($customer['customers_id'])) {
            $_SESSION['customer_id'] = $customer['customers_id'];
            $_SESSION['customer_default_address_id'] = $customer['customers_default_address_id'];
            $_SESSION['customers_authorization'] = $customer['customers_authorization'];
            $_SESSION['customer_first_name'] = $customer['customers_firstname'];
            $_SESSION['customer_last_name'] = $customer['customers_lastname'];
            $query = "UPDATE " . TABLE_CUSTOMERS_INFO ." SET customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 WHERE customers_info_id = :customersID";
            $query = bind_vars($query, ':customersID', $_SESSION['customer_id']);
            tep_db_query($query);
            $_SESSION['cart']->restore_contents();
            return true;
        }
        return false;
    }

    /**
     * @param $country_code
     * @return mixed
     */
    public static function get_country_id($country_code)
    {
        $query = 'SELECT countries_id FROM countries WHERE countries_iso_code_2 = :countryCode:';
        $query = bind_vars($query, ':countryCode:', $country_code);
        $result = tep_db_fetch_array(tep_db_query($query));
        return $result['countries_id'];
    }

    /**
     * @param $country_id
     * @param $zone_name
     * @return mixed
     */
    public static function get_zone_id($country_id, $zone_name)
    {
        $query = 'SELECT zone_id FROM zones WHERE zone_country_id = :countryID: AND zone_name = :zoneName:';
        $query = bind_vars($query, ':countryID:', $country_id);
        $query = bind_vars($query, ':zoneName:', $zone_name);
        $result = tep_db_fetch_array(tep_db_query($query));
        return $result['zone_id'];
    }

    /**
     * Update default address id.
     *
     * @param $customer_id
     * @param $address_id
     * @return bool
     */
    public static function set_new_default_address_id($customer_id, $address_id) {
        $query = "UPDATE " . TABLE_CUSTOMERS . " SET `customers_default_address_id` = :customers_default_address_id WHERE `customers_id`=:customers_id";
        $query = bind_vars($query, ':customers_default_address_id', $address_id);
        $query = bind_vars($query, ':customers_id', $customer_id);
        tep_db_query($query);
        $_SESSION['sendto'] = $address_id;
        $_SESSION['billto'] = $address_id;
        $_SESSION['customer_default_address_id'] = $address_id;
        return true;
    }

    public static function get_last_address_id($customer_id) {
        $query = "SELECT * FROM " . TABLE_ADDRESS_BOOK . " WHERE `customers_id` = :customersID ORDER BY `address_book_id` DESC LIMIT 1";
        $query = bind_vars($query, ':customersID', $customer_id);
        $address = tep_db_fetch_array(tep_db_query($query));
        if(!empty($address['address_book_id'])) {
            return $address['address_book_id'];
        }
        return 0;
    }

    /**
     * @return array
     */
    public static function get_config() {
        $client_id = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_CLIENT_ID'));
        $client_id = $client_id['configuration_value'];
        $seller_id = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_SELLER_ID'));
        $seller_id = $seller_id['configuration_value'];
        $mws_access_key = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_MWS_ACCESS_KEY'));
        $mws_access_key = $mws_access_key['configuration_value'];
        $mws_secret_key = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_MWS_SECRET_KEY'));
        $mws_secret_key = $mws_secret_key['configuration_value'];
        $logging = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_LOGGING'));
        $logging = $logging['configuration_value'];
        $enabled = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_STATUS'));
        $enabled = $enabled['configuration_value'];
        $pay_enabled = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_STATUS'));
        $pay_enabled = $pay_enabled['configuration_value'];
        $sandbox = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_SANDBOX'));
        $sandbox = $sandbox['configuration_value'];
        $capture = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_CAPTURE'));
        $capture = $capture['configuration_value'];
        $mode = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_MODE'));
        $mode = $mode['configuration_value'];
        $region = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_AMAZON_REGION'));
        $region = $region['configuration_value'];
        $excluded_products = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_EXCLUDED_PRODUCTS'));
        $excluded_products = explode(',', $excluded_products['configuration_value']);
        if(sizeof($excluded_products) == 1 && trim($excluded_products[0]) == '') {
            $excluded_products = false;
        }
        $restricted_ips = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_PAYMENT_PAYWITHAMAZON_RESTRICTED_IPS'));
        $restricted_ips = explode(',', $restricted_ips['configuration_value']);
        if(sizeof($restricted_ips) == 1 && trim($restricted_ips[0]) == '') {
            $restricted_ips = false;
        }

        return array(
            'client_id' => $client_id,
            'seller_id' => $seller_id,
            'mws_access_key' => $mws_access_key,
            'mws_secret_key' => $mws_secret_key,
            'logging' => ($logging == 'True'),
            'enabled' => ($enabled == 'True'),
            'pay_enabled' => ($pay_enabled == 'True'),
            'sandbox' => ($sandbox == 'True'),
            'capture' => ($capture == 'True'),
            'mode' => $mode,
            'region' => strtolower($region),
            'excluded_products' => $excluded_products,
            'restricted_ips' => $restricted_ips
        );
    }

    public static function get_payments_client()
    {
        $lwa_config = self::get_config();
        $response_log = false;
        if($lwa_config['logging']) {
            $response_log = realpath(dirname(__FILE__)) . '/../../../../cache/amazon.log';
        }
        $config = array(
            'merchant_id' => $lwa_config['seller_id'],
            'access_key' => $lwa_config['mws_access_key'],
            'secret_key' => $lwa_config['mws_secret_key'],
            'client_id' => $lwa_config['client_id'],
            'region' => $lwa_config['region'],
            'sandbox' => $lwa_config['sandbox'],
            'response_log' => $response_log,
        );

        return new OffAmazonPaymentsService_Client($config);
    }

    public static function get_client_ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

}
<?php
/**
 * Amazon Login - Login for osCommerce
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2015 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon/db_helper.php');
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon/config.php');
/**
 * Class LoginWithAmazon_Tools
 */
class LoginWithAmazon_Tools
{
    const TABLE_NAME_ONLY = 'loginwithamazon_users';
    const TABLE_NAME_MERGED = 'loginwithamazon_mergedusers';
    const MERGE_ACCOUNTS_BEGIN = 'merge_amazon_account';
    const MERGE_ACCOUNTS_PROCESS = 'process_amazon_merge';
    const MERGE_ACCOUNTS_SUCCESS = 'amazon_account_merge_success';
    const CSRF_AUTHENTICATOR_SLUG = '_amazonlogin_csrf_authenticator';
    const CSRF_SALT_SLUG = '_amazonlogin_csrf_salt';

    /**
     * @param $first_name
     * @param $last_name
     * @param $email
     * @return integer Customer ID
     */
    public static function create_user($first_name, $last_name, $email) {
        $existing_customer = self::get_customer_id_by_email($email);

        if (!$existing_customer) {
            // Customer doesn't exist, create them.
            // tep_encrypt_password deals with actual hashing, this is simply generating a longer string.
            $password_string = md5( self::gen_random_string() );
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
            $cust = tep_db_perform(TABLE_CUSTOMERS, $customer_data);
            $cust_id = tep_db_insert_id();

            if ( !$cust_id ) {
                return FALSE;
            }

            // Set an invalid password
            $query = "UPDATE " . TABLE_CUSTOMERS . " SET `customers_password` = :pw WHERE `customers_id` = :id";
            $query = bind_vars($query, ':pw', 'LOGINWITHAMAZON00000000000000000');
            $query = bind_vars($query, ':id', $cust_id);
            tep_db_query($query);

            // Add user to the Amazon users table
            $amazon_table_safe = tep_db_input(self::TABLE_NAME_ONLY);
            $cust_id_safe = tep_db_input($cust_id);
            $query = "INSERT INTO ". $amazon_table_safe ." (customer_id) VALUES (". $cust_id_safe .")";
            tep_db_query($query);

            // Create customer info entry
            tep_db_query("insert into " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) values ('" . (int)$cust_id . "', '0', now())");


            return $cust_id;
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
     * @param $id
     * @return bool|int Customer ID or False
     */
    public static function get_customer_password_hash_by_id($id) {
        $query = "SELECT `customers_password` FROM " . TABLE_CUSTOMERS . " WHERE `customers_id` = :id";
        $query = bind_vars($query, ':id', $id);
        $result = tep_db_fetch_array(tep_db_query($query));
        return (!empty($result['customers_password']) ? $result['customers_password'] : false);
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
     * @return array
     */
    public static function get_config() {
        $client_id = tep_db_fetch_array(paywithamazon_config::get_config_value_query('MODULE_HEADER_TAGS_LOGINWITHAMAZON_CLIENT_ID'));
        $client_id = $client_id['configuration_value'];

        return array(
            'client_id' => $client_id
        );
    }


    public static function get_userdata() {
        $lwa_config = self::get_config();

        if ( self::get_access_token() && self::verify_csrf_token(self::get_state_token()) ) {
            $c = curl_init('https://api.amazon.com/auth/o2/tokeninfo?access_token=' . urlencode($_REQUEST['access_token']));
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            $r = curl_exec($c);
            curl_close($c);
            $data = json_decode($r);
            $client_id = $lwa_config['client_id'];
            if ($data->aud != $client_id) {
                header('HTTP/1.1 404 Not Found');
                echo 'Page not found';
                exit;
            }
            $c = curl_init('https://api.amazon.com/user/profile');
            curl_setopt($c, CURLOPT_HTTPHEADER, array('Authorization: bearer ' . self::get_access_token()));
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            $r = curl_exec($c);
            curl_close($c);
            $user_data = json_decode($r);

            return $user_data;
        } else {
            if(isset($_REQUEST['error'])) {
                tep_redirect(tep_href_link(FILENAME_LOGIN));
            } else {
                $redirect_back = tep_href_link(FILENAME_DEFAULT);
                $_SESSION['access_token_missing'] = true;
                $_SESSION['login_redirect_back'] = $redirect_back;
            }
        }
    }


    public static function setup_session() {
        if ( !headers_sent() && session_id() == '' ) {
            session_start();
        }

        if ( !isset($_SESSION[self::CSRF_AUTHENTICATOR_SLUG]) ) {
            $authenticator = self::gen_random_string(64);
            $_SESSION[self::CSRF_AUTHENTICATOR_SLUG] = $authenticator;

            $salt = self::gen_random_string(64);
            $_SESSION[self::CSRF_SALT_SLUG] = $salt;
        }
    }


    public static function verify_csrf_token($token) {
        if ( !self::get_csrf_token() ) {
            return FALSE;
        }

        return strcmp($token, self::get_csrf_token()) === 0;
    }


    public static function get_csrf_token() {
        if ( isset($_SESSION[self::CSRF_AUTHENTICATOR_SLUG]) && isset($_SESSION[self::CSRF_SALT_SLUG]) ) {
            $salt = $_SESSION[self::CSRF_SALT_SLUG];
            return self::hmac( $_SESSION[self::CSRF_AUTHENTICATOR_SLUG], $salt );
        }

        return FALSE;
    }


    public static function hmac($authenticator, $salt) {
        return hash_hmac('sha256', $authenticator, $salt);
    }


    /**
     * @param int $length
     * @return string
     */
    private static function gen_random_string($length = 20) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password_string = '';

        for ($i = 0; $i < $length; $i++) {
            $password_string = $characters[rand(0, strlen($characters))];
        }

        return $password_string;
    }


    public static function get_access_token() {
        if ( !isset($_REQUEST['access_token']) ) {
            return FALSE;
        }

        return $_REQUEST['access_token'];
    }

    private static function get_state_token() {
        return ( isset($_REQUEST['state']) ) ? $_REQUEST['state'] : NULL;
    }


    public static function create_usermeta_tables() {
        foreach ([self::TABLE_NAME_ONLY, self::TABLE_NAME_MERGED] as $table_name) {
            if ( !self::_db_table_exists($table_name) ) {
                $query = "
                    CREATE TABLE ". tep_db_input($table_name) ." (
                        customer_id INT(11) NOT NULL,
                        PRIMARY KEY(customer_id)
                    )
                ";

                $result = tep_db_query($query);
            }
        }
    }


    public static function is_amazon_user_any($cust_id) {
        return (self::is_amazon_user($cust_id) || self::is_amazon_user_merged($cust_id));
    }

    public static function is_amazon_user_only($cust_id) {
        // User should exist the standard Amazon user table, but not the merged table.
        $table_only = tep_db_input(self::TABLE_NAME_ONLY);
        $table_merged = tep_db_input(self::TABLE_NAME_MERGED);
        $id = tep_db_input($cust_id);
        $query = "
            SELECT ONLY.customer_id
            FROM {$table_only} ONLY LEFT JOIN {$table_merged} MERGED ON ONLY.customer_id = MERGED.customer_id
            WHERE ONLY.customer_id = {$id} AND MERGED.customer_id IS NULL
        ";
        $result = tep_db_query($query);

        return $result->num_rows > 0;
    }

    public static function is_amazon_user($cust_id) {
        // User should exist the standard Amazon user table
        $table = tep_db_input(self::TABLE_NAME_ONLY);
        $id = tep_db_input($cust_id);
        $query = "SELECT customer_id FROM {$table} WHERE `customer_id`='{$id}'";
        $result = tep_db_query($query);

        return $result->num_rows > 0;
    }

    public static function is_amazon_user_merged($cust_id) {
        // User should exist the standard Amazon user merged table.
        $table = tep_db_input(self::TABLE_NAME_MERGED);
        $id = tep_db_input($cust_id);
        $query = "SELECT customer_id FROM {$table} WHERE `customer_id`='{$id}'";
        $result = tep_db_query($query);

        return $result->num_rows > 0;
    }


    private static function _db_table_exists($table_name) {
        $query = "SHOW TABLES LIKE '". tep_db_input($table_name) ."'";
        $result = tep_db_query($query);

        return $result->num_rows !== 0;
    }


    public static function get_merge_url($extra_params = array()) {
        $params = array(
            self::MERGE_ACCOUNTS_BEGIN . "=1",
            "access_token=" . self::get_access_token(),
            "state=" . self::get_state_token(),
        );

        $all_params = array_merge($extra_params, $params);
        $base = tep_href_link(FILENAME_LOGIN, '', 'SSL');

        return $base . '?' . join('&', $all_params);
    }


    public static function add_user_to_merge_table($cust_id) {
        // Add user to the Amazon users merge table
        $amazon_table_safe = tep_db_input(self::TABLE_NAME_MERGED);
        $cust_id_safe = tep_db_input($cust_id);
        $query = "INSERT INTO ". $amazon_table_safe ." (customer_id) VALUES (". $cust_id_safe .")";
        tep_db_query($query);
    }

}

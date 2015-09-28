<?php
/**
 * Amazon Login - Login for osCommerce
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2015 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */
include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');

LoginWithAmazon_Tools::setup_session();

if(!class_exists('ht_loginwithamazon')) {
    class ht_loginwithamazon {
        var $code = 'ht_loginwithamazon';
        var $group = 'header_tags';
        var $title;
        var $description;
        var $sort_order;
        var $enabled = false;

        function ht_loginwithamazon() {
            $this->title = MODULE_HEADER_TAGS_LOGINWITHAMAZON_TITLE;
            $this->description = MODULE_HEADER_TAGS_LOGINWITHAMAZON_DESCRIPTION;
            if(defined('MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS')) {
                $this->sort_order = MODULE_HEADER_TAGS_LOGINWITHAMAZON_SORT_ORDER;
                $this->enabled = (MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS == 'True');
            }
        }

        function execute() {
            global $PHP_SELF;
            switch(basename($PHP_SELF)) {
                //case FILENAME_LOGIN:
                //    $this->renderLogin();
                //    break;
                //case FILENAME_CREATE_ACCOUNT:
                //    $this->renderLogin();
                //    break;
                case FILENAME_SHOPPING_CART:
                    $this->renderCart();
                    break;
                case FILENAME_CHECKOUT_PAYMENT:
                    $this->renderCheckoutPaymentStep();
                    break;
                case FILENAME_CHECKOUT_SHIPPING:
                    $this->renderCheckoutShippingStep();
                    break;
                case FILENAME_CHECKOUT_CONFIRMATION:
                    $this->renderCheckoutConfirmationStep();
                    break;
            }

            // Intercept POST requests to the registration page.
            if ( basename($PHP_SELF) == FILENAME_CREATE_ACCOUNT && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                $this->renderRegistrationPost();
            }

            // Enable the account merge process
            if ( isset($_GET[LoginWithAmazon_Tools::MERGE_ACCOUNTS_BEGIN]) ) {
                $this->renderMerge();

                // Intercept POST requests to the login page.
                if ( basename($PHP_SELF) == FILENAME_LOGIN && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
                    $this->renderMergePost();
                }
            }

            // Show the success message after merging acounts
            if ( basename($PHP_SELF) == FILENAME_ACCOUNT && isset($_GET[LoginWithAmazon_Tools::MERGE_ACCOUNTS_SUCCESS]) ) {
                global $messageStack;
                $messageStack->add('account', MODULE_HEADER_TAGS_LOGINWITHAMAZON_MERGE_SUCCESS, 'success');
            }

            $this->renderLogin();
        }

        function isEnabled() {
            return $this->enabled;
        }

        function check() {
            return defined('MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS');
        }

        function install() {
            tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Login With Amazon Module', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS', 'True', 'Do you want to enable the Login With Amazon module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
            tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
            tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Amazon Client ID', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_CLIENT_ID', '', '', '6', '2', now())");
            LoginWithAmazon_Tools::create_usermeta_tables();
        }

        function remove() {
            tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }

        function keys() {
            return array('MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_SORT_ORDER', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_CLIENT_ID');
        }

        function renderLogin() {
            global $oscTemplate;
            $lwa_config = LoginWithAmazon_Tools::get_config();
            $csrf_token = LoginWithAmazon_Tools::get_csrf_token();

            if ( $lwa_config['client_id'] && $csrf_token ) {
                $lwa_client_id = $lwa_config['client_id'];
                $popup = 'true';
                if(empty($_SERVER['HTTPS'])) {
                    $popup = 'false';
                }
                $output = '<script type="text/javascript">' . "\n";
                $output .= 'var client_id = \'' . $lwa_client_id . '\';' . "\n";
                $output .= 'var popup = ' . $popup . ';' . "\n";
                $output .= 'var login_success_url = \'' . tep_href_link('loginwithamazon.php', '', 'SSL') . '\';' . "\n";
                $output .= 'function r(f){/in/.test(document.readyState)?setTimeout(\'r(\'+f+\')\',9):f()}' . "\n";
                $output .= "
                r(function(){
                    var amazon_root = document.createElement('div');
                    amazon_root.setAttribute('id', 'amazon-root');
                    document.body.appendChild(amazon_root);
                    window.onAmazonLoginReady = function() {
                        amazon.Login.setClientId(client_id);
                        amazon.Login.setUseCookie(true);
                        elements = document.getElementsByTagName('a');
                        for(var i=0; i<elements.length; i++){
                            if (elements[i] && elements[i].getAttribute('href') && elements[i].getAttribute('href').indexOf('logoff.php', elements[i].getAttribute('href').length - 10) !== -1) { 
                                elements[i].onclick = function() { 
                                    amazon.Login.logout();
                                }
                            }
                        }
                    };
                    (function(d) {
                        var a = d.createElement('script'); a.type = 'text/javascript';
                        a.async = true; a.id = 'amazon-login-sdk';
                        a.src = 'https://api-cdn.amazon.com/sdk/login1.js';
                        d.getElementById('amazon-root').appendChild(a);
                    })(document);

                    var loginWithAmazonButton = document.createElement('div');
                    loginWithAmazonButton.setAttribute('id', 'LoginWithAmazon');
                    loginWithAmazonButton.setAttribute('style', 'clear:both;float:right;cursor:pointer;');
                    loginWithAmazonButton.innerHTML = '<img border=\"0\" alt=\"Login with Amazon\" src=\"https://images-na.ssl-images-amazon.com/images/G/01/lwa/btnLWA_gold_156x32.png\" width=\"156\" height=\"32\" />';

                    var loginForm = $('form[name=login]');
                    if(loginForm.length > 0) {
                        loginForm = loginForm[0];
                        loginForm.parentNode.insertBefore(loginWithAmazonButton, loginForm.nextSibling);
                        document.getElementById('LoginWithAmazon').onclick = function() {
                            var loginOptions = {
                                scope : 'profile',
                                state: '".$csrf_token."',
                                popup: popup,
                            };
                            amazon.Login.authorize(loginOptions, login_success_url);
                            return false;
                        }
                    }
                    var registerForm = $('form[name=create_account]');
                    if(registerForm.length > 0) {
                        registerForm = registerForm[0];
                        loginWithAmazonButton.setAttribute('style', 'clear:both;float:right;cursor:pointer;margin-top:10px;');
                        $(registerForm).append(loginWithAmazonButton);
                        document.getElementById('LoginWithAmazon').onclick = function() {
                            var loginOptions = {
                                scope : 'profile',
                                state: '".$csrf_token."',
                                popup: popup,
                            };
                            amazon.Login.authorize(loginOptions, login_success_url);
                            return false;
                        }
                    }
                });
            " . "\n";
                $output .= '</script>' . "\n";
                $oscTemplate->addBlock($output . "\n", $this->group);
            }
        }


        /**
         * Alter the login form. Prompt for password to link Amazon and native accounts.
         */
        private function renderMerge() {
            global $oscTemplate;
            global $messageStack;
            $lwa_config = LoginWithAmazon_Tools::get_config();
            $messageStack->add('login', MODULE_HEADER_TAGS_LOGINWITHAMAZON_SHOULD_MERGE, 'success');

            $action_url = LoginWithAmazon_Tools::get_merge_url(array('action=process'));

            ob_start();
            ?>

            <script type="text/javascript">
            // document ready
            function amazonDocumentReady(fn) {
                if (document.readyState != 'loading'){
                    fn();
                } else if (document.addEventListener) {
                    document.addEventListener('DOMContentLoaded', fn);
                } else {
                    document.attachEvent('onreadystatechange', function() {
                        if (document.readyState != 'loading')
                            fn();
                    });
                }
            }

            amazonDocumentReady(function() {
                // Hide the email field
                var loginForm = document.querySelector('#loginModules form[name="login"]');
                if (!loginForm) return;
                loginForm.setAttribute('action', '<?php echo $action_url; ?>');

                var emailInput = loginForm.querySelector('input[name="email_address"]');
                if (!emailInput) return;

                var emailInputRow = emailInput.parentNode.parentNode;
                if (!emailInputRow) return;
                emailInputRow.style.display = 'none';

                // Change the form button's text
                var buttonSpan = loginForm.querySelector('button[type="submit"] .ui-button-text');
                if (!buttonSpan) return;
                var buttonText = 'Link My Amazon Account';

                if (typeof buttonSpan.textContent !== 'undefined') {
                    buttonSpan.textContent = buttonText;
                } else {
                    buttonSpan.innerText = buttonText;
                }
            });
            </script>

            <?php
            $output = ob_get_contents();
            ob_end_clean();

            $oscTemplate->addBlock($output . "\n", $this->group);
        }


        /**
         * If an Amazon user tries to register a native account, let them know.
         *
         * @return bool
         */
        private function renderRegistrationPost() {
            $email = ( isset($_POST['email_address']) ) ? $_POST['email_address'] : NULL;

            if ( empty($email) ) {
                return TRUE;
            }

            $cust_id = LoginWithAmazon_Tools::get_customer_id_by_email($email);

            if ( !$cust_id ) {
                return TRUE;
            }

            if ( LoginWithAmazon_Tools::is_amazon_user_any($cust_id) ) {
                // Set an error message
                global $messageStack;
                $messageStack->add('create_account', MODULE_HEADER_TAGS_LOGINWITHAMAZON_REGISTRATION_AMAZON_ALREADY_EXISTS, 'success');
            }
        }


        /**
         * If a native user tries log in with Amazon, prompt them to merge the accounts.
         *
         * @return bool
         */
        private function renderMergePost() {
            $user_data = LoginWithAmazon_Tools::get_userdata();
            $email = $user_data->email;
            $password = ( isset($_POST['password']) ) ? $_POST['password'] : NULL;

            if ( empty($email) ) {
                return TRUE;
            }

            $cust_id = LoginWithAmazon_Tools::get_customer_id_by_email($email);

            if ( !$cust_id ) {
                return TRUE;
            }

            $password_hash = LoginWithAmazon_Tools::get_customer_password_hash_by_id($cust_id);

            if ( !LoginWithAmazon_Tools::is_amazon_user_any($cust_id) ) {
                global $messageStack;
                $authenticated = tep_validate_password($password, $password_hash);

                if ( $authenticated ) {
                    LoginWithAmazon_Tools::add_user_to_merge_table($cust_id);
                    LoginWithAmazon_Tools::login_user($cust_id);
                    tep_redirect(tep_href_link(FILENAME_ACCOUNT, LoginWithAmazon_Tools::MERGE_ACCOUNTS_SUCCESS . '=1'));
                } else {
                    // Set an error message
                    $messageStack->add('login', MODULE_HEADER_TAGS_LOGINWITHAMAZON_MERGE_FAIL, 'error');
                }
            }
        }
    }
}

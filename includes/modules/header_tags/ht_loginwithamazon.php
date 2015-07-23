<?php
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
                case FILENAME_LOGIN:
                    $this->renderLogin();
                    break;
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
        }

        function isEnabled() {
            return $this->enabled;
        }

        function check() {
            return defined('MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS');
        }

        function install() {
            tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Login & Pay With Amazon Module', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS', 'True', 'Do you want to enable the Login & Pay With Amazon module?', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
            tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
        }

        function remove() {
            tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        }

        function keys() {
            return array('MODULE_HEADER_TAGS_LOGINWITHAMAZON_STATUS', 'MODULE_HEADER_TAGS_LOGINWITHAMAZON_SORT_ORDER');
        }

        function renderLogin() {
            global $oscTemplate;
            include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');
            $lwa_config = loginwithamazon_tools::get_config();
            if($lwa_config['enabled'] && (!$lwa_config['restricted_ips'] || in_array(loginwithamazon_tools::get_client_ip(), $lwa_config['restricted_ips']))) {
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
                            loginOptions = { scope : 'profile payments:widget payments:shipping_address' };
                            loginOptions['popup'] = popup;
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

        function renderCart() {
            global $oscTemplate;
            include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');
            $lwa_config = loginwithamazon_tools::get_config();
            if($lwa_config['enabled'] && $lwa_config['pay_enabled'] && (!$lwa_config['restricted_ips'] || in_array(loginwithamazon_tools::get_client_ip(), $lwa_config['restricted_ips'])) && !$this->cart_contains_product_in_array($lwa_config['excluded_products'])) {
                $lwa_client_id = $lwa_config['client_id'];
                $lwa_seller_id = $lwa_config['seller_id'];
                $sandbox = $lwa_config['sandbox'] ? 'sandbox/' : '';
                $popup = 'true';
                if(empty($_SERVER['HTTPS'])) {
                    $popup = 'false';
                }
                $output = '<script type="text/javascript" src="https://static-na.payments-amazon.com/OffAmazonPayments/us/' . $sandbox . 'js/Widgets.js?sellerId=' . $lwa_seller_id . '"></script>';
                $output .= '<script type="text/javascript">' . "\n";
                $output .= 'var client_id = \'' . $lwa_client_id . '\';' . "\n";
                $output .= 'var seller_id = \'' . $lwa_seller_id . '\';' . "\n";
                $output .= 'var popup = ' . $popup . ';' . "\n";
                $output .= 'var sandbox = \'' . $sandbox . '\';' . "\n";
                $output .= 'var login_success_url = \'' . tep_href_link('paywithamazon.php', '', 'SSL') . '\';' . "\n";
                $output .= 'window.onAmazonLoginReady = function() { amazon.Login.setClientId(client_id); };window.onAmazonLoginReady();' . "\n";
                $output .= 'function r(f){/in/.test(document.readyState)?setTimeout(\'r(\'+f+\')\',9):f()}' . "\n";
                $output .= "
                r(function(){
                    var payWithAmazonButton = document.createElement('div');
                    payWithAmazonButton.setAttribute('id', 'AmazonPayButton');
                    payWithAmazonButton.setAttribute('style', 'clear:both;float:right;cursor:pointer;');

                    var cartForm = $('.buttonSet')[0];
                    cartForm.parentNode.insertBefore(payWithAmazonButton, cartForm.nextSibling);

                    var authRequest;
                    OffAmazonPayments.Button(\"AmazonPayButton\", seller_id, {
                        type:  \"PwA\",
                        color: \"Gold\",
                        size:  \"medium\",
                        useAmazonAddressBook: true,
                        authorization: function() {
                            var loginOptions = {scope: 'profile payments:widget payments:shipping_address'};
                            loginOptions['popup'] = popup;
                            authRequest = amazon.Login.authorize(loginOptions, login_success_url);
                        },
                        onError: function(error) {
                        }
                    });
                });
            " . "\n";
                $output .= '</script>' . "\n";
                $oscTemplate->addBlock($output . "\n", $this->group);
            }
        }

        function renderCheckoutPaymentStep() {
            global $oscTemplate;
            include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');
            $lwa_config = loginwithamazon_tools::get_config();
            if($lwa_config['enabled']) {
                $output = '<script type="text/javascript">' . "\n";
                if(!loginwithamazon_tools::get_customer_address_id($_SESSION['customer_id'])) {
                    $address_id = loginwithamazon_tools::create_customer_blank_address($_SESSION['customer_id']);
                    loginwithamazon_tools::set_new_default_address_id($_SESSION['customer_id'], $address_id);
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT_ADDRESS));
                }

                if($lwa_config['pay_enabled'] && isset($_SESSION['amzn_order_id'])) {
                    $output .= '$(function() {' . "\n";
                    $output .= '$(\'form[name=checkout_payment] a\').hide();' . "\n";
                    $output .= 'var addr = $(\'form[name=checkout_payment] .contentText\').first()' . "\n";
                    $output .= 'addr.html(addr.find(\'.infoBoxContainer\').css(\'float\',\'none\'))' . "\n";
                    $output .= '$(\'input[name=payment]\').parent().parent().hide();' . "\n";
                    $output .= '$(\'input[name=payment][value=paywithamazon]\').parent().parent().show();' . "\n";
                    $output .= '$(\'input[name=payment][value=paywithamazon]\').attr(\'checked\', true).trigger(\'click\');' . "\n";
                    $output .= '});' . "\n";
                }
                $output .= '</script>' . "\n";
                $oscTemplate->addBlock($output . "\n", $this->group);
            }
        }

        function renderCheckoutShippingStep() {
            global $oscTemplate;
            include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');
            $lwa_config = loginwithamazon_tools::get_config();
            if($lwa_config['enabled']) {
                $output = '<script type="text/javascript">' . "\n";
                if(!loginwithamazon_tools::get_customer_address_id($_SESSION['customer_id'])) {
                    $address_id = loginwithamazon_tools::create_customer_blank_address($_SESSION['customer_id']);
                    loginwithamazon_tools::set_new_default_address_id($_SESSION['customer_id'], $address_id);
                    tep_redirect(tep_href_link(FILENAME_CHECKOUT_SHIPPING_ADDRESS));
                }
                if($lwa_config['pay_enabled'] && isset($_SESSION['amzn_order_id'])) {
                    $output .= '$(function() {' . "\n";
                    $output .= '$(\'form[name=checkout_address] a\').hide();' . "\n";
                    $output .= 'var addr = $(\'form[name=checkout_address] .contentText\').first()' . "\n";
                    $output .= 'addr.html(addr.find(\'.infoBoxContainer\').css(\'float\',\'none\'))' . "\n";
                    $output .= '});' . "\n";
                }
                $output .= '</script>' . "\n";
                $oscTemplate->addBlock($output . "\n", $this->group);
            }
        }

        function renderCheckoutConfirmationStep() {
            global $oscTemplate;
            include_once(DIR_FS_CATALOG . 'includes/modules/loginwithamazon/tools.php');
            $lwa_config = loginwithamazon_tools::get_config();
            if($lwa_config['enabled']) {
                $output = '<script type="text/javascript">' . "\n";
                if($lwa_config['pay_enabled'] && isset($_SESSION['amzn_order_id'])) {
                    $output .= '$(function() {' . "\n";
                    $output .= '$(\'.orderEdit\').hide()';
                    $output .= '});' . "\n";
                }
                $output .= '</script>' . "\n";
                $oscTemplate->addBlock($output . "\n", $this->group);
            }
        }


        function cart_contains_product_in_array($product_ids) {
            if(!is_array($product_ids)) {
                return false;
            }
            foreach($product_ids as $product_id) {
                if($this->cart_contains_product($product_id)) {
                    return true;
                }
            }
            return false;
        }

        function cart_contains_product($product_id)
        {
            global $cart;
            if($cart->count_contents() > 0) {
                $products = $cart->get_products();
                $products_count = sizeof($products);
                for ($i=0; $i < $products_count; $i++) {
                    if($products[$i]['id'] == $product_id) {
                        return true;
                    }
                }
            }
            return false;
        }
    }
}
<?php
function ddd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}
/**
 * Amazon Login - Login for osCommerce
 *
 * @category    Amazon
 * @package     Amazon_Login
 * @copyright   Copyright (c) 2015 Amazon.com
 * @license     http://opensource.org/licenses/Apache-2.0  Apache License, Version 2.0
 */
require('includes/application_top.php');
include_once(DIR_WS_MODULES . 'loginwithamazon/tools.php');
include_once(DIR_WS_MODULES . 'payment/paywithamazon/lib/Client.php');

// Start...
if ( LoginWithAmazon_Tools::get_access_token() ) {
    $user_data = LoginWithAmazon_Tools::get_userdata();

    $user_email = $user_data->email;
    $user_id = LoginWithAmazon_Tools::get_customer_id_by_email($user_email);
    $new_account = false;

    if ( $user_id ) {
        if ( !LoginWithAmazon_Tools::is_amazon_user_any($user_id) ) {
            // If user exists, but is NOT an Amazon user, then start the merge prompt.
            tep_redirect( LoginWithAmazon_Tools::get_merge_url() );
            exit;
        }
    } else {
        $name = explode(' ', $user_data->name, 2);
        $user_id = LoginWithAmazon_Tools::create_user($name[0], $name[1], $user_email);
        $new_account = true;
    }
    LoginWithAmazon_Tools::login_user($user_id);

    if ($new_account) {
        tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS));
        tep_redirect(tep_href_link(FILENAME_DEFAULT));
    } else {
        tep_redirect(tep_href_link(FILENAME_DEFAULT));
    }
}

?>
<html>
    <head>
        <script type="text/javascript">
            function getURLParameter(name, source) {
                return decodeURIComponent((new RegExp('[?|&|#]' + name + '=' +
                    '([^&;]+?)(&|#|;|$)').exec(source)||[,""])[1].replace(/\+/g,'%20'))||
                    null;
            }

            var accessToken = getURLParameter("access_token", location.hash);
            var state = getURLParameter("state", location.hash);

            if (typeof accessToken === 'string' && accessToken.match(/^Atza/)) {
                document.cookie = "amazon_Login_accessToken=" + accessToken + ";secure";
                var url = '<?php echo tep_href_link('loginwithamazon.php', '', 'SSL'); ?>';
                window.location = url + '?access_token=' + accessToken + '&state=' + state;
            }
        </script>
    </head>
    <body>
        <h1>Please Wait...</h1>
    </body>
</html>

<?php
require('includes/application_top.php');
include_once(DIR_WS_MODULES . 'loginwithamazon/tools.php');
include_once(DIR_WS_MODULES . 'payment/paywithamazon/lib/Client.php');
$lwa_config = loginwithamazon_tools::get_config();

$show_missing_token_script = false;
if(isset($_SESSION['access_token_missing']) && $_SESSION['access_token_missing']) {
    $missing_token_redirect_back = $_SESSION['login_redirect_back'];
    unset($_SESSION['access_token_missing']);
    unset($_SESSION['login_redirect']);
    unset($_SESSION['login_redirect_back']);
    $show_missing_token_script = true;
}
// Start...
if(isset($_REQUEST['access_token'])) {
    $client = loginwithamazon_tools::get_payments_client();
    $user_info = $client->getUserInfo($_REQUEST['access_token']);
    $user_email = $user_info['email'];
    $user_id = loginwithamazon_tools::get_customer_id_by_email($user_email);
    $new_account = false;
    if(!$user_id) {
        $name = explode(' ', $user_info['name'], 2);
        $user_id = loginwithamazon_tools::create_user($name[0], $name[1], $user_email);
        $new_account = true;
    }
    loginwithamazon_tools::login_user($user_id);

    if($new_account) {
        tep_redirect(tep_href_link(FILENAME_CREATE_ACCOUNT_SUCCESS));
        tep_redirect(tep_href_link(FILENAME_DEFAULT));
    } else {
        tep_redirect(tep_href_link(FILENAME_DEFAULT));
    }
} else {
    $redirect_back = tep_href_link(FILENAME_DEFAULT);
    $_SESSION['access_token_missing'] = true;
    $_SESSION['login_redirect_back'] = $redirect_back;
}
?>
<html>
    <head>
        <?php if($show_missing_token_script): ?>
            <script type="text/javascript">
                function getURLParameter(name, source) {
                    return decodeURIComponent((new RegExp('[?|&|#]' + name + '=' +
                        '([^&;]+?)(&|#|;|$)').exec(source)||[,""])[1].replace(/\+/g,'%20'))||
                        null;
                }
                var accessToken = getURLParameter("access_token", location.hash);
                if (typeof accessToken !== 'string' || !accessToken.match(/^Atza/)) {
                    window.location = '<?php echo $missing_token_redirect_back; ?>';
                }
            </script>
        <?php endif; ?>
        <script type="text/javascript">
            function getURLParameter(name, source) {
                return decodeURIComponent((new RegExp('[?|&|#]' + name + '=' +
                    '([^&;]+?)(&|#|;|$)').exec(source)||[,""])[1].replace(/\+/g,'%20'))||
                    null;
            }
            var accessToken = getURLParameter("access_token", location.hash);
            if (typeof accessToken === 'string' && accessToken.match(/^Atza/)) {
                document.cookie = "amazon_Login_accessToken=" + accessToken + ";secure";
                window.location = '<?php echo tep_href_link('loginwithamazon.php', '', 'SSL'); ?>&access_token=' + accessToken;
            }
        </script>
    </head>
    <body>
        <h1>Please Wait...</h1>
    </body>
</html>
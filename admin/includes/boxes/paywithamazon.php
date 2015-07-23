<?php
include_once(DIR_FS_CATALOG . 'includes/modules/payment/paywithamazon.php');
if(basename($GLOBALS['PHP_SELF']) == 'orders.php' && isset($_GET['oID']) && $_GET['action'] == 'edit'):
    $orderId = $_GET['oID'];
    $order = new order($orderId);
    if($order->info['payment_method'] == 'Pay With Amazon'):
        $pwa = new paywithamazon();
        $output = str_replace('\'', '\\\'', $pwa->admin_notification($orderId));
        ?>
        <script type="text/javascript">
            $(document).ready(function () {
                $('#contentText > table').first().prepend('<?php echo $output; ?>');
            });
        </script>
    <?php endif; ?>
<?php endif; ?>
<?php
require 'includes/modules/payment/paywithamazon/lib/MarketplaceWebServiceSellers/Client.php';
require 'includes/modules/payment/paywithamazon/lib/MarketplaceWebServiceSellers/Model/ListMarketplaceParticipationsRequest.php';

$config = array (
    'ServiceURL' => "https://mws.amazonservices.com/Sellers/2011-07-01",
    'ProxyHost' => null,
    'ProxyPort' => -1,
    'ProxyUsername' => null,
    'ProxyPassword' => null,
    'MaxErrorRetry' => 3,
);
$service = new MarketplaceWebServiceSellers_Client(
    $_GET['mws_access_key'],
    $_GET['mws_secret_key'],
    'Login and Pay for Zen Cart',
    '1.0',
    $config);
$request = new MarketplaceWebServiceSellers_Model_ListMarketplaceParticipationsRequest();
$request->setSellerId($_GET['seller_id']);
try {
    $service->ListMarketplaceParticipations($request);
    echo "Your Amazon Credentials are correct.<br />";
}
catch (MarketplaceWebServiceSellers_Exception $ex) {
    if ($ex->getErrorCode() == 'InvalidAccessKeyId'){
        echo "Your Amazon MWS Access Key is incorrect.<br />";
    }
    else if ($ex->getErrorCode() == 'SignatureDoesNotMatch'){
        echo "Your Amazon MWS Secret Key is incorrect.<br />";
    }
    else if ($ex->getErrorCode() == 'InvalidParameterValue'){
        echo "Your Amazon Seller/Merchant ID is incorrect.<br />";
    }
    else if ($ex->getErrorCode() == 'AccessDenied') {
        echo "Your Amazon Seller/Merchant ID does not match the MWS keys provided.<br />";
    }
    else{
        echo "Error Message: " . $ex->getMessage() . "<br />";
        echo "Response Status Code: " . $ex->getStatusCode() . "<br />";
        echo "Error Code: " . $ex->getErrorCode() . "<br />";
    }
}

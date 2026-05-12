<?php

require(__DIR__ . "/../../../../init.php");

use WHMCS\Module\Server\OpenproviderSsl\Helper;
use WHMCS\Database\Capsule;

if (!defined('WHMCS')) {
    die('You cannot access this file directly.');
}
try {
    global $whmcs;
    global $CONFIG;
    $helper = new Helper();

    // $productData = Capsule::table('tblproducts')->where('id', $whmcs->get_req_var("reseller_pid"))->first();
    // $baseUrl = ($productData->configoption3 == '') ? 'https://www.openprovider.com/' : $productData->configoption3;

    // if (($whmcs->get_req_var("ajaxaction") == "Product Description") && ($whmcs->get_req_var("ajaxcall") == true)) {

    //     $message = ["status" => true, "html" => $html];
    //     echo json_encode($message);
    //     exit;
    // }
    
} catch (\Exception $e) {
    logActivity("Error Occur Open Provider Configrable ajax " . $e->getMessage());
}

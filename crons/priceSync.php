<?php

$whmcspath = "";

if (file_exists(dirname(__FILE__) . "/config.php"))
    require_once dirname(__FILE__) . "/config.php";

if (!empty($whmcspath)) {
    require_once $whmcspath . "/init.php";
} else {
    require(__DIR__ . "/../init.php");
}

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\OpenproviderSsl\Helper;
use Module\OpenproviderSsl\classes\ApiCall;

try {
    global $CONFIG;

    $helper = new Helper();
    $apiCall = new ApiCall();

    $whmcsAllProducts = Capsule::table("tblproducts")->where("servertype", "openprovider_ssl")->get();

    foreach ($whmcsAllProducts as $key => $product) {

        $whmcsPid = $product->id;
        $sslPid = $product->configoption1;

        $where = ['id' => '1'];
        $baseUrl = $helper->fetch_table_record('modssl_api_setting', $where, 'singleValue', 'api_url');

        $allSslProduct = $apiCall->get($baseUrl . "/ssl/products", [], "Get SSL Product List");

        $price = '';
        foreach ($allSslProduct['result']->data->results as $products) {
            if ($sslPid == $products->id) {

                $setMargin = Capsule::table("tbladdonmodules")
                    ->where('module', 'openprovider_ssl')
                    ->where('setting', 'margin')
                    ->value('value');

                $marginPrice =   $products->warranty->product->price * $setMargin / 100;
                $price = ($marginPrice + $products->warranty->product->price);
                $productPrices = $helper->productPrices($price);
                $updateproductprice = $helper->updateprice($products->warranty->product->currency, $whmcsPid, 'product', $productPrices);
            }
        }
    }
} catch (\Exception $e) {
    logActivity("Error Occur OpenproviderSsl Cron " . $e->getMessage());
}

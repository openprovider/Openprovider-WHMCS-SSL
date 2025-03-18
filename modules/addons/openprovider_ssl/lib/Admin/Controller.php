<?php

namespace WHMCS\Module\Addon\OpenproviderSsl\Admin;

use WHMCS\Module\Addon\OpenproviderSsl\Helper;
use WHMCS\Database\Capsule;
use Smarty;
use Module\OpenproviderSsl\classes\ProductSetting;
use Module\OpenproviderSsl\classes\EmailTemplates;
use Module\OpenproviderSsl\classes\ApiCall;
use Module\OpenproviderSsl\classes\CustomDatabase;
use Module\OpenproviderSsl\classes\Upgrade;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (file_exists(__DIR__ . DS . 'classes/ApiCall.php')) {
    require_once __DIR__ . DS . 'classes/ApiCall.php';
}

class Controller
{
    public $tplFileName;
    public $tplDIR;
    public $smarty;
    public $tplVar = array();

    public function __construct($params)
    {
        global $CONFIG;
        global $whmcs;

        $this->params = $params;
        $this->tplVar['rootURL'] = $CONFIG["SystemURL"];
        $this->tplVar['urlPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/";
        $this->tplVar['lang'] = $params["_lang"];
        $this->tplVar['moduleLink'] = $params['modulelink'];
        $this->tplVar['module'] = $params['module'];
        $this->tplVar['license'] = $params['license'];
        $this->tplVar['version'] = $params['version'];
        $this->tplVar['action'] = $whmcs->get_req_var("action");
        $this->tplVar['license_key'] = $params['licenseNumtoactivate'];
        $this->tplVar['tplDIR'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/";
        $this->tplVar['header'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/header.tpl";
        $this->tplVar['footer'] = ROOTDIR . "/modules/addons/{$params['module']}/templates/admin/footer.tpl";
        $this->tplVar['cssPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/css/";
        $this->tplVar['scriptPath'] = $CONFIG["SystemURL"] . "/modules/addons/{$params['module']}/assets/js/";
    }

    public function fileNotFound()
    {
        $this->tplFileName = __FUNCTION__;
        $this->output();
    }

    public function apisetting()
    {
        global $whmcs;
        global $CONFIG;
        $helper = new Helper();
        $apiCall = new ApiCall();

        if (isset($_SESSION["adminid"])) {

            // Save details into DB
            if (($whmcs->get_req_var("ajaxaction") == "Save Setting") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $data = html_entity_decode($whmcs->get_req_var("data"));
                parse_str($data, $dataArray);

                if (empty($dataArray["api_user_name"]) || empty($dataArray["api_password"]) || empty($dataArray["api_url"])) {
                    $helper->sendResponse(false, 'Username, Password, and API URL are required.');
                }

                $data = ['api_url' => $dataArray["api_url"], 'api_user_name' => $dataArray["api_user_name"], 'api_password' => encrypt($dataArray["api_password"])];
                $where = ['id' => '1'];
                $updateReseller =  $helper->insertUpdate('modssl_api_setting', $where, $data);

                if (str_contains($updateReseller, 'Error')) {
                    $message = ["status" => false, "message" => $updateReseller];
                } else {
                    $message = ["status" => true, "message" => $updateReseller];
                }

                echo json_encode($message);
                exit;
            }

            // Test Connection With API
            if (($whmcs->get_req_var("ajaxaction") == "Api Test Connection") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $data = html_entity_decode($whmcs->get_req_var("data"));
                parse_str($data, $dataArray);

                $postData = [
                    'username' => $dataArray["api_user_name"],
                    'password' => $dataArray["api_password"],
                ];

                $testConnection = $apiCall->post("{$dataArray['api_url']}/auth/login", $postData, "Open Ssl Test Connection");

                $data = ['token' => $testConnection['result']->data->token];
                $where = ['id' => '1'];
                $updateReseller =  $helper->insertUpdate('modssl_api_setting', $where, $data);

                if ($testConnection['httpcode'] != '200') {

                    $helper->sendResponse(false, $testConnection['result']->desc);
                }

                $data = ['token' => $testConnection['result']->data->token];
                $where = ['id' => '1'];
                $updateReseller =  $helper->insertUpdate('modssl_api_setting', $where, $data);

                $helper->sendResponse(true, 'Connection Successfully!');
            }

            $connectionData = $helper->fetch_table_record('modssl_api_setting', [], 'singleRowData');
        }

        $this->tplVar['decryptPassword'] = decrypt($connectionData->api_password);
        $this->tplVar['connectionData'] = $connectionData;
        $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
        $this->output();
    }
    public function productsync()
    {
        global $whmcs;
        $helper = new Helper();
        $apiCall = new ApiCall();
        $lang = $this->tplVar['lang'];

        if (isset($_SESSION["adminid"])) {

            $productSyncData = $helper->fetch_table_record('modssl_product_info', [], '');

            $where = ['id' => '1'];
            $baseUrl = $helper->fetch_table_record('modssl_api_setting', $where, 'singleValue', 'api_url');

            $allWhmProduct = $helper->fetch_table_record('tblproducts', [], '');

            $allSslProduct = $apiCall->get($baseUrl . "/ssl/products", [], "Get SSL Product List");
            // Get SSl Product List
            if (($whmcs->get_req_var("ajaxaction") == "Get SSL Product") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                // Check if HTTP code is not 200
                if ($allSslProduct['httpcode'] != '200') {
                    $helper->sendResponse(false, $allSslProduct['result']->desc);
                }

                // Check if the results are empty
                if (empty($allSslProduct['result']->data->results)) {
                    $helper->sendResponse(false, 'Product Not Available in SSL Provider.');
                }

                $html = '';
                $html .= '
                <div class="create-product-box">
                            <button type="button" data-toggle="modal" class="btn btn-success create-product">
                                ' . $lang['create_product'] . '
                            </button>
                        </div>
                <div class="product-table">
                    <form mettod="post" class="all-ssl-product">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="display:none">' . $lang['id'] . '</th>
                                    <th>' . $lang['provider'] . '</th>
                                    <th>' . $lang['product_name'] . '</th>
                                    <th style="display:none">' . $lang['product_price'] . '</th>
                                    <th>' . $lang['max_domains'] . '</th>
                                    <th>' . $lang['delivery_time'] . '</th>
                                </tr>
                            </thead>
                            <tbody>';
                foreach ($allSslProduct['result']->data->results as $product) {
                    $html .= '
                            <tr>
                                <td style="display:none"><input type="text" name="id[]" value="' . ($product->id) . '" class="form-control no-border" readonly></td>
                                <td><input type="text" name="provider[]" value="' . ($product->brand_name) . '" class="form-control no-border" readonly></td>
                                <td ><input type="text" name="product_name[]" value="' . ($product->name) . '" class="form-control no-border" readonly></td>
                                <td style="display:none"><input type="text" name="product_price[]" value="' . ($product->warranty->product->price) . ' ' . ($product->warranty->product->currency) . '" class="form-control no-border" readonly></td>
                                <td><input type="number" name="max_domains[]" value="' . ($product->max_domains) . '" class="form-control no-border" readonly></td>
                                <td><input type="text" name="delivery_time[]" value="' . ($product->delivery_time) . '" class="form-control no-border" readonly></td>
                            </tr>';
                }
                $html .= '
                            </tbody>
                        </table>
                        </form>
                </div>';

                $message = ["status" => true, "html" => $html];
                echo json_encode($message);
                exit;
            }

            if (($whmcs->get_req_var("ajaxaction") == "Create Product") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $data = html_entity_decode($whmcs->get_req_var("data"));
                parse_str($data, $dataArray);

                $finalProducts = [];

                // Assuming the arrays in $dataArray are of the same length
                foreach ($dataArray['product_name'] as $key => $productName) {
                    $finalProducts[$dataArray['id'][$key]] = [
                        // 'id' => $dataArray['id'][$i],
                        'product_name' => $productName,
                        'provider' => $dataArray['provider'][$key],
                        'product_price' => $dataArray['product_price'][$key],
                        'max_domains' => $dataArray['max_domains'][$key],
                        'delivery_time' => $dataArray['delivery_time'][$key]
                    ];
                }

                $modulesWhere = ['module' => 'openprovider_ssl', 'setting' => 'product_grp_name'];
                $addonGrp = $helper->fetch_table_record('tbladdonmodules', $modulesWhere, 'singleValue', 'value');
                $slug = $helper->createSlug($addonGrp);

                $getExistingGrp = Capsule::table('tblproductgroups')->where('name', $addonGrp)->first();
                if (!empty($getExistingGrp)) {
                    $groupId = $getExistingGrp->id;
                } else {
                    $groupId = Capsule::table('tblproductgroups')->insertGetId([
                        "name" => $addonGrp,
                        "slug" => $slug,
                        "hidden" => 0,
                        "created_at" => date("Y-m-d H:i:s", time()),
                        "updated_at" => date("Y-m-d H:i:s", time()),
                    ]);
                }

                $price = '';

                foreach ($finalProducts as $key => $product) {
                    // Check if the product already exists in the 'tblproducts' table based on configoption1
                    $existingProduct = Capsule::table('tblproducts')->where('configoption8', $key)->first();

                    $price_parts = explode(' ', $product['product_price']); // Split the string at the space
                    $price = (float)$price_parts[0]; // Monthly price

                    $payType = ($price == 0 ? 'free' : 'recurring');

                    if (!empty($existingProduct)) {
                        Capsule::table('tblproducts')->where('configoption8', $key)->update([
                            'gid' => $groupId,
                            'name' => $product['product_name'],
                            "paytype" => $payType,
                            "showdomainoptions" => 1,
                            'configoption9' => $product['provider'],
                            'configoption10' => $product['product_price'],
                            'configoption11' => $product['max_domains'],
                            'configoption12' => $product['delivery_time'],
                        ]);
                        // 'module' => 'openprovider_ssl',
                    } else {

                        $postData = array(
                            'gid' => $groupId,
                            'name' => $product['product_name'],
                            "paytype" => $payType,
                            "showdomainoptions" => 1,
                        );
                        $createProduct = localAPI('AddProduct', $postData);
                        if ($createProduct['result'] == 'error') {
                            $helper->sendResponse(false, $createProduct['message']);
                            return;
                        }

                        Capsule::table('tblproducts')->where('id', $createProduct['pid'])->update([
                            'configoption8' => $key,
                            'configoption9' => $product['provider'],
                            'configoption10' => $product['product_price'],
                            'configoption11' => $product['max_domains'],
                            'configoption12' => $product['delivery_time'],
                        ]);

                        $whmcsPid = $createProduct['pid'];
                    }

                    $pid = $whmcsPid ?? $existingProduct->id;

                    $existingGrp = Capsule::table('modssl_product_info')->where('ssl_pid', $key)->first();
                    if (empty($existingGrp)) {
                        Capsule::table('modssl_product_info')->insert([
                            "pid" => $pid,
                            "gid" => $groupId,
                            "ssl_pid" => $key,
                            "ssl_pname" => $product['product_name'],
                        ]);
                    } else {
                        Capsule::table('modssl_product_info')->where('ssl_pid', $key)->update([
                            "pid" => $pid,
                            "gid" => $groupId,
                            "ssl_pname" => $product['product_name'],
                        ]);
                    }

                    if ($price == 0) {
                        continue;
                    }

                    $setMargin = Capsule::table("tbladdonmodules")
                        ->where('module', 'openprovider_ssl')
                        ->where('setting', 'margin')
                        ->value('value');

                    $marginPrice =   $price * $setMargin / 100;
                    $price = ($marginPrice + $price);

                    $productPrices = $helper->productPrices($price);

                    $updateproductprice = $helper->updateprice($price_parts[1], $pid, 'product', $productPrices);
                }

                $helper->sendResponse(true, $lang['sync_success']);
            }
        }
       
        $this->tplVar['checkerror'] = $allSslProduct;
        $this->tplVar['allSslProduct'] = $allWhmProduct;
        $this->tplVar['productSyncData'] = $productSyncData;
        $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
        $this->output();
    }
    public function logs()
    {
        global $whmcs;
        $helper = new Helper();
        $apiCall = new ApiCall();
        $lang = $this->tplVar['lang'];
        $logData = Capsule::table("modssl_logs")
            ->get();

        if (($whmcs->get_req_var("ajaxaction") == "clientLogs") && ($whmcs->get_req_var("ajaxcall") == "true")) {
            $data = $helper->clientListLogs($_POST);
            echo $data;
            exit;
        }
        if (($whmcs->get_req_var("ajaxaction") == "Delete logs") && ($whmcs->get_req_var("ajaxcall") == "true")) {

            $delete = Capsule::table("modssl_logs")
                ->delete();

            if ($delete) {
                $helper->sendResponse(true, 'Logs Deleted Successfully!');
            } else {
                $helper->sendResponse(false, 'No logs found to delete.');
            }
        }

        $this->tplVar['logData'] = $logData;
        $this->tplFileName = $this->tplVar['tab'] = __FUNCTION__;
        $this->output();
    }
    public function output($data = null)
    {
        $this->tplVar['data'] = $data;
        $this->smarty = new Smarty();
        $this->smarty->assign('tplVar', $this->tplVar);
        if (!empty($this->tplFileName)) {
            $this->smarty->display($this->tplVar['tplDIR'] . $this->tplFileName . '.tpl');
        } else {
            $this->tplVar['errorMsg'] = 'not found';
            $this->smarty->display($this->tplDIR . 'error.tpl');
        }
    }
}

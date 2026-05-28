<?php
require_once __DIR__ . '/../../servers/openprovider_ssl/classes/ApiCall.php';

use WHMCS\Module\Server\OpenproviderSsl\Helper;
use Module\OpenproviderSsl\Server\classes\ApiCall;
use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
function openprovider_ssl_MetaData()
{
    return array(
        'DisplayName' => 'Openprovider SSL',
        'APIVersion' => '2.0',
        'RequiresServer' => true,
    );
}
function openprovider_ssl_TestConnection(array $params)
{
    try {
        $apiCall = new ApiCall();

        $postData = [
            'username' => $params["serverusername"],
            'password' => $params["serverpassword"],
        ];
        $url = 'https://' . $params['serverhostname'] . '/v1beta/auth/login';

        $testConnection = $apiCall->post($url, $postData, "Open Ssl Test Connection");

        if ($testConnection['httpcode'] != 200) {
            $errorMsg = $testConnection['result']->desc;
            return array(
                'success' => false,
                'error' => $errorMsg,
            );
        }

        $gettoken = $testConnection['result']->data->token;

        $result = Capsule::table('tblservers')->where('hostname', $params['serverhostname'])->where('type', 'openprovider_ssl')->update([
            "accesshash" => $gettoken
        ]);

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_ssl',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

function openprovider_ssl_ConfigOptions(array $params)
{
    try {
        global $CONFIG;
        global $whmcs;
        $helper = new Helper();
        $apiCall = new ApiCall();

        $countries = new WHMCS\Utility\Country();
        $countries = $countries->getCountryNameArray();

        $language = $CONFIG['Language'];
        $langfilename = __DIR__ . '/lang/' . $language . '.php';
        if (file_exists($langfilename)) {
            require($langfilename);
        } else {
            require(__DIR__ . '/lang/english.php');
        }
        $LANG = $_ADDONLANG;

        $pid = $whmcs->get_req_var("id");

        $customfieldarray = [
            'approver_email' =>
            [
                'type' => 'product',
                'fieldname' => 'approver_email|Approver Email',
                'relid' => $pid,
                'fieldtype' => 'dropdown',
                'description' => '',
                'adminonly' => '',
                'required' => 'on',
                'showorder' => 'on',
                'sortorder' => '0',
                'fieldoptions' => 'admin,administrator,hostmaster,postmaster,webmaster',
            ],
            'csr' =>
            [
                'type' => 'product',
                'fieldname' => 'csr|CSR',
                'relid' => $pid,
                'fieldtype' => 'textarea',
                'description' => '',
                'adminonly' => '',
                'required' => 'on',
                'showorder' => 'on',
                'sortorder' => '1',
            ],
            'ssl_id' =>
            [
                'type' => 'product',
                'fieldname' => 'ssl_id|SSL Id',
                'relid' => $pid,
                'fieldtype' => 'text',
                'description' => '',
                'adminonly' => 'on',
                'required' => '',
                'showorder' => '',
                'sortorder' => '',
            ],
            'organization_handle' =>
            [
                'type' => 'product',
                'fieldname' => 'organization_handle|Organization Handle',
                'relid' => $pid,
                'fieldtype' => 'text',
                'description' => '',
                'adminonly' => 'on',
                'required' => '',
                'showorder' => '',
                'sortorder' => '',
            ],
            'organization_handle' =>
            [
                'type' => 'client',
                'fieldtype' => 'text',
                'relid' => '0',
                'fieldname' => 'organization_handle|Organization Handle',
                'description' => 'use carefully',
                'adminonly' => 'on',
                'required' => '',
                'showorder' => '',
                'showinvoice' => '',
            ],
        ];
        // 'technical_handle' =>
        // [
        //     'type' => 'product',
        //     'fieldname' => 'technical_handle|Technical Handle',
        //     'relid' => $pid,
        //     'fieldtype' => 'text',
        //     'description' => '',
        //     'adminonly' => 'on',
        //     'required' => '',
        //     'showorder' => '',
        //     'sortorder' => '',
        // ],
        $helper->createCustomFields($customfieldarray);
        // $helper->createResellerEmailTemplates();
        $helper->createConfigurableOptions($pid);

        $baseUrl = $helper->getBaseUrl();
        $allSslProduct = $apiCall->get($baseUrl . '/ssl/products', [], "Get SSL Product List");

        $script = "";

        $script .= '<script>
                        $(document).ready(function() {
                            $("textarea[name=\'packageconfigoption[6]\']").after(`
                                <div class="admin-copy-csr">
                                    <div class="copy-btn">
                                        <i class="fad fa-copy" aria-hidden="true"></i>
                                        <span id="myTooltip" class="tooltip">Copy CSR Token</span>
                                    </div>
                                </div>
                            `);
                        });
                    </script>';

        if (empty($allSslProduct['result']) || $allSslProduct['httpcode'] != 200) {
            $errorMessage = $allSslProduct['result']->desc ?? "Products Not Available.";
            $script .= "
                            <script>
                                $(document).ready(function() {
                                    $('#frmProductEdit .tab-content #tab3 #divModuleSettings').before(`
                                        <div class='alert alert-danger' role='alert' id='reseller_error'>
                                            " . ($errorMessage) . "
                                        </div>
                                    `);
                                });
                            </script>
                        ";

            return array(
                'Products' => array(
                    'Type' => 'dropdown',
                    'Options' => empty($options) ? [] : $options,
                    'Description' => 'Choose one',
                ),
                'Auto Renew' => array(
                    'Type' => 'yesno',
                    'Description' => 'Tick to enable' . $script,
                ),
                'Enable Dns Automation' => array(
                    'Type' => 'yesno',
                    'Description' => 'Tick to enable',
                ),
                'Signature Hash Algorithm' => array(
                    'Type' => 'dropdown',
                    'Options' => array(
                        'sha2' => 'SHA-2',
                        'sha1' => 'SHA-1',
                    ),
                    'Description' => 'Choose one',
                ),
                'Period' => array(
                    'Type' => 'dropdown',
                    'Options' => array(
                        '1' => '1 Year',
                        '2' => '2 year',
                    ),
                    'Description' => 'Choose one',
                ),
                'CSR' => array(
                    'Type' => 'textarea',
                    'Rows' => '5',
                    'Cols' => '60',
                    'Description' => '<a href="#" data-toggle="modal" id="admin-csr-btn" class="btn-csr"
                                    data-target="#create_csr" data-type="create_csr">
                                    Create CSR
                                    </a>',
                ),
            );
        }

        $options = [];
        foreach ($allSslProduct['result']->data->results as $i => $product) {
            // $label = $product->name . ' price ' . $product->warranty->product->price.' '. $product->warranty->product->currency;
            $options[$product->id] = $product->name;
        }

        return array(
            'Products' => array(
                'Type' => 'dropdown',
                'Options' => empty($options) ? [] : $options,
                'Description' => 'Choose one',
            ),
            'Auto Renew' => array(
                'Type' => 'yesno',
                'Description' => 'Tick to enable',
            ),
            'Enable Dns Automation' => array(
                'Type' => 'yesno',
                'Description' => 'Tick to enable',
            ),
            'Signature Hash Algorithm' => array(
                'Type' => 'dropdown',
                'Options' => array(
                    'sha2' => 'SHA-2',
                    'sha1' => 'SHA-1',
                ),
                'Description' => 'Choose one' . $script,
            ),
            'Period' => array(
                'Type' => 'dropdown',
                'Options' => array(
                    '1' => '1 Year',
                    '2' => '2 year',
                ),
                'Description' => 'Choose one',
            ),
            'CSR' => array(
                'Type' => 'textarea',
                'Rows' => '5',
                'Cols' => '60',
                'Description' => '<a href="#" data-toggle="modal" id="admin-csr-btn" class="btn-csr"
                                    data-target="#create_csr" data-type="create_csr">
                                    Create CSR
                                    </a>',
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'Openprovider SSL',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
}

function openprovider_ssl_CreateAccount(array $params)
{
    try {
        global $CONFIG;
        $helper = new Helper();
        $apiCall = new ApiCall();
        $serviceId = $params['serviceid'];
        $pid = $params['pid'];
        logModuleCall('Test 1', 'test I am here', 'test I am here', null, null, null);
        $handle = $helper->getClientCustomField($params['userid']);
        logModuleCall('Test 2', 'Get Client Custom Field', $params['userid'], $handle, null, null);

        $orgnizationHandle = "";

        $baseUrl = $helper->getBaseUrl();

        if (empty($handle)) {

            $createContact =  [
                "address" => [
                    "city" => $params['clientsdetails']['city'],
                    "country" => $params['clientsdetails']['country'],
                    "number" => $params['clientsdetails']['address2'],
                    "state" => $params['clientsdetails']['state'],
                    "street" => $params['clientsdetails']['address1'],
                    "zipcode" => $params['clientsdetails']['postcode']
                ],
                "company_name" => $params['clientsdetails']['companyname'],
                "email" => $params['clientsdetails']['email'],
                "name" => [
                    "first_name" =>  $params['clientsdetails']['firstname'],
                    "full_name" =>  $params['clientsdetails']['fullname'],
                    "last_name" =>  $params['clientsdetails']['lastname'],
                ],
                "phone" => [
                    "area_code" =>  "72",
                    "country_code" =>  "+" . $params['clientsdetails']['phonecc'],
                    "subscriber_number" =>  $params['clientsdetails']['phonenumber']
                ],
            ];

            $createCustomer = $apiCall->post($baseUrl . '/customers', $createContact, "Create Customer");

            if ($createCustomer['httpcode'] != 200) {
                return $createCustomer['result']->desc;
            }
            $orgnizationHandle = $createCustomer['result']->data->handle;

            $fieldId = $helper->getUniqueKeyFieldId();
            Capsule::table('tblcustomfieldsvalues')
                ->updateOrInsert(
                    ['fieldid' => $fieldId, 'relid' => $params['userid']],
                    ['value' => $orgnizationHandle]
                );
        } else {
            $orgnizationHandle = $handle;
        }

        $postData = [
            "approver_email" => $params['customfields']['approver_email'] . '@' . $params['domain'],
            "autorenew" => ($params['configoption2'] == 'on') ? 'on' : 'off',
            "csr" => $params['customfields']['csr'],
            "domain_amount" => $params['configoptions']['no_of_domain'],
            "domain_validation_methods" => [
                [
                    "host_name" => $params['domain'],
                    "method" => "email"
                ],
            ],
            "host_names" => [
                $params['domain'],
            ],
            "organization_handle" => $orgnizationHandle,
            "enable_dns_automation" => ($params['configoption3'] == 'on') ? true : false,
            "start_provision" => true,
            "software_id" => "linux",
            "period" => (int) $params['configoption5'],
            "product_id" => (int) $params['configoption1'],
            "signature_hash_algorithm" => $params['configoption4'],
        ];
        logModuleCall('Openprovider SSL test', __FUNCTION__, $postData, null, null, null);
        $createOrder = [];
        // $createOrder = $apiCall->post($baseUrl . '/ssl/orders', $postData, "Create order");

        if ($createOrder['httpcode'] != 200) {
            return $createOrder['result']->desc;
        }

        $fields = ["ssl_id" => $createOrder['result']->data->id, "organization_handle" => $orgnizationHandle];
        $helper->insert_custom_fields_value($serviceId, $pid, $fields);

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $createOrder['result']);
        return "success";
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function openprovider_ssl_SuspendAccount(array $params)
{
    try {
        return "success";
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function openprovider_ssl_UnsuspendAccount(array $params)
{
    try {
        return "success";
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function openprovider_ssl_TerminateAccount(array $params)
{
    try {
        $helper = new Helper();
        $apiCall = new ApiCall();

        $postData = [
            "id" => $params['customfields']['ssl_id'],
        ];

        $baseUrl = $helper->getBaseUrl();
        $cancleOrder = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/cancel', $postData, "Cancle order");

        if ($cancleOrder['httpcode'] != 200) {
            return $cancleOrder['result']->desc;
        }

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $cancleOrder['result']);
        return "success";
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

// function openprovider_ssl_ChangePackage(array $params)
// {
//     try {
//         $helper = new Helper();
//         $apiCall = new ApiCall();

//         // $postData = [
//         //     "approver_email" => $params['customfields']['approver_email'] . '@' . $params['domain'],
//         //     "autorenew" => ($params['configoption2'] == 'on') ? 'on' : 'off',
//         //     "csr" => $params['customfields']['csr'],
//         //     // "domain_amount" => $params['configoptions']['no_of_domain'],
//         //     "domain_validation_methods" => [
//         //         [
//         //             "host_name" => $params['domain'],
//         //             "method" => "email"
//         //         ],
//         //     ],
//         //     "enable_dns_automation" => ($params['configoption3'] == 'on') ? true : false,
//         //     "id" => (int) $params['customfields']['ssl_id'],
//         //     "organization_handle" => $params['customfields']['organization_handle'],
//         //     "signature_hash_algorithm" => $params['configoption4'],
// "start_provision" => true,
// "software_id" => "linux",
// "technical_handle" => $params['customfields']['technical_handle'],
//         // ];

//         // $baseUrl = $helper->getBaseUrl();
//         // $upgradeServer = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'], $postData, "Upgrade Service");

//         // if ($upgradeServer['httpcode'] != 200) {
//         //     return $upgradeServer['result']->desc;
//         // }

//         // logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $upgradeServer['result']);
//         // return "success";
//     } catch (Exception $e) {
//         logModuleCall('Openprovider SSL Upgrades ', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
//         return $e->getMessage();
//     }
// }

function openprovider_ssl_AdminServicesTabFields(array $params)
{
    try {
        global $CONFIG;
        global $whmcs;
        $helper = new Helper();
        $apiCall = new ApiCall();

        $language = $CONFIG['Language'];
        $langfilename = __DIR__ . '/lang/' . $language . '.php';
        if (file_exists($langfilename)) {
            require($langfilename);
        } else {
            require(__DIR__ . '/lang/english.php');
        }
        $LANG = $_ADDONLANG;

        $baseUrl = $helper->getBaseUrl();
        $getOrder = $apiCall->get($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'], [], "Get order Detail");

        if ($getOrder['httpcode'] != 200) {
            $htmlArray = $helper->serviceAdminError($getOrder['result']->desc);
            return $htmlArray;
        }

        if (($whmcs->get_req_var("ajaxaction") == "Change Domain Validation") && ($whmcs->get_req_var("ajaxcall") == "true")) {

            if (empty($whmcs->get_req_var("method"))) {
                $helper->sendResponse(false, "Method is required");
            }
            $postData = [
                "domain_validation_methods" => [
                    [
                        "host_name" => $params['domain'],
                        "method" => $whmcs->get_req_var("method")
                    ],
                ],
                "id" => (int)$params['customfields']['ssl_id'],
            ];

            $baseUrl = $helper->getBaseUrl();
            $resendApproval = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'], $postData, "Change Validation Method");

            if ($resendApproval['httpcode'] != 200) {
                $helper->sendResponse(false, $resendApproval['result']->desc);
            }
            $helper->sendResponse(true, "Change Method Successffly!");
        }

        if (($whmcs->get_req_var("ajaxaction") == "Update Email") && ($whmcs->get_req_var("ajaxcall") == "true")) {

            if (empty($whmcs->get_req_var("emailval"))) {
                $helper->sendResponse(false, "Email Approver is required");
            }

            $postData = [
                "approver_email" => $whmcs->get_req_var("emailval"),
                "id" => (int)$params['customfields']['ssl_id'],
            ];

            $baseUrl = $helper->getBaseUrl();
            $updateEmail = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/approver-email', $postData, "Update Approval Email");

            if ($updateEmail['httpcode'] != 200) {
                $helper->sendResponse(false, $updateEmail['result']->desc);
            }

            $helper->sendResponse(true, "Update Email Successffly!");
        }

        $getEmail = $apiCall->get($baseUrl . '/ssl/approver-emails?product_id=' . $getOrder['result']->data->product_id . '&domain=' . $params['domain'], [], "Get Approver email");

        if ($getEmail['httpcode'] != 200) {
            $htmlArray = $helper->serviceAdminError($getEmail['result']->desc);
            return $htmlArray;
        }

        $orderInfo = $helper->orderInfo($getOrder['result']->data, $LANG);
        $fieldArray = [
            '' => $helper->adminButton($LANG),
            ' ' => $orderInfo,
            '  ' => $helper->changeValidationMethodModal($getEmail['result']->data->results, $getOrder['result']->data, $LANG),
            '   ' => $helper->changeApproverEmailModal($getEmail['result']->data->results, $getOrder['result']->data->email_approver, $LANG),
        ];

        if (!empty($params['customfields']['ssl_id'])) {
            return $fieldArray;
        }
    } catch (Exception $e) {
        logModuleCall('soyoustart', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
    }

    return array();
}

function openprovider_ssl_Renew(array $params)
{
    try {
        $helper = new Helper();
        $apiCall = new ApiCall();

        $postData = [
            "id" => $params['customfields']['ssl_id'],
        ];

        $baseUrl = $helper->getBaseUrl();
        $renewOrder = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/renew', $postData, "Renew order");

        if ($renewOrder['httpcode'] != 200) {
            return $renewOrder['result']->desc;
        }

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $renewOrder['result']);
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'openprovider_ssl ',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}
function openprovider_ssl_AdminCustomButtonArray()
{
    return array(
        "Resend confirmation email" => "resend_approval",
        "Reissue" => "reissue",
        // "Update Email" => "update_email",
    );
}

function openprovider_ssl_update_email(array $params)
{
    try {
        $helper = new Helper();
        $apiCall = new ApiCall();

        $postData = [
            "approver_email" => $params['customfields']['approver_email'] . '@' . $params['domain'],
            "id" => $params['customfields']['ssl_id'],
        ];

        $baseUrl = $helper->getBaseUrl();
        $updateEmail = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/approver-email', $postData, "Update Approval Email");

        if ($updateEmail['httpcode'] != 200) {
            return $updateEmail['result']->desc;
        }

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $updateEmail['result']);
        return 'success';
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function openprovider_ssl_reissue(array $params)
{
    try {
        $helper = new Helper();
        $apiCall = new ApiCall();

        // "csr" => $params['customfields']['csr'],$params['configoption6']
        $postData = [
            "approver_email" => $params['customfields']['approver_email'] . '@' . $params['domain'],
            "csr" => $params['customfields']['csr'],
            "domain_validation_methods" => [
                [
                    "host_name" => $params['domain'],
                    "method" => "email"
                ],
            ],
            "host_names" => [
                $params['domain']
            ],
            "enable_dns_automation" => ($params['configoption3'] == 'on') ? true : false,
            "id" => (int)$params['customfields']['ssl_id'],
            "organization_handle" => $params['customfields']['organization_handle'],
            "signature_hash_algorithm" => $params['configoption4'],
            "software_id" => "linux",
        ];

        $baseUrl = $helper->getBaseUrl();
        $reissueOrder = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/reissue', $postData, "Reissue Order");

        if ($reissueOrder['httpcode'] != 200) {
            return $reissueOrder['result']->desc;
        }

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $reissueOrder['result']);
        return 'success';
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}
function openprovider_ssl_resend_approval(array $params)
{
    try {
        $helper = new Helper();
        $apiCall = new ApiCall();

        $postData = [
            "id" => (int)$params['customfields']['ssl_id'],
        ];

        $baseUrl = $helper->getBaseUrl();
        $resendApproval = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/approver-email/resend', $postData, "Resend Approver Email");

        if ($resendApproval['httpcode'] != 200) {
            return $resendApproval['result']->desc;
        }

        logModuleCall('Openprovider SSL', __FUNCTION__, $postData, $resendApproval['result']);
        return 'success';
    } catch (Exception $e) {
        logModuleCall('Openprovider SSL', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }
}

function openprovider_ssl_ClientArea(array $params)
{
    try {
        global $CONFIG;
        global $whmcs;
        $helper = new Helper();
        $apiCall = new ApiCall();
        /* adding lang file according to whmcs default language */
        $assets = $CONFIG['SystemURL'] . "/modules/servers/openprovider_ssl/assets";
        $language = $CONFIG['Language'];
        /* adding lang file according to whmcs default language */
        $language = $CONFIG['Language'];
        $langfilename = __DIR__ . '/lang/' . $language . '.php';
        if (file_exists($langfilename)) {
            require($langfilename);
        } else {
            require(__DIR__ . '/lang/english.php');
        }
        $error = '';

        $baseUrl = $helper->getBaseUrl();
        $getOrder = $apiCall->get($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'], [], "Get order Detail");

        if ($getOrder['httpcode'] == 200) {
            $orderInfo = $helper->orderInfo($getOrder['result']->data, $_ADDONLANG);

            $getEmail = $apiCall->get($baseUrl . '/ssl/approver-emails?product_id=' . $getOrder['result']->data->product_id . '&domain=' . $params['domain'], [], "Get Approver email");

            if ($getEmail['httpcode'] != 200) {
                $error = $getEmail['result']->desc;
            }

            if (($whmcs->get_req_var("ajaxaction") == "Resend Approval Email") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                $postData = [
                    "id" => (int)$params['customfields']['ssl_id'],
                ];

                $baseUrl = $helper->getBaseUrl();
                $resendApproval = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/approver-email/resend', $postData, "Resend Approver Email");

                if ($resendApproval['httpcode'] != 200) {
                    $helper->sendResponse(false, $resendApproval['result']->desc);
                }
                $helper->sendResponse(true, "Resend Approval Successffly!");
            }
            if (($whmcs->get_req_var("ajaxaction") == "Change Domain Validation") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                if (empty($whmcs->get_req_var("method"))) {
                    $helper->sendResponse(false, "Method is required");
                }
                $postData = [
                    "domain_validation_methods" => [
                        [
                            "host_name" => $params['domain'],
                            "method" => $whmcs->get_req_var("method")
                        ],
                    ],
                    "id" => (int)$params['customfields']['ssl_id'],
                ];

                $baseUrl = $helper->getBaseUrl();
                $resendApproval = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'], $postData, "Change Validation Method");

                if ($resendApproval['httpcode'] != 200) {
                    $helper->sendResponse(false, $resendApproval['result']->desc);
                }
                $helper->sendResponse(true, "Change Method Successffly!");
            }

            // if (($whmcs->get_req_var("ajaxaction") == "Reissue SSL Order") && ($whmcs->get_req_var("ajaxcall") == "true")) {

            //     $postData = [
            //         "approver_email" => $params['customfields']['approver_email'] . '@' . $params['domain'],
            //         "csr" => $params['customfields']['csr'],
            //         "domain_validation_methods" => [
            //             [
            //                 "host_name" => $params['domain'],
            //                 "method" => "email"
            //             ],
            //         ],
            //         "enable_dns_automation" => ($params['configoption3'] == 'on') ? true : false,
            //         "id" => $params['customfields']['ssl_id'],
            //         "signature_hash_algorithm" => $params['configoption4'],
            //     ];

            //     $baseUrl = $helper->getBaseUrl();
            //     $reissueOrder = $apiCall->post($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/reissue', $postData, "Reissue Order Clientarea");

            //     if ($reissueOrder['httpcode'] != 200) {
            //         $helper->sendResponse(false, $reissueOrder['result']->desc);
            //     }
            //     $helper->sendResponse(true, "Reissue Order Successffly!");
            // }

            if (($whmcs->get_req_var("ajaxaction") == "Update Email") && ($whmcs->get_req_var("ajaxcall") == "true")) {

                if (empty($whmcs->get_req_var("emailval"))) {
                    $helper->sendResponse(false, "Email Approver is required");
                }

                $postData = [
                    "approver_email" => $whmcs->get_req_var("emailval"),
                    "id" => (int)$params['customfields']['ssl_id'],
                ];

                $baseUrl = $helper->getBaseUrl();
                $updateEmail = $apiCall->put($baseUrl . '/ssl/orders/' . $params['customfields']['ssl_id'] . '/approver-email', $postData, "Update Approval Email");

                if ($updateEmail['httpcode'] != 200) {
                    $helper->sendResponse(false, $updateEmail['result']->desc);
                }

                // $email_parts = explode("@", $whmcs->get_req_var("emailval"));
                // $fields = ["approver_email" => $email_parts[0]];
                // $helper->insert_custom_fields_value($params['serviceid'], $params['pid'], $fields);

                $helper->sendResponse(true, "Update Email Successffly!");
            }

            $updateAprovalEmail = $helper->changeApproverEmailModal($getEmail['result']->data->results, $getOrder['result']->data->email_approver, $_ADDONLANG);
            $updateValidationMethod = $helper->changeValidationMethodModal($getEmail['result']->data->results, $getOrder['result']->data, $_ADDONLANG);

            return array(
                "templatefile" => 'templates/clientarea.tpl',
                'templateVariables' => array(
                    'error' => $error,
                    'updateValidationMethod' => $updateValidationMethod,
                    'LANG' => $_ADDONLANG,
                    'assets' => $assets,
                    'orderInfo' => $orderInfo,
                    'aprovalEmailModal' => $updateAprovalEmail,
                    // 'allEmail' => $getEmail['result']->data->results,
                    // 'aprovalEmail' => $getOrder['result']->data->email_approver,
                )
            );
        } else {
            return array(
                'templatefile' => 'templates/error.tpl',
                'templateVariables' => array(
                    'usefulErrorHelper' => $getOrder['result']->desc,
                ),
            );
        }
    } catch (Exception $e) {
        logModuleCall(basename(__FILE__, '.php'), __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return array(
            'templatefile' => 'templates/error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}

<?php
require_once __DIR__ . '/classes/ApiCall.php';
require_once __DIR__ . '/classes/HandleCSRCreation.php';

use WHMCS\Database\Capsule;
use WHMCS\Module\Server\OpenproviderSsl\Helper;
use Module\OpenproviderSsl\Server\classes\ApiCall;
use Module\OpenproviderSsl\Server\classes\HandleCSRCreation;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    try {
        if ($vars['productinfo']['module'] == 'openprovider_ssl') {
            $clientAreaHelper = new HandleCSRCreation();

            if ($vars['templatefile'] == 'configureproduct') {
                return $clientAreaHelper->handleCSRCreation($vars);
            }
        }
    } catch (\Exception $e) {
        logActivity("Error Occur Openprovider SSL ClientAreaHeadOutput" . $e->getMessage());
    }
});

// add_hook('ClientAreaHeadOutput', 1, function ($vars) {
//     try {
//         if ($vars['productinfo']['module'] == 'openprovider_ssl') {
//             global $CONFIG, $whmcs, $LANG;

//             $helper = new Helper();
//             $apiCall = new ApiCall();

// $language = $CONFIG['Language'];
// $langfilename = __DIR__ . '/lang/' . $language . '.php';
// if (file_exists($langfilename)) {
//     require($langfilename);
// } else {
//     require(__DIR__ . '/lang/english.php');
// }
// $LANG = $_ADDONLANG;


//             if ($vars['templatefile'] == 'configureproduct') {
//                 $csrCustomFieldId = $helper->getCustomField($vars['customfields']);

//                 if (($whmcs->get_req_var("ajaxaction") == "create token") && ($whmcs->get_req_var("ajaxcall") == "true")) {
//                     $data = html_entity_decode($whmcs->get_req_var("data"));
//                     parse_str($data, $dataArray);

//                     $postData = [
//                         "bits" => (int) 2048,
//                         "common_name" => $vars['domain'],
//                         "country" => $dataArray['country'],
//                         "email" => $dataArray['email'],
//                         "locality" => $dataArray['locality'],
//                         "organization" => $dataArray['organization'],
//                         "signature_hash_algorithm" => $dataArray['signature_hash_algorithm'],
//                         "state" => $dataArray['state'],
//                         "unit" => $dataArray['unit'],
//                         "with_config" => (bool)$dataArray['with_config']
//                     ];
//                     $baseUrl = $helper->getBaseUrl();
//                     // "subject_alternative_name" => [
//                     //     $dataArray['subject_alternative_name']
//                     // ],
//                     $createCSRToken = $apiCall->post($baseUrl . '/ssl/csr', $postData, "Create CSR Token");

//                     if ($createCSRToken['httpcode'] != '200' && empty($createCSRToken['result']->data->csr)) {
//                         $helper->sendResponse(false, "Something Went Wrong!");
//                     }

//                     $message = ["status" => true, 'message' => 'CSR Token Created Successfully!', "data" => $createCSRToken['result']->data, 'fieldId' => $csrCustomFieldId];
//                     echo json_encode($message);
//                     exit;
//                 }

//                 $countries = new WHMCS\Utility\Country();
//                 $countries = $countries->getCountryNameArray();

//                 $html = "";
//                 $html .= '
//             <script src="' . $CONFIG["SystemURL"] . '/assets/js/StatesDropdown.js"></script>
//             <script src="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/js/jquerygrowl.js" type="text/javascript"></script>
//             <link href="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/css/jquerygrowl.css" rel="stylesheet" type="text/css" />
//             <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
//             <link rel="stylesheet" type="text/css" href="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/css/style.css">
//             <script type="text/javascript" src="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/js/script.js"></script>';
//                 $html .= '<script>
//                         $(document).ready(function() {
//                             var id = "#customfield' . $csrCustomFieldId . '";
//                             $(id).closest(".form-group").after(`
//                                 <a href="#" data-toggle="modal" class="btn-csr"
//                                 data-target="#create_csr" data-type="create_csr">
//                                 Create CSR
//                                 </a>
//                             `);
//                             $(id).after(`
//                                         <div class="copy-btns">
//                                             <i class="fad fa-copy" aria-hidden="true"></i>
//                                             <span id="myTooltip" class="tooltip">'.$LANG['copy_csr'].'</span>
//                                         </div>
//                                     `);
//                             $(document).on("click", ".copy-btns", function () {
//                                 copyTexts();
//                             });
//                             function copyTexts() {
//                                 var copyText = $(id);
//                                 copyText.focus();
//                                 copyText.select();
//                                 document.execCommand("copy");
//                                 var tooltip = $("#myTooltip");
//                                 tooltip.text("Copied");
//                             }
//                         });
//                     </script>';
//                 $html .= $helper->createCsrTokenHtml($countries, $LANG);
//                 return $html;
//             }
//         }
//     } catch (\Exception $e) {
//         logActivity("Error Occur Openprovider SSL ClientAreaHeadOutput" . $e->getMessage());
//     }
// });

add_hook('AdminAreaHeadOutput', 1, function ($vars) {

    try {
        if (isset($vars['addon_modules']['openprovider_ssl'])) {
            global $CONFIG;
            global $whmcs;
            global $LANG;
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


            if ($vars['filename'] == 'configproducts') {

                if (($whmcs->get_req_var("ajaxaction") == "create token") && ($whmcs->get_req_var("ajaxcall") == "true")) {
                    $data = html_entity_decode($whmcs->get_req_var("data"));
                    parse_str($data, $dataArray);

                    $postData = [
                        "bits" => (int) 4098,
                        "common_name" => $dataArray['common_name'],
                        "country" => $dataArray['country'],
                        "email" => $dataArray['email'],
                        "locality" => $dataArray['locality'],
                        "organization" => $dataArray['organization'],
                        "signature_hash_algorithm" => $dataArray['signature_hash_algorithm'],
                        "state" => $dataArray['state'],
                        "unit" => $dataArray['unit'],
                        "with_config" => (bool)$dataArray['with_config'],
                        "subject_alternative_name" => [
                            "www.".$dataArray['common_name'],
                        ],
                    ];
                    $baseUrl = $helper->getBaseUrl();
                    $createCSRToken = $apiCall->post($baseUrl . '/ssl/csr', $postData, "Create CSR Token");

                    if ($createCSRToken['httpcode'] != '200' && empty($createCSRToken['result']->data->csr)) {
                        $helper->sendResponse(false, "Something Went Wrong!");
                    }

                    $message = ["status" => true, 'message' => 'CSR Token Created Successfully!', "data" => $createCSRToken['result']->data, 'fieldId' => '', 'adminId' => 'textarea[name=\'packageconfigoption[6]\']'];
                    echo json_encode($message);
                    exit;
                }

                $countries = new WHMCS\Utility\Country();
                $countries = $countries->getCountryNameArray();


                $html = "";
                $html .= '
            <script src="' . $CONFIG["SystemURL"] . '/assets/js/StatesDropdown.js"></script>
            <script src="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/js/jquerygrowl.js" type="text/javascript"></script>
            <link href="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/css/jquerygrowl.css" rel="stylesheet" type="text/css" />
            <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
            <link rel="stylesheet" type="text/css" href="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/css/style.css">
            <script type="text/javascript" src="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/js/script.js"></script>';

                
                $html .= $helper->createCsrTokenHtml($countries, $LANG, 'yes');
                return $html;
            }
        }
    } catch (\Exception $e) {
        logActivity("Error Occur Openprovider SSL AdminAreaHeadOutput" . $e->getMessage());
    }
});


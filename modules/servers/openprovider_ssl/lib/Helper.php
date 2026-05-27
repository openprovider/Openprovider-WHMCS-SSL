<?php

namespace WHMCS\Module\Server\OpenproviderSsl;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

class Helper
{
    public function fetch_table_record($tableName, $conditions, $for, $columnValue = null, $order = null, $limit = null)
    {
        try {
            $query = Capsule::table($tableName);
            foreach ($conditions as $column => $value) {
                $query->where($column, $value);
            }
            if ($for == 'groupBy') {
                $query->groupBy(key($conditions));
            } elseif ($for == 'singleRowData') {
                return $query->first();
            } elseif ($for == 'countData') {
                return $query->count();
            } elseif ($for == 'deleteRow') {
                return $query->delete();
            } elseif ($for == 'singleValue') {
                return $query->value($columnValue);
            }
            if ($order) {
                $query->orderBy($order['column'], $order['direction']);
            }
            if ($limit) {
                $query->limit($limit);
            }

            return $query->get();
        } catch (\Exception $e) {
            return [
                'status' => "error",
                'description' => 'systembot wgs_fetch_table_record_systembot function: ' . $e->getMessage(),
            ];
        }
    }
    /* 
        @param $table_name = table name.
        @param $where = where condition in associative array if any.
        @param $data = data to insert/update in associative array.

    */
    public function insertUpdate($table_name = '', $where = [], $data = null)
    {
        try {
            $row = Capsule::table($table_name)->where($where)->first();
            if (is_null($row)) {
                Capsule::table($table_name)->insertGetId($data);
                return "Data has been Saved successfully!";
            } else {
                Capsule::table($table_name)->where($where)->update($data);
                return "Data has been updated successfully!";
            }
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        }
    }

    public function updateOrCreateGetId($table_name = '', $where = [], $data = null)
    {
        try {
            $row = Capsule::table($table_name)->where($where)->first();
            if (is_null($row)) {
                $productId = Capsule::table($table_name)->insertGetId($data);
                return $productId;
            } else {
                Capsule::table($table_name)->where($where)->update($data);
                return $where["id"];
            }
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in inserting/updating data: ' . $e->getMessage());
        }
    }

    function sendResponse($status, $message)
    {
        $response = [
            'status' => $status,
            'message' => $message,
        ];
        echo json_encode($response);
        exit;
    }

    public function getBaseUrl()
    {
        try {
            $url = $this->fetch_table_record('tblservers', ['type' => 'openprovider_ssl'], 'singleValue', 'hostname');
            $baseUrl = 'https://' . $url . '/v1beta';
            return $baseUrl;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }
    public function getCustomField($customfield)
    {
        try {
            foreach ($customfield as $value) {
                if ($value['textid'] == 'csr') {
                    $id = $value['id'];
                }
            }

            return $id;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }
    public function getUniqueKeyFieldId()
    {
        return Capsule::table('tblcustomfields')
            ->where('type', 'client')
            ->where('relid', '0')
            ->where('fieldname', 'like', '%organization_handle%')
            ->value('id');
    }

    public function getClientCustomField($userid)
    {
        try {
            $fieldId = $this->getUniqueKeyFieldId();

            $customFieldHandle = Capsule::table('tblcustomfieldsvalues')
                ->where('fieldid', $fieldId)
                ->where('relid', $userid)
                ->value('value');

            return $customFieldHandle;
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    public function serviceAdminError($msg)
    {
        $html = '';
        $html .= '<div class="order-info">' . $msg . '</div>';
        $htmlArray = [
            ' ' => $html,
        ];
        return $htmlArray;
    }
    public function changeApproverEmailModal($approverEmail, $selectedEmail, $LANG)
    {
        $html = '';
        $html .= '<div class="modal whmcs-modal fade" id="update_email" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content panel panel-primary">
                            <div class="modal-header panel-heading" id="reinstall-vm">
                                <h4 class="modal-title" id="modalAjaxTitle">' . $LANG["email_heading"] . '</h4>
                                <button id="modalAjaxCloseSmall" type="button" class="close" data-dismiss="modal">
                                    <span>×</span>
                                    <span class="sr-only">' . $LANG["close"] . '</span>
                                </button>
                            </div>
                            <div class="modal-body panel-body" id="update_email_body">
                                <form method="post" class="update_email">
                                    <div class="update_email_data">
                                        <label for="emailval">' . $LANG["select_email"] . '</label>
                                        <select id="emailval" class="form-control">
                                            <option value="">' . $LANG["select_email"] . '</option>';
        foreach ($approverEmail as $value) {
            $html .= '<option value="' . $value . '" ' . ($value == $selectedEmail ? 'selected' : '') . '>' . $value . '</option>';
        }
        $html .= '</select>
                                    </div>
                                    <div class="modal-footer panel-footer">
                                        <button id="modalAjaxClose" type="button" class="btn btn-default" data-dismiss="modal">
                                            ' . $LANG["close"] . '
                                        </button>
                                        <button type="button" class="btn btn-success sgdfgdg update_email_approver">' . $LANG["change_email"] . '</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>';

        return $html;
    }
    public function changeValidationMethodModal($approverEmail, $orderData, $LANG)
    {
        $https =  ($orderData->domain_validation_methods[0]->method == "https") ? "selected" : "";
        $dns = ($orderData->domain_validation_methods[0]->method == "dns") ? "selected" : "";
        $email =  ($orderData->domain_validation_methods[0]->method == "email") ? "selected" : "";

        $html = '';
        $html .= '  <div class="modal whmcs-modal fade" id="update_method" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content panel panel-primary">
                            <div class="modal-header panel-heading" id="reinstall-vm">
                                <h4 class="modal-title" id="modalAjaxTitle">' . $LANG["method_heading"] . '</h4>
                                <button id="modalAjaxCloseSmall" type="button" class="close" data-dismiss="modal">
                                    <span >×</span>
                                    <span class="sr-only">' . $LANG["close"] . '</span>
                                </button>
                            </div>
                            <div class="modal-body panel-body" id="update_method_body">
                                <form method="post" class="update_method">
                                    <div class="update_method_data">
                                        <label for="methosval">' . $LANG["select_method"] . '</label>
                                        <select id="methods" class="form-control">
                                            <option value="">' . $LANG["select_methods"] . '</option>
                                                <option value="https" ' . $https . '>
                                                    ' . $LANG["https"] . '
                                                </option>
                                                <option value="dns" ' . $dns . '>
                                                   ' .  $LANG["dns"] . '
                                                </option>
                                                <option value="email" ' . $email . '>
                                                   ' .  $LANG["email"] . '
                                                </option>';
        foreach ($approverEmail as $value) {
            $html .= '<option value="' . $value . '" ' . ($value == $orderData->domain_validation_methods[0]->method ? 'selected' : '') . '>' . $value . '</option>';
        }
        $html .= '</select>
                                    </div>
                                    <div class="modal-footer panel-footer">
                                        <button id="modalAjaxClose" type="button" class="btn btn-default" data-dismiss="modal">
                                            ' . $LANG["close"] . '
                                        </button>
                                        <button type="button" class="btn btn-success change_method">' . $LANG["change_method"] . '</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>';

        return $html;
    }
    public function adminButton($LANG)
    {
        global $CONFIG;

        $html = '';
        $html .= '<script src="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/js/script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>';
        $html .= '<div class="container-fluid">
                    <section class="action-sec py-5">
                        <div class="containers">
                            <div class="row justify-content-center">
                                <div class="col-md-2 mb-4">
                                    <div class="action-box">
                                        <button class="btn btn-primary w-100" type="button" data-target="#update_email"
                                            data-toggle="modal">' . $LANG["update_email"] . '</button>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-4">
                                    <div class="action-box">
                                        <button class="btn btn-primary w-100" type="button" data-target="#update_method"
                                            data-toggle="modal">' . $LANG["change_method"] . '</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>';
        return $html;
    }

    public function createCsrTokenHtml($clientcountries, $LANG, $action = '')
    {
        try {
            $html = '';
            $html .= '<div class="modal whmcs-modal fade in" id="create_csr" tabindex="-1" role="dialog">
                        <div class="modal-dialog">
                            <div class="modal-content panel panel-primary">
                                <div class="modal-header panel-heading">
                                <h4 class="modal-title" id="modalAjaxTitle">' . $LANG['create_token'] . '</h4>
                                    <button id="modalAjaxCloseSmall" type="button" class="close" data-dismiss="modal">
                                        <span aria-hidden="true">×</span>
                                        <span class="sr-only">' . $LANG['close'] . '</span>
                                    </button>
                                </div>

                                <div class="modal-body panel-body" id="create_csr_body">
                                    <form class="csr-token-form" method="POST">
                                        <div class="row">
                                            

                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="country">' . $LANG['country'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <select name="country" id="inputCountry" class="field form-control" aria-placeholder="Choose..">
                                                        <option value="">' . $LANG['select_country'] . '</option>';
            foreach ($clientcountries as $countryCode => $countryName) {
                $selected = '';
                $html .= '<option value="' . $countryCode . '"' . $selected . '>' . $countryName . '</option>';
            }
            $html .= ' </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="email">' . $LANG['email'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <input type="email" id="email" name="email" placeholder="' . $LANG['email_text'] . '" value="" >
                                                </div>
                                            </div>';
            $html .= '
            <label>' . $LANG['common_name'] . '</label>
            <input type="text" name="common_name" class="form-control" placeholder="example.com or *.example.com">
            ';

            $html .= '<div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="locality">' . $LANG['locality'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <input type="text" id="locality" name="locality" placeholder="' . $LANG['locality_text'] . '" value="" >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="organization">' . $LANG['organization'] . ' </label>
                                                </div>
                                                <div class="input-div">
                                                    <input type="text" id="organization" placeholder="' . $LANG['organization_text'] . '" name="organization" value="" >
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="signature_hash_algorithm">' . $LANG['signature_hash'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <select id="signature_hash_algorithm" name="signature_hash_algorithm" >
                                                        <option value="sha2">' . $LANG['sha2'] . '</option>
                                                        <option value="sha1">' . $LANG['sha1'] . '</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6 state-csr">
                                                <div class="csr_token_content_main">
                                                    <label for="state" class="field-icon" id="inputStateIcon">' . $LANG['state'] . '
                                                    </label>
                                                    <label for="stateinput" class="field-icon" id="inputStateIcon" style="display:none">
                                                       ' . $LANG['state'] . '
                                                    </label>
                                                </div>
                                                <div class="input-div">
                                                    <input type="text" name="state" id="state" class="field form-control" placeholder="" value="">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="unit">' . $LANG['unit'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <select id="unit" name="unit" required>
                                                        <option value="Dev" selected>' . $LANG['dev'] . '</option>
                                                        <option value="IT">' . $LANG['it'] . '</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="csr_token_content_main">
                                                    <label for="with_config">' . $LANG['config_text'] . '</label>
                                                </div>
                                                <div class="input-div">
                                                    <select id="with_config" name="with_config" required>
                                                        <option value="true" selected>' . $LANG['yes'] . '</option>
                                                        <option value="false">' . $LANG['no'] . '</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer panel-footer" id="create_csrFooter">
                                                <button id="modalAjaxClose" type="button" class="btn btn-default" data-dismiss="modal">
                                                    ' . $LANG['close'] . '
                                                </button>
                                                <button type="button" class="btn btn-success submit_btn create_csr_btn" name="create_csr">' . $LANG['create_token'] . '</button>
                                            </div>
                                        </form>
                                </div>
                            </div>
                        </div>
                    </div>';

            return $html;

            // <div class="col-md-6">
            //                                     <div class="csr_token_content_main">
            //                                         <label for="subject_alternative_name">'.$LANG['subject_alternative'].'</label>
            //                                     </div>
            //                                     <div class="input-div">
            //                                         <input type="text" id="subject_alternative_name" placeholder="'.$LANG['subject_alternative_text'].'" name="subject_alternative_name" value="" >
            //                                     </div>
            //                                 </div>


        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    public function insert_custom_fields_value($serviceid, $package_id, $fields = [])
    {
        try {
            foreach ($fields as $key => $value) {
                $custom_field_data = Capsule::table('tblcustomfields')->where("type", "product")->where("fieldname", "like", "%$key%")->where("relid", $package_id)->first();

                if ($custom_field_data) {
                    $field_value = Capsule::table('tblcustomfieldsvalues')->where("fieldid", "=", $custom_field_data->id)->where("relid", "=", $serviceid)->first();
                    /* checking field value exist */
                    if ($field_value->id) {
                        /* updating */
                        $field_value = Capsule::table('tblcustomfieldsvalues')->where("fieldid", "=", $custom_field_data->id)->where("relid", "=", $serviceid)->update(["value" => $value]);
                    } else {
                        /* inserting */
                        $field_value = Capsule::table('tblcustomfieldsvalues')->insert(["fieldid" => $custom_field_data->id, "relid" => $serviceid, "value" => $value]);
                    }
                }
            }

            return "success";
        } catch (\Exception $e) {
            logActivity('funtion(insert_custom_fields_value) Error:', $e->getMessage());
            return $e->getMessage();
        }
    }

    public function createCustomFields($customfieldarray)
    {
        try {
            foreach ($customfieldarray as $fieldname => $customfieldarrays) {

                if (Capsule::table('tblcustomfields')->where('type', $customfieldarrays['type'])->where('relid', $customfieldarrays['relid'])->where('fieldname', 'like', '%' . $fieldname . '%')->count() == 0) {
                    Capsule::table('tblcustomfields')->insert($customfieldarrays);
                }
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }
    public function insertlogDetails($response, $data = [], $action)
    {
        try {
            $logData = [
                'date' => date('Y/m/d H:i:s'),
                'module' => 'OpenProvider SSL Server',
                'action' => $action ?? 'OpenProvider API',
                'request' => json_encode($data),
                'response' => json_encode($response),
            ];

            Capsule::table('modssl_logs')->insertGetId($logData);
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in inserting Log data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in inserting Log data: ' . $e->getMessage());
        }
    }

    public function orderInfo($orderInfo, $lang)
    {
        try {
            global $CONFIG;

            $html = '';
            $html .= '<link rel="stylesheet" type="text/css" href="' . $CONFIG["SystemURL"] . '/modules/servers/openprovider_ssl/assets/css/style.css">';
            $html .= '<div class="orderDetailInfo" id="order_heading">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><strong>' . $lang['product_information'] . '</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>' . $lang['product_name'] . '</td>
                                <td>' . $orderInfo->product_name . '</td>
                            </tr>
                            <tr>
                                <td>' . $lang['brand_name'] . '</td>
                                <td>' . $orderInfo->brand_name . '</td>
                            </tr>
                            <tr>
                                <td class="val-email-tr">' . $lang['email_approver'] . '</td>
                                <td>' . $orderInfo->email_approver . '</td>
                            </tr>
                            <tr>
                                <td>' . $lang['auto_renew'] . '</td>';
            if ($orderInfo->autorenew == 'on') {
                $html .= '<td><span class="badge badge-success">' . $lang['on'] . '</span></td>';
            } elseif ($orderInfo->autorenew == 'off') {
                $html .= '<td><span class="badge badge-danger">' . $lang['off'] . '</span></td>';
            } else {
                $html .= '<td>' . $orderInfo->autorenew . '</td>';
            }

            $html .= '  </tr>
                            <tr>
                                <td>' . $lang['status'] . '</td>';

            if ($orderInfo->status == 'ACT') {
                $html .= '<td><span class="badge badge-success">' . $lang['active'] . '</span></td>';
            } elseif ($orderInfo->status == 'PAI') {
                $html .= '<td><span class="badge badge-success">' . $lang['purchased'] . '</span></td>';
            } elseif ($orderInfo->status == 'REQ') {
                $html .= '<td><span class="badge badge-success">' . $lang['requested'] . '</span></td>';
            } elseif ($orderInfo->status == 'REJ' || $orderInfo->status == 'FAI') {
                $html .= '<td><span class="badge badge-secondary">' . $lang['order_cancelled'] . '</span></td>';
            } elseif ($orderInfo->status == 'EXP') {
                $html .= '<td><span class="badge badge-info">' . $lang['expired_order'] . '</span></td>';
            }

            $html .= '</tr>
                            <tr>
                                <td class="val-method-tr">' . $lang['domain_validation_method'] . '</td>
                                <td>' . ucfirst($orderInfo->domain_validation_methods[0]->method) . '</td>
                            </tr>
                            <tr>
                                <td>' . $lang['software'] . '</td>
                                <td>' . $orderInfo->software . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                ';

            return $html;
        } catch (\Illuminate\Database\QueryException $e) {
            throw new \Exception('Error in Order Info data: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new \Exception('Error in Order Info data: ' . $e->getMessage());
        }
    }

    private function productCongifurationArray()
    {
        return [
            [
                "name" => "No of Domains",
                "friendlyName" => "no_of_domain|No of Domains",
                "description" => "Number of domains to include in certificate",
                "optiontype" => "4",
                "qtyminimum" => 1,
                "qtymaximum" => 10,
                "optionvalue" =>  [
                    "Unit" => "Unit",
                ]
            ],
        ];
    }

    public function createConfigurableOptions($pid)
    {
        try {
            $groupName = 'Configurable options Openprovider SSL';
            $configgroup = $this->createConfigGroup($groupName, 'Number of domains to include in certificate');
            if ($configgroup["status"] == "error") {
                return $configgroup;
            }
            $configLinkId = $this->createConfigLinks($configgroup, $pid);
            $configurableOptions = $this->productCongifurationArray();
            foreach ($configurableOptions as $key => $value) {
                if ($value["optiontype"] == "1" || $value["optiontype"] == "2" || $value["optiontype"] == "3") {
                    $configgroupOption = $this->configGroupOption($configgroup, $value["friendlyName"], $value["optiontype"]);
                } else {
                    $configgroupOption = $this->configGroupOption($configgroup, $value["friendlyName"], $value["optiontype"], $value["qtyminimum"], $value["qtymaximum"]);
                }
                if (!empty($value["optionvalue"])) {
                    foreach ($value["optionvalue"] as $optiontypekey => $optiontypevalue) {

                        $friendlyName = ($optiontypekey . "|" . $optiontypevalue);
                        $this->configGroupSubOption($configgroupOption, $friendlyName);
                    }
                } else {
                    $this->configGroupSubOption($configgroupOption, " ");
                }
            }
            return [
                'status' => 'success',
                'message' => 'Configurable potion has been created successfully!',
            ];
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    function createConfigGroup($name, $description = '')
    {
        try {
            $data = [
                'name' => $name,
                'description' => $description
            ];
            $name = explode("|", $name);
            $get_group_id = Capsule::table('tblproductconfiggroups')->where("name", "like", "%" . $name["0"] . "%")->first();

            if (empty($get_group_id->name)) {
                $confid_id = Capsule::table('tblproductconfiggroups')->insertGetId($data);
                return $confid_id;
            } else {
                $confid_id =  $get_group_id->id;
                return $confid_id;
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Somthing Went Wrong' . $e->getMessage(),
            ];
        }
    }

    function createConfigLinks($gid, $pid, $status = "insert")
    {
        try {
            $data = [
                'gid' => $gid,
                'pid' => $pid
            ];

            $check_exxisting_data = Capsule::table('tblproductconfiglinks')->where("pid", "=", $pid)->where("gid", "=", $gid)->first();
            if (empty($check_exxisting_data)) {
                $inserted_id = Capsule::table('tblproductconfiglinks')->insertGetId($data);
                return $inserted_id;
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Somthing Went Wrong' . $e->getMessage(),
            ];
        }
    }

    function configGroupOption($id, $optionname, $optiontype = '1', $qtyminimum = '0', $qtymaximum = '0', $order = '0', $hidden = '0')
    {

        try {
            $data = [
                'gid' =>  $id,
                'optionname' => $optionname,
                'optiontype' => $optiontype,
                'qtyminimum' => $qtyminimum,
                'qtymaximum' => $qtymaximum,
                'order' => $order,
                'hidden' => $hidden

            ];
            $get_group_id = Capsule::table('tblproductconfigoptions')->where('gid', $id)->where('optionname', $optionname)->first();
            if (empty($get_group_id)) {
                $ConfigGroupOption_id = Capsule::table('tblproductconfigoptions')->insertGetId($data);
                return $ConfigGroupOption_id;
            } else {

                $ConfigGroupOption_id =  $get_group_id->id;
                return $ConfigGroupOption_id;
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Somthing Went Wrong' . $e->getMessage(),
            ];
        }
    }

    function configGroupSubOption($configid, $optionname, $sortorder = '0',  $hidden = '0')
    {
        try {
            $data = [
                'configid' =>  $configid,
                'optionname' => $optionname,
                'sortorder' => $sortorder,
                'hidden' => $hidden
            ];
            $get_group_id = Capsule::table('tblproductconfigoptionssub')->where('configid', $configid)->where('optionname', $optionname)->first();
            if (empty($get_group_id)) {
                $ConfigGroupOption_id = Capsule::table('tblproductconfigoptionssub')->insertGetId($data);
                $command = 'GetCurrencies';
                $results = localAPI($command);
                foreach ($results as $key => $val) {
                    if ($key == 'currencies') {
                        foreach ($val as $key1 => $val1) {
                            foreach ($val1 as $key2 => $val2) {
                                $data_cur = [
                                    'type' =>  'configoptions',
                                    'currency' => $val2['id'],
                                    'relid' => $ConfigGroupOption_id,
                                    'msetupfee' => '0.00',
                                    'qsetupfee' => '0.00',
                                    'ssetupfee' => '0.00',
                                    'asetupfee' => '0.00',
                                    'bsetupfee' => '0.00',
                                    'tsetupfee' => '0.00',
                                    'monthly' => '0.00',
                                    'quarterly' => '0.00',
                                    'semiannually' => '0.00',
                                    'annually' => '0.00',
                                    'biennially' => '0.00',
                                    'triennially' => '0.00'
                                ];
                                $ConfigGroupOption_cur_id = Capsule::table('tblpricing')->insertGetId($data_cur);
                            }
                        }
                    }
                }
                return $ConfigGroupOption_id;
            } else {

                $ConfigGroupOption_id =  $get_group_id->id;
                return $ConfigGroupOption_id;
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Somthing Went Wrong' . $e->getMessage(),
            ];
        }
    }
}

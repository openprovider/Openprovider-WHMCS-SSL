<?php

namespace Module\OpenproviderSsl\classes;

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\OpenproviderSsl\Helper;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

class ApiCall
{
    public $url = '';

    public function genrateToken()
    {
        try {

            $helper = new Helper();
            $authData = $helper->fetch_table_record('modssl_api_setting', ['id' => '1'], 'singleRowData');

            $postData = [
                'username' => $authData->api_user_name,
                'password' => decrypt($authData->api_password),
            ];

            $testConnection = $this->post("{$authData->api_url}/auth/login", $postData, "Open Ssl Test Connection");

            if ($testConnection['httpcode'] != 200) {
                return $testConnection;
            }

            if (!empty($testConnection['result']->data->token)) {
                $data = ['token' => $testConnection['result']->data->token];
                $where = ['id' => '1'];
                $updateReseller =  $helper->insertUpdate('modssl_api_setting', $where, $data);
            }

            return $testConnection;
        } catch (\Exception $e) {
            throw new \Exception('Error while generating Token: ' . $e->getMessage());
        }
    }

    private function createHeader($action)
    {
        try {
            if ($action == "Open Ssl Test Connection") {
                return [
                    'content-type:application/json',
                ];
            }
            $result = $this->genrateToken();

            if ($result['httpcode'] != 200) {
                return $result;
            }
            return [
                'content-type:application/json',
                'Authorization: Bearer ' . $result['result']->data->token,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Error while generating header: ' . $e->getMessage());
        }
    }

    public function __curlCall($method, $data = null, $apiUrl = null, $action = '')
    {
        $helper = new Helper();
        $header = $this->createHeader($action);
        if (isset($header['httpcode']) && $header['httpcode'] != 200) {
            return $header;
        }

        $curl = curl_init();
        switch ($method) {
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($data) ? json_encode($data) : ""));
                break;

            default:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            throw new \Exception(curl_error($curl));
        }
        curl_close($curl);
        $decodedResponse = json_decode($response);
        $replaceVars = $this->getSensitiveLogValues($data, $decodedResponse);
        $sanitizedRequest = $this->sanitizeLogRequest($data);
        $sanitizedResponse = $this->sanitizeLogResponseToken($decodedResponse);
        // logModuleCall masks $replaceVars values itself wherever they appear, same as modules/registrars/openprovider's Logger.
        logModuleCall("Open Provider SSl", $action, $data, $decodedResponse, null, $replaceVars);
        $helper->insertlogDetails($sanitizedResponse, (empty($data) ? ['url' => $apiUrl] : $sanitizedRequest), $action);
        return ['httpcode' => $httpCode, 'result' => $decodedResponse];
    }

    // Values logModuleCall should mask wherever they appear in the logged request/response.
    private function getSensitiveLogValues($data, $response = null)
    {
        $values = [];
        if (is_array($data) && !empty($data['password'])) {
            $values[] = $data['password'];
            $values[] = htmlentities($data['password']);
        }
        if (is_object($response) && isset($response->data) && is_object($response->data) && !empty($response->data->token)) {
            $values[] = $response->data->token;
        }
        return $values;
    }

    // modssl_logs is our own table, not covered by logModuleCall's masking, so mask known-sensitive fields ourselves.
    private function sanitizeLogRequest($data)
    {
        if (is_array($data) && array_key_exists('password', $data)) {
            $data['password'] = str_repeat('*', strlen($data['password']));
        }
        return $data;
    }

    // Same reasoning as sanitizeLogRequest(), but for the access token returned in the response.
    private function sanitizeLogResponseToken($response)
    {
        if (is_object($response) && isset($response->data) && is_object($response->data) && !empty($response->data->token)) {
            $response = clone $response;
            $response->data = clone $response->data;
            $response->data->token = str_repeat('*', strlen($response->data->token));
        }
        return $response;
    }

    public function get($url, $data = null, $action = '')
    {
        try {
            $response = $this->__curlCall("GET", $data, $url, $action);
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error while getting data for ' . $action . ' : ' . $e->getMessage());
        }
    }

    public function post($url, $data = null, $action = '')
    {
        try {
            $response = $this->__curlCall("POST", $data, $url, $action);
            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error while creating ' . $action . ' : ' . $e->getMessage());
        }
    }

    public function put($url, $data = null, $action = '')
    {
        try {
            // $response = $this->__curlCall("PUT", $data, $url, $action);
            // return $response;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }


    public function delete($url, $data = null, $action = '')
    {
        try {
            // $response = $this->__curlCall("DELETE", $data, $url, $action);
            // return $response;
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    /* 
        * @param string $applicationKey 
        * @param string $consumerKey 
        * @param string $signature 
        @return array header.
    */
}

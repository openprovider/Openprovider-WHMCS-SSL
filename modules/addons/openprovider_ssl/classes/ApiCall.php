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
        logModuleCall("Open Provider SSl", $action, $data, json_decode($response));

        $helper->insertlogDetails(json_decode($response), (empty($data) ? ['url' => $apiUrl] : $data), $action);
        return ['httpcode' => $httpCode, 'result' => json_decode($response)];
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

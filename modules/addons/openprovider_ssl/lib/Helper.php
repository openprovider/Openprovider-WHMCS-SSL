<?php

namespace WHMCS\Module\Addon\OpenproviderSsl;

use Module\OpenproviderSsl\classes\ApiCall;

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
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

    function createSlug($string)
    {
        $string = strtolower($string);
        $string = str_replace(' ', '-', $string);
        $string = preg_replace('/[^a-z0-9-]/', '', $string);
        $string = trim($string, '-');
        return $string;
    }

    public function insertlogDetails($response, $data = [], $action)
    {
        try {
            $logData = [
                'date' => date('Y/m/d H:i:s'),
                'module' => 'OpenProvider SSL Addon',
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

    public function clientListLogs($data)
    {
        try {
            // Set the timezone
            date_default_timezone_set('Asia/Qatar');

            // Get request data from the $data parameter
            $columnNumber = $data['order'][0]['column'];
            $ordercolumn = $data['order'][0]['dir'];
            $searchValue = $data['search']['value'];

            // Determine the column name based on the column number
            $columns = ['date', 'module', 'action', 'request', 'response'];
            $columnName = isset($columns[$columnNumber]) ? $columns[$columnNumber] : 'module';

            // Start building the query using the Capsule query builder
            $query = Capsule::table('modssl_logs');

            // Add search filters for module and action
            if ($searchValue !== '') {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('module', 'LIKE', '%' . $searchValue . '%')
                        ->orWhere('action', 'LIKE', '%' . $searchValue . '%');
                });
            }

            // Get pagination details from the $data parameter
            $start = isset($data['start']) ? $data['start'] : 0;
            $length = isset($data['length']) ? $data['length'] : 10; // Default length

            // Adjust length if it's zero
            if ($length == "0") {
                $length = Capsule::table("modssl_logs")->count();
            }

            // Execute the query with ordering and limits
            $listPagesData = $query->orderBy($columnName, $ordercolumn)
                ->offset($start)
                ->limit($length)
                ->get();

            // Query for total count (without limits)
            $countTotal = Capsule::table("modssl_logs")->count();

            // Prepare data for the response
            $dataArray = [];
            foreach ($listPagesData as $log) {
                $dataArray[] = [
                    'date' => $log->date,
                    'module' => $log->module,
                    'action' => $log->action,
                    'request' => '<textarea rows="5" class="form-control">' . htmlspecialchars(print_r(json_decode($log->request, true), true)) . '</textarea>',
                    'response' => '<textarea rows="5" class="form-control">' . htmlspecialchars(print_r(json_decode($log->response, true), true)) . '</textarea>',
                    // 'request' => '<textarea rows="5" class="form-control">' . htmlspecialchars(json_encode(json_decode($log->request, true), JSON_PRETTY_PRINT)) . '</textarea>',
                    // 'response' => '<textarea rows="5" class="form-control">' . htmlspecialchars(json_encode(json_decode($log->response, true), JSON_PRETTY_PRINT)) . '</textarea>',
                ];
            }

            // Create the response array
            $response = [
                'draw' => intval($data['draw']),
                'recordsTotal' => $countTotal,
                'recordsFiltered' => $countTotal, // Adjust if implementing server-side filtering
                'data' => $dataArray,
            ];

            // Return the JSON response
            return json_encode($response);
        } catch (\Exception $e) {
            return json_encode([
                'status' => 'error',
                'description' => 'Something went wrong: ' . $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
        }
    }

    public function getWhmcsConversionRate()
    {
        try {
            $defaultEuroExchangeRates = [];

            foreach (\WHMCS\Utility\CurrencyExchange::fetchCurrentRates() as $key =>  $exchangeRate) {
                $defaultEuroExchangeRates[$key] = $exchangeRate;
            }

            return $defaultEuroExchangeRates;
        } catch (\Exception $e) {
            throw new \Exception('Error: ' . $e->getMessage());
        }
    }
    public function productPrices($price)
    {
        $productPrices = [
            'monthly' => $price,                          // Monthly price
            'quarterly' => $price * 3,                    // Quarterly price (3 months)
            'semiannually' => $price * 6,                  // Semiannually price (6 months)
            'annually' => $price * 12,                     // Annually price (12 months)
            'biannually' => $price * 24,                   // Biannually price (24 months)
            'triennially' => $price * 36,                  // Triennially price (36 months)
        ];
        return $productPrices;
    }

    public function updateprice($productCurrency, $relid, $type, $productPrices = [])
    {
        $currencies = $this->fetch_table_record("tblcurrencies", [], '');
        // $defaultEuroExchangeRates = $this->helper->getWhmcsConversionRate();
        if (!isset($currencies[0])) {
            throw new \Exception('Error: Please enable atleat one currency!.');
        }
        $defaultEuroExchangeRates = $this->getWhmcsConversionRate();

        foreach ($currencies as $key => $currency) {
            $monthly = ($productPrices["monthly"] == 0 ? 0 : round(($productPrices["monthly"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));

            $quarterly =  ($productPrices["quarterly"] == 0 ? 0 : round(($productPrices["quarterly"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
            $semiannually =  ($productPrices["semiannually"] == 0 ? 0 : round(($productPrices["semiannually"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
            $annually =  ($productPrices["annually"] == 0 ? 0 : round(($productPrices["annually"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));

            $bianually =  ($productPrices["biannually"] == 0 ? 0 : round(($productPrices["biannually"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));
            $triennially =  ($productPrices["triennially"] == 0 ? 0 : round(($productPrices["triennially"] / $defaultEuroExchangeRates[$productCurrency]) * $defaultEuroExchangeRates[$currency->code], 5));

            if ($type != "configoptions") {
                $monthly = ($monthly == 0 || $monthly == "0.00" ? "-1.00" : $monthly);
                $quarterly = ($quarterly == 0 || $quarterly == "0.00" ? "-1.00" : $quarterly);
                $semiannually = ($semiannually == 0 || $semiannually == "0.00" ? "-1.00" : $semiannually);
                $annually = ($annually == 0 || $annually == "0.00" ? "-1.00" : $annually);
                $bianually = ($bianually == 0 || $bianually == "0.00" ? "-1.00" : $bianually);
                $triennially = ($triennially == 0 || $triennially == "0.00" ? "-1.00" : $triennially);
            }

            $select = $this->fetch_table_record("tblpricing", ["type" => $type, "relid" => $relid, 'currency' => $currency->id], "singleRowData");
            if (empty($select) && !isset($select->id)) {
                Capsule::table('tblpricing')->insertGetId(array('type' => $type, 'relid' => $relid, 'monthly' => $monthly, 'quarterly' => $quarterly, 'semiannually' => $semiannually, 'annually' => $annually, 'biennially' => $bianually, 'currency' => $currency->id, "triennially" => $triennially));
            } else {
                Capsule::table('tblpricing')->where("type", $type)->where("relid", $relid)->where('currency', $currency->id)->update(['monthly' => $monthly, 'quarterly' => $quarterly, 'semiannually' => $semiannually, 'annually' => $annually, 'biennially' => $bianually, "triennially" => $triennially]);
            }
        }

        return "success";
    }
}

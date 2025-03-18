<?php

use WHMCS\Database\Capsule;
use WHMCS\Module\Addon\OpenproviderSsl\Helper;
use Module\OpenproviderSsl\classes\CustomDatabase;
use WHMCS\Module\Addon\OpenproviderSsl\Admin\AdminDispatcher;

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if (file_exists(__DIR__ . DS . 'classes/ApiCall.php')) {
    require_once __DIR__ . DS . 'classes/ApiCall.php';
}

function openprovider_ssl_config()
{
    global $CONFIG;

    /* adding lang file according to whmcs default language */
    $language = $CONFIG['Language'];

    $langfilename = __DIR__ . '/lang/' . $language . '.php';
    if (file_exists($langfilename)) {
        require($langfilename);
    } else {
        require(__DIR__ . '/lang/english.php');
    }

    $lang = $_ADDONLANG;
    return [
        'name' => $lang["addon_name"],
        'description' =>  $lang["addon_desc"],
        'author' => '<a href="https://cp.openprovider.eu/" target="_blank"><img width="150" src="../modules/addons/openprovider_ssl/assets/images/openprovider.png" alt="Openprovider SSL Service"></a>',
        'language' => 'english',
        'version' => '1.0.0',
        'fields' => [
            'margin' => [
                'FriendlyName' => 'Product Margin',
                'Type' => 'text',
                'Size' => '35',
                'Default' => '',
                'Description' => 'Enter the margin for the product here as a percentage.'
            ],
            'product_grp_name' => [
                'FriendlyName' => 'Product Group Name',
                'Type' => 'text',
                'Size' => '35',
                'Default' => '',
                'Description' => 'Enter the Product Group Name here.'
            ],
            "delete_db" => array("FriendlyName" => "Delete Database Table", "Type" => "yesno", "Default" => "", "Description" => "Tick this box to delete the addon module database table when deactivating the module."),
        ]
    ];
}

function openprovider_ssl_activate()
{
    require_once __DIR__ . DS . 'classes/CustomDatabase.php';
    /* creating all the custon table */
    $create = new CustomDatabase();
    $create->createTableIfNotExist();
}

function openprovider_ssl_deactivate()
{
    require_once __DIR__ . DS . 'classes/CustomDatabase.php';
    /* creating all the custon table */
    $deleteTable = new CustomDatabase();

    $deleteTable->deleteTalbe();
}

function openprovider_ssl_output($vars)
{
    $whmcs = WHMCS\Application::getInstance();
    $action = !empty($whmcs->get_req_var("action")) ? $whmcs->get_req_var("action") : 'apisetting';
    $dispatcher = new AdminDispatcher();
    $dispatcher->dispatch($action, $vars);
}

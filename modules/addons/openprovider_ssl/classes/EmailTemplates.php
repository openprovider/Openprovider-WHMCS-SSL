<?php

namespace WGSModule\OpenproviderSsl\classes;

use WHMCS\Module\Addon\OpenproviderSsl\Helper;
use WHMCS\Database\Capsule;

class EmailTemplates extends Helper
{
    public function customEmailTempaltes()
    {
        try {
            // return [
            //     [
            //         "name" => "Hardware Reboot",
            //         "type" => "general",
            //         "subject" => "Hardware Reboot Notification",
            //         "message" => 'Dear Customer,<br /><br /><span>You have requested a remote</span><span class="il">hardware</span><span></span><span class="il">reboot</span><span>for your server {$custom_server_name}.<br />We have successfully rebooted your Server.<br />Operating System : {$operating_system}<br /><br />.Thanks for using our service.<br /><br /><br /><br /></span>',
            //         "custom" => "1",
            //         "plaintext" => 0
            //     ],
            // ];
        } catch (\Exception $e) {
            throw new \Exception('Error while creating custom email templates: ' . $e->getMessage());
        }
    }

    public function getAllCustomEmailTemp(){

        try {
            $custommailTemplate = Capsule::table("tblemailtemplates")->where(["type"=>"general", "custom" => "1"])->whereIn("name", ['Hardware Reboot','Server Re-installed','Ftp Backup Password change','FTP Backup Configured','Server Details','Rescue-Pro Mode','BSD10 Rescue mode','Service Monitoring','Detection of an attack on IP','Spam Detected','Anti-hack','Monitoring Online','End of Attack','Detection of an attack','Operation Hard Reboot Finished','Additional IP addresses','Additional Disk'])->get()->toArray();
            return $custommailTemplate;

        } catch (\Exception $e) {
            throw new \Exception('Error while getting all email templates: ' . $e->getMessage());
        }
    }

}

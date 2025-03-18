{include file=$tplVar.header}

<div class="main-heading-box">
    <strong class="main-heading">{$tplVar['lang']["log_details"]}</strong>
    {if count($tplVar['logData']) > 0}
    <button id="deletelog" class="btn btn-danger"><i class="fa fa-trash"></i>
        {$tplVar['lang']["delete_logs"]}</button>
    {/if}
</div>

<div id="log" class="">
    <div class="tab-contents">
        <div class="logs-details">
            <table class="ssl-log datatable" id="tblModuleLogSSl" width="100%" border="0" cellspacing="1"
                cellpadding="3">
                <thead>
                    <tr>
                        <th width="120">{$tplVar['lang']["date"]}</th>
                        <th width="120">{$tplVar['lang']["module"]}</th>
                        <th width="150">{$tplVar['lang']["action"]}</th>
                        <th>{$tplVar['lang']["request"]}</th>
                        <th>{$tplVar['lang']["response"]}</th>
                    </tr>
                </thead>
                <tbody class="logs_body">
                </tbody>
            </table>

        </div>
    </div>
</div>
{assign var=unique_id value=10|mt_rand:20000000}

<link rel="stylesheet" type="text/css" href="{$tplVar.cssPath}style.css?v={$unique_id}">
<script src="{$tplVar.scriptPath}script.js?v={$unique_id}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.2/additional-methods.min.js"></script>

<div class="add_hdr toggle-header-main">
    <div class="smarttech-header-icon">
    <a href="https://www.openprovider.com/" class="small_logo bars-icon" target="_blank"><img
                src="{$tplVar['urlPath']}assets/images/openprovider.png"></a>
    </div>

    <div class="add_nav">
        <ul class="nav nav-pills">
            <li class="">
                <a href="{$tplVar.moduleLink}" class="{if $tplVar.tab =='apisetting' || $tplVar.tab==''}active{/if}"><i class="fa fa-cog" aria-hidden="true"></i>{$tplVar['lang']['api_setting']}</a>
            </li>
            <li class=""><a href="{$tplVar.moduleLink}&action=productsync" class="{if $tplVar.tab =='productsync'}active{/if}"><i class="fas fa-money-bill-wave fa-fw"></i>{$tplVar['lang']['productsync']}</a></li>
            <li class=""><a href="{$tplVar.moduleLink}&action=logs" class="{if $tplVar.tab =="logs"} active{/if}"><i class="fad fa-copy"
                aria-hidden="true"></i>{$tplVar['lang']['logs']}</a></li>
        </ul>
    </div>
</div>
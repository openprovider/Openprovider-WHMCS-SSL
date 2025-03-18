{include file=$tplVar.header}


<div class="api_setting-main">
    <div class="container mt-5">
        <div class="row justify-content-center api-setting">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">{$tplVar['lang']['apisetting_head']}</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" class="test-api-form" id="api-setting">
                            <div class="form-group">
                                <label for="apiurl">{$tplVar['lang']['apiurl']}</label>
                                <input type="text" id="apiurl" value="{$tplVar['connectionData']->api_url}"
                                    name="api_url" class="form-control"
                                    placeholder="{$tplVar['lang']['apiurl']} like: https://api.openprovider.eu/v1beta">
                            </div>
                            <div class="form-group">
                                <label for="username">{$tplVar['lang']['username']}</label>
                                <input type="text" id="username" value="{$tplVar['connectionData']->api_user_name}"
                                    name="api_user_name" class="form-control"
                                    placeholder="{$tplVar['lang']['username']}">
                            </div>
                            <div class="form-group">
                                <label for="password">{$tplVar['lang']['password']}</label>
                                <div class="input-group">
                                    <input type="password" id="password" value="{$tplVar['decryptPassword']}"
                                        name="api_password" class="form-control"
                                        placeholder="{$tplVar['lang']['password']}">
                                        <span class="input-group-text" id="togglePassword" style="cursor: pointer;">
                                            <i class="fa fa-eye-slash" id="eyeIcon"></i>
                                        </span>
                                </div>
                            </div>

                            <div class="form-group api-btn-footer">
                                <button type="button" name="savesetting" id="savesetting" class="btn btn-primary w-100">
                                    {$tplVar['lang']['savesetting']}
                                </button>
                                <button type="button" name="testconnection" id="testconnection"
                                    class="btn btn-success w-100">
                                    {$tplVar['lang']['testconection']}
                                </button>
                                {if $tplVar['connectionData']->token != ''}
                                <span class="badge badge-pill badge-success"> <i class="fas fa-check fa-fw"></i> {$tplVar['lang']['succ_conect']}</span>
                                {else}
                                <span class="badge badge-pill badge-danger"> <i class="fas fa-times fa-fw"></i> {$tplVar['lang']['not_conect']}</span>
                                {/if}
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
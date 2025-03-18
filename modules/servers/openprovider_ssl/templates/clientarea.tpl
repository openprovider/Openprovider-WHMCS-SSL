{assign var=unique_id value=10|mt_rand:20}
<link href="{$assets}/css/style.css?v={$unique_id}" rel="stylesheet">
<script src="{$assets}/js/script.js?v={$unique_id}"></script>
<script src="{$assets}/js/jquerygrowl.js" type="text/javascript"></script>
<link href="{$assets}/css/jquerygrowl.css" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



<div class="container-fluid clientarea-action">
    <section class="action-sec">
        <div class="containers">
           
            <div class="row justify-content-center">
                <div class="col-md-4 mt-3">
                    <div class="action-box">
                        <!-- Bootstrap button -->
                        <button class="btn btn-primary w-100 resend_email_btn"
                            type="button">{$LANG["resend_email"]}</button>
                    </div>
                </div>
                {*<div class="col-md-4 mt-3">
                    <div class="action-box">
                        <!-- Bootstrap button -->
                        <button class="btn btn-primary w-100 reissue_btn"
                            type="button">{$LANG["reissue_order"]}</button>
                    </div>
                </div>*}
                <div class="col-md-4 mt-3">
                    <div class="action-box">
                        <!-- Bootstrap button -->
                        <button class="btn btn-primary w-100" type="button " data-target="#update_email"
                            data-toggle="modal">{$LANG["update_email"]}</button>
                    </div>
                </div>
                <div class="col-md-4 mt-3">
                    <div class="action-box">
                        <!-- Bootstrap button -->
                        <button class="btn btn-primary w-100" type="button" data-target="#update_method"
                            data-toggle="modal">{$LANG["change_method"]}</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>


<div class="clientarea-order-info">
    {$orderInfo}
</div>

{$aprovalEmailModal}
{$updateValidationMethod}

<div class="">
    {$error}
</div>
{include file=$tplVar.header}

<div class="container-box">
    <div class="box light shadow-sm">
        <div class="box-body">
            <div class="product-box">
                <div class="rc-actions text-center">
                    <button type="button" class="btn btn-success btn-lg product_sync">
                        {$tplVar['lang']['sync_product']}
                    </button>
                </div>

                <div class="allproduct-box">
                    {if count($tplVar['allSslProduct']) == 0}
                    <div class="errorbox"><strong><span
                                class="title">{$tplVar['lang']['not_produc_sync']}</span></strong><br>{$tplVar['lang']['not_produc_sync1']}
                    </div>
                    {else}
                    <div class="product-table">
                        <form method="post" class="all-ssl-product">
                            <div class="create-product-box">
                                <button type="button" data-toggle="modal" class="btn btn-success create-product">
                                    {$tplVar['lang']['create_product']}
                                </button>
                            </div>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th style="display:none">{$tplVar['lang']['id']}</th>
                                        <th>{$tplVar['lang']['provider']}</th>
                                        <th>{$tplVar['lang']['product_name']}</th>
                                        <th style="display:none">{$tplVar['lang']['product_price']}</th>
                                        <th>{$tplVar['lang']['max_domains']}</th>
                                        <th>{$tplVar['lang']['delivery_time']}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$tplVar['allSslProduct'] item=product}
                                    <tr>
                                        <td style="display:none"><input type="text" name="id[]" value="{$product->configoption8}"
                                                class="form-control no-border" readonly></td>
                                        <td><input type="text" name="provider[]" value="{$product->configoption9|default:'-'}"
                                                class="form-control no-border" readonly></td>
                                        <td><input type="text" name="product_name[]" value="{$product->name|default:'-'}"
                                                class="form-control no-border" readonly></td>
                                        <td style="display:none"><input type="text" name="product_price[]"
                                                value="{$product->configoption10}"
                                                class="form-control no-border" readonly></td>
                                        <td><input type="number" name="max_domains[]" value="{$product->configoption11|default:'-'}"
                                                class="form-control no-border" readonly></td>
                                        <td><input type="text" name="delivery_time[]" value="{$product->configoption12|default:'-'}"
                                                class="form-control no-border" readonly></td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table>

                        </form>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

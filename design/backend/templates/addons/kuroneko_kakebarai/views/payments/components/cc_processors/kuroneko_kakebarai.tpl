{* $Id: kuroneko_kakebarai.tpl by takahashi from cs-cart.jp 2016 *}

<p>{__("jp_kuroneko_kakebarai_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_connections_settings") target="#krnkkb_connection_settings"}
<div id="krnkkb_connection_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("test_live_mode")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][mode]" id="mode">
                    <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                    <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
                </select>
            </div>
        </div>
    </fieldset>
</div>

{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_payment_settings") target="#krnkkb_payment_settings"}
<div id="krnkkb_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_kuroneko_kakebarai_product_code")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][krnkkb_product_code]" id="mode" style="width:400px">
                    <option value="NO" {if $processor_params.krnkkb_product_code == "NO"}selected="selected"{/if}>{__("jp_kuroneko_kakebarai_no_product_code")}</option>
                    <option value="10" {if $processor_params.krnkkb_product_code == "10"}selected="selected"{/if}>{__("jp_kuroneko_kakebarai_10_product_code")}</option>
                </select>
            </div>
        </div>
    </fieldset>
</div>
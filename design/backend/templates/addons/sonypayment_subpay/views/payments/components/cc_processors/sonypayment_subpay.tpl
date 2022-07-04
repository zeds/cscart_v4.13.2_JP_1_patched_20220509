{* $Id: smartlink_ccreg.tpl by takahashi from cs-cart.jp 2019 *}

<p>{__('jp_sonypayment_subpay_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_sonypayment_subpay_connections_settings') target="#text_sonys_cc_connection_settings"}

<div id="text_sonys_cc_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="token_ninsyo_code">{__("jp_sonys_token_ninsyocode")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][token_ninsyo_code]" id="token_ninsyo_code" value="{$processor_params.token_ninsyo_code}"  size="60">
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_sonypayment_subpay_payment_settings') target="#text_sonys_cc_payment_settings"}

<div id="text_sonys_cc_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="use_cvv">{__('jp_sonypayment_subpay_use_cvv')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="process_type">{__("jp_sonys_process_type")}:</label>
        <div class="controls">
            <input id="elm_process_type_capture" class="radio" type="radio" value="capture" name="payment_data[processor_params][process_type]" {if $processor_params.process_type == "capture" || !$processor_params.process_type} checked="checked"{/if}/> {__("jp_sonys_gathering")}
            <input id="elm_process_type_auth" class="radio" type="radio" value="auth_only" name="payment_data[processor_params][process_type]" {if $processor_params.process_type == "auth_only"} checked="checked"{/if}/> {__("jp_sonys_auth_only")}
        </div>
    </div>
</div>


<!--
{include file="common/subheader.tpl" title=__('jp_sonys_3dsecure_settings') target="#text_sonys_3dsecure_settings"}

<div id="text_sonys_3dsecure_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="tdflag">{__("jp_sonys_use_3dsecure")}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][tdflag]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][tdflag]" id="tdflag" value="true" {if $processor_params.tdflag == "true"} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="3dsecure_aes_key">{__("jp_sonys_3dsecure_aes_key")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][3dsecure_aes_key]" id="3dsecure_aes_key" value="{$processor_params.3dsecure_aes_key}"  size="60">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="3dsecure_aes_key">{__("jp_sonys_3dsecure_aes_iv")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][3dsecure_aes_iv]" id="3dsecure_aes_iv" value="{$processor_params.3dsecure_aes_iv}"  size="60">
        </div>
    </div>
</div>
-->
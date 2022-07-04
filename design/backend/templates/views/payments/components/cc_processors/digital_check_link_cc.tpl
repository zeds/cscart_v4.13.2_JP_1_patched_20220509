{* $Id: digital_check_link_cc.tpl by tommy from cs-cart.jp 2015 *}

<p>{__('jp_digital_check_link_cc_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_digital_check_connections_settings') target="#text_dc_cc_link_connection_settings"}

<div id="text_dc_cc_link_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="ip">{__('jp_digital_check_ip')}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][ip]" id="ip" value="{$processor_params.ip}" class="input-text" size="10" />
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_digital_check_cc_payment_settings') target="#text_dc_link_cc_payment_settings"}

<div id="text_dc_link_cc_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="with_capture">{__("jp_digital_check_process_type")}:</label>
        <div class="controls">
            <input id="elm_with_capture_yes" class="radio" type="radio" value="1" name="payment_data[processor_params][with_capture]" {if $processor_params.with_capture == "1" || !$processor_params.with_capture} checked="checked"{/if}/> {__("jp_digital_check_with_capture")}
            <input id="elm_with_capture_no" class="radio" type="radio" value="0" name="payment_data[processor_params][with_capture]" {if $processor_params.with_capture == "0"} checked="checked"{/if}/> {__("jp_digital_check_auth_only")}
        </div>
    </div>

    <div class="control-group">
	    <label class="control-label" for="use_uid">{__('jp_digital_check_use_uid')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][use_uid]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][use_uid]" id="use_uid" value="true" {if $processor_params.use_uid == "true"} checked="checked"{/if} />
        </div>
    </div>
</div>
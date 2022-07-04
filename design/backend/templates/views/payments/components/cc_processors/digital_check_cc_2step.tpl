{* $Id: digital_check_cc_2step.tpl by tommy from cs-cart.jp 2013 *}

<p>{__('jp_digital_check_cc_2step_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_digital_check_connections_settings') target="#text_dc_cc_2step_connection_settings"}

<div id="text_dc_cc_2step_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="ip">{__('jp_digital_check_ip')}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][ip]" id="ip" value="{$processor_params.ip}" class="input-text" size="10" />
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_digital_check_cc_payment_settings') target="#text_dc_cc_2step_payment_settings"}

<div id="text_dc_cc_2step_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="use_cvv">{__('jp_digital_check_cc_use_cvv')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="paymode">{__('jp_cc_method')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][paymode][10]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][10]" id="paymode_10" value="true" {if $processor_params.paymode[10] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_onetime')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][21]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][21]" id="paymode_21" value="true" {if $processor_params.paymode[21] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_digital_check_cc_bonus')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][31]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][31]" id="paymode_31" value="true" {if $processor_params.paymode[31] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_digital_check_cc_bonus_combination')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][61]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][61]" id="paymode_61" value="true" {if $processor_params.paymode[61] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_installment')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][80]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][80]" id="paymode_80" value="true" {if $processor_params.paymode[80] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_revo')}</span>
        </div>
    </div>

    <div class="control-group">
    	<label class="control-label" for="incount">{__('jp_cc_installment_times')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][incount][2]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][2]" id="incount_2" value="true" {if $processor_params.incount[2] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">2{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][3]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][3]" id="incount_3" value="true" {if $processor_params.incount[3] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">3{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][5]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][5]" id="incount_5" value="true" {if $processor_params.incount[5] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">5{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][6]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][6]" id="incount_6" value="true" {if $processor_params.incount[6] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">6{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][10]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][10]" id="incount_10" value="true" {if $processor_params.incount[10] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">10{__('jp_paytimes_unit')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][incount][12]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][12]" id="incount_12" value="true" {if $processor_params.incount[12] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">12{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][15]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][15]" id="incount_15" value="true" {if $processor_params.incount[15] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">15{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][18]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][18]" id="incount_18" value="true" {if $processor_params.incount[18] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">18{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][20]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][20]" id="incount_20" value="true" {if $processor_params.incount[20] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">20{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][24]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][24]" id="incount_24" value="true" {if $processor_params.incount[24] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">24{__('jp_paytimes_unit')}</span>
        </div>
    </div>

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
            <input type="checkbox" name="payment_data[processor_params][use_uid]" id="use_uid" value="true" {if $processor_params.use_uid == "true"} checked="checked"{/if} /
        </div>
    </div>
</div>
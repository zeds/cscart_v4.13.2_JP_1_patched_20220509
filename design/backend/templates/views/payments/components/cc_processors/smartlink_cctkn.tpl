{* $Id: smartlink_cctkn.tpl by takahashi from cs-cart.jp 2017 *}

<p>{__('jp_sln_cc_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_sln_connections_settings') target="#text_sln_cc_connection_settings"}

<div id="text_sln_cc_connection_settings" class="in collapse">
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
        <label class="control-label" for="token_ninsyo_code">{__("jp_sln_token_ninsyocode")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][token_ninsyo_code]" id="token_ninsyo_code" value="{$processor_params.token_ninsyo_code}"  size="60">
        </div>
    </div>

</div>

{include file="common/subheader.tpl" title=__('jp_sln_cc_payment_settings') target="#text_sln_cc_payment_settings"}

<div id="text_sln_cc_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="use_cvv">{__('jp_sln_cc_use_cvv')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="paymode">{__('jp_cc_method')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][paymode][10]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][10]" id="paymode_10" value="true" {if $processor_params.paymode['10'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_onetime')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][61]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][61]" id="paymode_61" value="true" {if $processor_params.paymode['61'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_installment')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][80]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][80]" id="paymode_80" value="true" {if $processor_params.paymode['80'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_sln_cc_bonus')}</span>
            <input type="hidden" name="payment_data[processor_params][paymode][88]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][paymode][88]" id="paymode_88" value="true" {if $processor_params.paymode['88'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cc_revo')}</span>
        </div>
    </div>

    <div class="control-group">
    	<label class="control-label" for="incount">{__('jp_cc_installment_times')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][incount][02]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][02]" id="incount_2" value="true" {if $processor_params.incount['02'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">2{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][03]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][03]" id="incount_3" value="true" {if $processor_params.incount['03'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">3{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][04]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][04]" id="incount_4" value="true" {if $processor_params.incount['04'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">4{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][05]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][05]" id="incount_5" value="true" {if $processor_params.incount['05'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">5{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][06]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][06]" id="incount_6" value="true" {if $processor_params.incount['06'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">6{__('jp_paytimes_unit')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][incount][07]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][07]" id="incount_7" value="true" {if $processor_params.incount['07'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">7{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][08]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][08]" id="incount_8" value="true" {if $processor_params.incount['08'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">8{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][09]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][09]" id="incount_9" value="true" {if $processor_params.incount['09'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">9{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][10]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][10]" id="incount_10" value="true" {if $processor_params.incount['10'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">10{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][11]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][11]" id="incount_11" value="true" {if $processor_params.incount['11'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">11{__('jp_paytimes_unit')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][incount][12]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][12]" id="incount_12" value="true" {if $processor_params.incount['12'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">12{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][15]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][15]" id="incount_15" value="true" {if $processor_params.incount['15'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">15{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][16]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][16]" id="incount_16" value="true" {if $processor_params.incount['16'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">16{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][18]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][18]" id="incount_18" value="true" {if $processor_params.incount['18'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">18{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][20]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][20]" id="incount_20" value="true" {if $processor_params.incount['20'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">20{__('jp_paytimes_unit')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][incount][24]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][24]" id="incount_24" value="true" {if $processor_params.incount['24'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">24{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][30]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][30]" id="incount_30" value="true" {if $processor_params.incount['30'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">30{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][36]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][36]" id="incount_36" value="true" {if $processor_params.incount['36'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">36{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][48]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][48]" id="incount_48" value="true" {if $processor_params.incount['48'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">48{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][54]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][54]" id="incount_54" value="true" {if $processor_params.incount['54'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">54{__('jp_paytimes_unit')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][incount][60]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][60]" id="incount_60" value="true" {if $processor_params.incount['60'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">60{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][72]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][72]" id="incount_72" value="true" {if $processor_params.incount['72'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">72{__('jp_paytimes_unit')}</span>
            <input type="hidden" name="payment_data[processor_params][incount][84]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][incount][84]" id="incount_84" value="true" {if $processor_params.incount['84'] == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">84{__('jp_paytimes_unit')}</span>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="process_type">{__("jp_sln_cc_process_type")}:</label>
        <div class="controls">
            <input id="elm_process_type_capture" class="radio" type="radio" value="capture" name="payment_data[processor_params][process_type]" {if $processor_params.process_type == "capture" || !$processor_params.process_type} checked="checked"{/if}/> {__("jp_sln_cc_gathering")}
            <input id="elm_process_type_auth" class="radio" type="radio" value="auth_only" name="payment_data[processor_params][process_type]" {if $processor_params.process_type == "auth_only"} checked="checked"{/if}/> {__("jp_sln_cc_auth_only")}
        </div>
    </div>

    <div class="control-group">
	    <label class="control-label" for="register_card_info">{__('jp_sln_cc_register_card_info')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][register_card_info]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][register_card_info]" id="register_card_info" value="true" {if $processor_params.register_card_info == "true"} checked="checked"{/if} />
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_sln_3dsecure_settings') target="#text_sln_dsecure_settings"}

<div id="text_sln_dsecure_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="tdflag">{__("jp_sln_use_3dsecure")}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][tdflag]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][tdflag]" id="tdflag" value="true" {if $processor_params.tdflag == "true"} checked="checked"{/if} />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="3dsecure_aes_key">{__("jp_sln_3dsecure_aes_key")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][3dsecure_aes_key]" id="3dsecure_aes_key" value="{$processor_params.3dsecure_aes_key}"  size="60">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="3dsecure_aes_key">{__("jp_sln_3dsecure_aes_iv")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][3dsecure_aes_iv]" id="3dsecure_aes_iv" value="{$processor_params.3dsecure_aes_iv}"  size="60">
        </div>
    </div>
</div>
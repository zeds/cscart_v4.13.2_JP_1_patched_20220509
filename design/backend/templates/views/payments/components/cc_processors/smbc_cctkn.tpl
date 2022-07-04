{* $Id: smbc_cctkn.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_smbc_cc_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_smbc_connections_settings") target="#smbc_cc_connection_settings"}
<div id="smbc_cc_connection_settings" class="in collapse">
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

        <div class="control-group">
            <label class="control-label" for="api_key">{__("jp_smbc_api_key")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][api_key]" id="token_ninsyo_code" value="{$processor_params.api_key}"  size="60">
            </div>
        </div>

    </fieldset>
</div>

{include file="common/subheader.tpl" title=__("jp_smbc_cc_payment_settings") target="#smbc_cc_payment_settings"}
<div id="smbc_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="use_cvv">{__("jp_smbc_cc_use_cvv")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="shiharai_kbn">{__("jp_cc_method")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][shiharai_kbn][1]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][shiharai_kbn][1]" id="shiharai_kbn_1" value="true" {if $processor_params.shiharai_kbn[1] == "true"} checked="checked"{/if} /> {__("jp_cc_onetime")}
                <input type="hidden" name="payment_data[processor_params][shiharai_kbn][91]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][shiharai_kbn][91]" id="shiharai_kbn_91" value="true" {if $processor_params.shiharai_kbn[91] == "true"} checked="checked"{/if} /> {__("jp_smbc_cc_bonus")}
                <input type="hidden" name="payment_data[processor_params][shiharai_kbn][61]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][shiharai_kbn][61]" id="shiharai_kbn_61" value="true" {if $processor_params.shiharai_kbn[61] == "true"} checked="checked"{/if} /> {__("jp_cc_installment")}
                <input type="hidden" name="payment_data[processor_params][shiharai_kbn][80]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][shiharai_kbn][80]" id="shiharai_kbn_80" value="true" {if $processor_params.shiharai_kbn[80] == "true"} checked="checked"{/if} /> {__("jp_cc_revo")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="paycount">{__("jp_cc_installment_times")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][paycount][2]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][2]" id="paycount_2" value="true" {if $processor_params.paycount[2] == "true"} checked="checked"{/if} /> 2{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][3]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][3]" id="paycount_3" value="true" {if $processor_params.paycount[3] == "true"} checked="checked"{/if} /> 3{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][5]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][5]" id="paycount_5" value="true" {if $processor_params.paycount[5] == "true"} checked="checked"{/if} /> 5{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][6]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][6]" id="paycount_6" value="true" {if $processor_params.paycount[6] == "true"} checked="checked"{/if} /> 6{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][10]" id="paycount_10" value="true" {if $processor_params.paycount[10] == "true"} checked="checked"{/if} /> 10{__("jp_paytimes_unit")}
                <br />
                <input type="hidden" name="payment_data[processor_params][paycount][12]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][12]" id="paycount_12" value="true" {if $processor_params.paycount[12] == "true"} checked="checked"{/if} /> 12{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][15]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][15]" id="paycount_15" value="true" {if $processor_params.paycount[15] == "true"} checked="checked"{/if} /> 15{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][18]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][18]" id="paycount_18" value="true" {if $processor_params.paycount[18] == "true"} checked="checked"{/if} /> 18{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][20]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][20]" id="paycount_20" value="true" {if $processor_params.paycount[20] == "true"} checked="checked"{/if} /> 20{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paycount][24]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paycount][24]" id="paycount_24" value="true" {if $processor_params.paycount[24] == "true"} checked="checked"{/if} /> 24{__("jp_paytimes_unit")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="register_card_info">{__("jp_smbc_cc_register_card_info")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][register_card_info]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][register_card_info]" id="register_card_info" value="true" {if $processor_params.register_card_info == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>

{* $Id: nttstr_cc.tpl by takahashi from cs-cart.jp 2019 *}

<p>{__("jp_nttstr_cc_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_nttstr_connections_settings") target="#nttstr_cc_connection_settings"}
<div id="nttstr_cc_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_nttstr_cc_payment_settings") target="#nttstr_cc_payment_settings"}
<div id="nttstr_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_nttstr_jobcd")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][jobcd]" id="mode">
                    <option value="50" {if $processor_params.jobcd == "50"}selected="selected"{/if}>{__("jp_nttstr_auth")}</option>
                    <option value="52" {if $processor_params.jobcd == "52"}selected="selected"{/if}>{__("jp_nttstr_capture")}</option>
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="nttstr_method">{__("jp_nttstr_cc_method")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][nttstr_method][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][nttstr_method][10]" id="nttstr_method_10" value="true" {if $processor_params.nttstr_method[10] == "true"} checked="checked"{/if} /> {__("jp_nttstr_cc_onetime")}
                <input type="hidden" name="payment_data[processor_params][nttstr_method][21]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][nttstr_method][21]" id="nttstr_method_21" value="true" {if $processor_params.nttstr_method[21] == "true"} checked="checked"{/if} /> {__("jp_nttstr_cc_bonus")}
                <input type="hidden" name="payment_data[processor_params][nttstr_method][61]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][nttstr_method][61]" id="nttstr_method_61" value="true" {if $processor_params.nttstr_method[61] == "true"} checked="checked"{/if} /> {__("jp_nttstr_cc_installment")}
                <input type="hidden" name="payment_data[processor_params][nttstr_method][80]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][nttstr_method][80]" id="nttstr_method_80" value="true" {if $processor_params.nttstr_method[80] == "true"} checked="checked"{/if} /> {__("jp_nttstr_cc_revo")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="paytimes">{__("jp_nttstr_cc_installment_times")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][paytimes][2]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][2]" id="paytimes_2" value="true" {if $processor_params.paytimes[2] == "true"} checked="checked"{/if} /> 2{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][3]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][3]" id="paytimes_3" value="true" {if $processor_params.paytimes[3] == "true"} checked="checked"{/if} /> 3{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][5]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][5]" id="paytimes_5" value="true" {if $processor_params.paytimes[5] == "true"} checked="checked"{/if} /> 5{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][6]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][6]" id="paytimes_6" value="true" {if $processor_params.paytimes[6] == "true"} checked="checked"{/if} /> 6{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][10]" id="paytimes_10" value="true" {if $processor_params.paytimes[10] == "true"} checked="checked"{/if} /> 10{__("jp_paytimes_unit")}
                <br />
                <input type="hidden" name="payment_data[processor_params][paytimes][12]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][12]" id="paytimes_12" value="true" {if $processor_params.paytimes[12] == "true"} checked="checked"{/if} /> 12{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][15]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][15]" id="paytimes_15" value="true" {if $processor_params.paytimes[15] == "true"} checked="checked"{/if} /> 15{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][18]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][18]" id="paytimes_18" value="true" {if $processor_params.paytimes[18] == "true"} checked="checked"{/if} /> 18{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][20]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][20]" id="paytimes_20" value="true" {if $processor_params.paytimes[20] == "true"} checked="checked"{/if} /> 20{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][paytimes][24]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paytimes][24]" id="paytimes_24" value="true" {if $processor_params.paytimes[24] == "true"} checked="checked"{/if} /> 24{__("jp_paytimes_unit")}
            </div>
        </div>
    </fieldset>
</div>

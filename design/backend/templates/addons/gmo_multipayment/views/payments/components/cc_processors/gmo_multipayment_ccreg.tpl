{* $Id: gmo_multipayment_ccreg.tpl by tommy from cs-cart.jp 2015 *}

<p>{__("jp_gmo_multipayment_ccreg_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_gmo_multipayment_connections_settings") target="#gmomp_cc_connection_settings"}
<div id="gmomp_cc_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_gmo_multipayment_cc_payment_settings") target="#gmomp_cc_payment_settings"}
<div id="gmomp_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="use_cvv">{__("jp_gmo_multipayment_cc_use_cvv")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gmomp_method">{__("jp_cc_method")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][gmomp_method][1]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][gmomp_method][1]" id="gmomp_method_1" value="true" {if $processor_params.gmomp_method[1] == "true"} checked="checked"{/if} /> {__("jp_cc_onetime")}
                <input type="hidden" name="payment_data[processor_params][gmomp_method][2]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][gmomp_method][2]" id="gmomp_method_2" value="true" {if $processor_params.gmomp_method[2] == "true"} checked="checked"{/if} /> {__("jp_cc_installment")}
                <input type="hidden" name="payment_data[processor_params][gmomp_method][3]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][gmomp_method][3]" id="gmomp_method_3" value="true" {if $processor_params.gmomp_method[3] == "true"} checked="checked"{/if} /> {__("jp_gmo_multipayment_cc_bonus")}
                <input type="hidden" name="payment_data[processor_params][gmomp_method][5]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][gmomp_method][5]" id="gmomp_method_5" value="true" {if $processor_params.gmomp_method[5] == "true"} checked="checked"{/if} /> {__("jp_cc_revo")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="paytimes">{__("jp_cc_installment_times")}:</label>
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
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_gmo_multipayment_jobcd")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][jobcd]" id="mode">
                    <option value="AUTH" {if $processor_params.jobcd == "AUTH"}selected="selected"{/if}>{__("jp_gmo_multipayment_auth")}</option>
                    <option value="CAPTURE" {if $processor_params.jobcd == "CAPTURE"}selected="selected"{/if}>{__("jp_gmo_multipayment_capture")}</option>
                </select>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="tdflag">{__("jp_gmo_multipayment_use_3dsecure")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][tdflag]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][tdflag]" id="tdflag" value="true" {if $processor_params.tdflag == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>

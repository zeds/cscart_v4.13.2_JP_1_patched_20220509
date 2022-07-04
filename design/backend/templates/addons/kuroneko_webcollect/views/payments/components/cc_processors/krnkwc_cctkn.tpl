{* $Id: krnkwc_cctkn.tpl by takahashi from cs-cart.jp 2017 *}

<p>{__("jp_kuroneko_webcollect_cc_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_kuroneko_webcollect_connections_settings") target="#krnkwc_cc_connection_settings"}
<div id="krnkwc_cc_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_kuroneko_webcollect_cc_payment_settings") target="#krnkwc_cc_payment_settings"}
<div id="krnkwc_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="pay_way">{__("jp_cc_method")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][pay_way][1]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pay_way][1]" id="pay_way_1" value="true" {if $processor_params.pay_way[1] == "true"} checked="checked"{/if} /> {__("jp_cc_onetime")}
                <input type="hidden" name="payment_data[processor_params][pay_way][2]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pay_way][2]" id="pay_way_2" value="true" {if $processor_params.pay_way[2] == "true"} checked="checked"{/if} /> {__("jp_cc_installment")}
                <input type="hidden" name="payment_data[processor_params][pay_way][0]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pay_way][0]" id="pay_way_0" value="true" {if $processor_params.pay_way[0] == "true"} checked="checked"{/if} /> {__("jp_cc_revo")}
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
            <label class="control-label" for="use_member_id">{__('jp_kuroneko_webcollect_cc_register_card_info')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_member_id]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_member_id]" id="use_member_id" value="true" {if $processor_params.use_member_id == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="tdflag">{__("jp_kuroneko_webcollect_use_3dsecure")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][tdflag]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][tdflag]" id="tdflag" value="true" {if $processor_params.tdflag == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>

{include file="common/subheader.tpl" title=__('jp_kuroneko_webcollect_wc_cron_setting') target="#krnkwc_cron_settings"}
<div id="krnkwc_cron_settings" class="in collapse">
    <div class="control-group">{'wc'|fn_krnkwc_get_cron_command}</div>
</div>

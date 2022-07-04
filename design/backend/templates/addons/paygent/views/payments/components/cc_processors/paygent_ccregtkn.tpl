{* $Id: paygent_ccregtkn.tpl by tommy from cs-cart.jp 2016 *}

<p>{__("jp_paygent_ccreg_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_paygent_connections_settings") target="#paygent_cc_connection_settings"}
<div id="paygent_cc_connection_settings" class="in collapse">
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
            <label class="control-label" for="token_key">{__("jp_paygent_tokey_key")}:</label>
            <div class="controls">
                <input size="35" type="text" name="payment_data[processor_params][token_key]" id="token_key" value="{$processor_params.token_key}"/>
            </div>
        </div>
    </fieldset>
</div>

{include file="common/subheader.tpl" title=__("jp_paygent_ccreg_payment_settings") target="#pygnt_payment_settings"}
<div id="pygnt_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="use_cvv">{__("jp_paygent_use_cvv")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="pygnt_method">{__("jp_cc_method")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][pygnt_method][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pygnt_method][10]" id="pygnt_method_10" value="true" {if $processor_params.pygnt_method[10] == "true"} checked="checked"{/if} /> {__("jp_cc_onetime")}
                <input type="hidden" name="payment_data[processor_params][pygnt_method][61]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pygnt_method][61]" id="pygnt_method_61" value="true" {if $processor_params.pygnt_method[61] == "true"} checked="checked"{/if} /> {__("jp_cc_installment")}
                <input type="hidden" name="payment_data[processor_params][pygnt_method][23]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pygnt_method][23]" id="pygnt_method_23" value="true" {if $processor_params.pygnt_method[23] == "true"} checked="checked"{/if} /> {__("jp_paygent_cc_bonus")}
                <input type="hidden" name="payment_data[processor_params][pygnt_method][80]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][pygnt_method][80]" id="pygnt_method_80" value="true" {if $processor_params.pygnt_method[80] == "true"} checked="checked"{/if} /> {__("jp_cc_revo")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="split_count">{__("jp_cc_installment_times")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][split_count][2]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][2]" id="split_count_2" value="true" {if $processor_params.split_count[2] == "true"} checked="checked"{/if} /> 2{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][3]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][3]" id="split_count_3" value="true" {if $processor_params.split_count[3] == "true"} checked="checked"{/if} /> 3{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][4]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][4]" id="split_count_4" value="true" {if $processor_params.split_count[4] == "true"} checked="checked"{/if} /> 4{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][5]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][5]" id="split_count_5" value="true" {if $processor_params.split_count[5] == "true"} checked="checked"{/if} /> 5{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][6]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][6]" id="split_count_6" value="true" {if $processor_params.split_count[6] == "true"} checked="checked"{/if} /> 6{__("jp_paytimes_unit")}
                <br />
                <input type="hidden" name="payment_data[processor_params][split_count][8]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][8]" id="split_count_8" value="true" {if $processor_params.split_count[8] == "true"} checked="checked"{/if} /> 8{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][10]" id="split_count_10" value="true" {if $processor_params.split_count[10] == "true"} checked="checked"{/if} /> 10{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][12]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][12]" id="split_count_12" value="true" {if $processor_params.split_count[12] == "true"} checked="checked"{/if} /> 12{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][14]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][14]" id="split_count_14" value="true" {if $processor_params.split_count[14] == "true"} checked="checked"{/if} /> 14{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][15]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][15]" id="split_count_15" value="true" {if $processor_params.split_count[15] == "true"} checked="checked"{/if} /> 15{__("jp_paytimes_unit")}
                <br />
                <input type="hidden" name="payment_data[processor_params][split_count][16]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][16]" id="split_count_16" value="true" {if $processor_params.split_count[16] == "true"} checked="checked"{/if} /> 16{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][18]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][18]" id="split_count_18" value="true" {if $processor_params.split_count[18] == "true"} checked="checked"{/if} /> 18{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][20]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][20]" id="split_count_20" value="true" {if $processor_params.split_count[20] == "true"} checked="checked"{/if} /> 20{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][22]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][22]" id="split_count_22" value="true" {if $processor_params.split_count[22] == "true"} checked="checked"{/if} /> 22{__("jp_paytimes_unit")}
                <input type="hidden" name="payment_data[processor_params][split_count][24]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][split_count][24]" id="split_count_24" value="true" {if $processor_params.split_count[24] == "true"} checked="checked"{/if} /> 24{__("jp_paytimes_unit")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="3dsecure">{__("jp_paygent_use_3dsecure")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][3dsecure]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][3dsecure]" id="3dsecure" value="true" {if $processor_params.3dsecure == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>

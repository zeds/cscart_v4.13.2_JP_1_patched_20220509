{* $Id: smbc_ps.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_smbc_ps_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_smbc_connections_settings") target="#smbc_ps_connection_settings"}
<div id="smbc_ps_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_smbc_cc_payment_settings") target="#smbc_ps_payment_settings"}
<div id="smbc_ps_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="hakkou_kbn">{__("jp_smbc_hakkou_kbn")}:</label>
            <div class="controls">
                <input id="elm_hakkou_kbn_self" class="radio" type="radio" value="1" name="payment_data[processor_params][hakkou_kbn]" {if $processor_params.hakkou_kbn == "1" || !$processor_params.hakkou_kbn} checked="checked"{/if}/> {__("jp_smbc_issue_self")}
                <input id="elm_hakkou_kbn_3p" class="radio" type="radio" value="2" name="payment_data[processor_params][hakkou_kbn]" {if $processor_params.hakkou_kbn == "2"} checked="checked"{/if}/> {__("jp_smbc_issue_3p")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="yuusousaki_kbn">{__("jp_smbc_yuusousaki_kbn")}:</label>
            <div class="controls">
                <input id="elm_yuusousaki_kbn_self" class="radio" type="radio" value="1" name="payment_data[processor_params][yuusousaki_kbn]" {if $processor_params.yuusousaki_kbn == "1" || !$processor_params.yuusousaki_kbn} checked="checked"{/if}/> {__("jp_smbc_send_self")}
                <input id="elm_yuusousaki_kbn_customer" class="radio" type="radio" value="2" name="payment_data[processor_params][yuusousaki_kbn]" {if $processor_params.yuusousaki_kbn == "2"} checked="checked"{/if}/> {__("jp_smbc_send_customer")}
            </div>
        </div>
    </fieldset>
</div>

{* $Id: payjp_cc.tpl by takahashi from cs-cart.jp 2020 *}

<p>{__("jp_payjp_cc_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_payjp_cc_payment_settings") target="#payjp_cc_payment_settings"}
<div id="payjp_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_payjp_jobcd")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][jobcd]" id="mode">
                    <option value="AUTH" {if $processor_params.jobcd == "AUTH"}selected="selected"{/if}>{__("jp_payjp_auth")}</option>
                    <option value="CAPTURE" {if $processor_params.jobcd == "CAPTURE"}selected="selected"{/if}>{__("jp_payjp_capture")}</option>
                </select>
            </div>
        </div>
    </fieldset>
</div>

{* $Id: omise_ccreg.tpl by takahashi from cs-cart.jp 2017 *}

<p>{__("jp_omise_ccreg_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_omise_cc_payment_settings") target="#omise_cc_payment_settings"}
<div id="omise_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_omise_jobcd")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][jobcd]" id="mode">
                    <option value="AUTH" {if $processor_params.jobcd == "AUTH"}selected="selected"{/if}>{__("jp_omise_auth")}</option>
                    <option value="CAPTURE" {if $processor_params.jobcd == "CAPTURE"}selected="selected"{/if}>{__("jp_omise_capture")}</option>
                </select>
            </div>
        </div>

    </fieldset>
</div>

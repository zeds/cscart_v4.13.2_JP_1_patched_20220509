{* $Id: sonypayment_carrier_rb.tpl by takahashi from cs-cart.jp 2018 *}

<p>{__("jp_sonypayment_carrier_rb_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_sonypayment_carrier_connections_settings") target="#sonypayment_carrier_rb_connection_settings"}
<div id="sonypayment_carrier_rb_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_sonypayment_carrier_rb_payment_settings") target="#sonypayment_carrier_rb_payment_settings"}
<div id="sonypayment_carrier_rb_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="crkind">{__("jp_sonypayment_carrier_crkind")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][carrier_rb][01]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][carrier_rb][01]" id="carrier_01" value="true" {if $processor_params.carrier_rb['01'] == "true"} checked="checked"{/if} /> {__("jp_sonypayment_carrier_docomo")}
                <br />
                <input type="hidden" name="payment_data[processor_params][carrier_rb][02]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][carrier_rb][02]" id="carrier_02" value="true" {if $processor_params.carrier_rb['02'] == "true"} checked="checked"{/if} /> {__("jp_sonypayment_carrier_au")}
                <br />
                <input type="hidden" name="payment_data[processor_params][carrier_rb][03]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][carrier_rb][03]" id="carrier_03" value="true" {if $processor_params.carrier_rb['03'] == "true"} checked="checked"{/if} /> {__("jp_sonypayment_carrier_softbank")}
                <br />
            </div>
        </div>
    </fieldset>
</div>
{* $Id: credix_cc.tpl by tommy from cs-cart.jp 2014 *}

<p>{__("jp_credix_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_credix_payment_settings") target="#credix_payment_settings"}
<div id="credix_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="quick_charge">{__("jp_credix_use_quick_charge")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][quick_charge]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][quick_charge]" id="quick_charge" value="true" {if $processor_params.quick_charge == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>

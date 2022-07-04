{* $Id: paygent_mc_cc.tpl by tommy from cs-cart.jp 2016 *}

<p>{__("jp_paygent_mc_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_paygent_mc_payment_settings") target="#pygntmc_payment_settings"}
<div id="pygntmc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="use_cvv">{__("jp_paygent_use_cvv")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="register_card_info">{__('jp_paygent_cc_register_card_info')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][register_card_info]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][register_card_info]" id="register_card_info" value="true" {if $processor_params.register_card_info == "true"} checked="checked"{/if} />
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

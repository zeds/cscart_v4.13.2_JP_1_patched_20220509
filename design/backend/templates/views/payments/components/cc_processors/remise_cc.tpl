{* $Id: remise_cc.tpl by tommy from cs-cart.jp 2013 *}

{assign var="r_url" value="`$config.http_location`/jp_extras/remise_cc/set_result.php"}

<p>{__("jp_text_remise_cc_notice")}</p>
<hr />

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
	<label class="control-label" for="shop_code">{__("jp_remise_shop_code")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][shop_code]" id="shop_code" value="{$processor_params.shop_code}" size="10" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="host_id">{__("jp_remise_host_id")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][host_id]" id="host_id" value="{$processor_params.host_id}" size="10" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="url_production">{__("jp_remise_url_production")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][url_production]" id="url_production" value="{$processor_params.url_production}" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="url_test">{__("jp_remise_url_test")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][url_test]" id="url_test" value="{$processor_params.url_test}" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="installment">{__("jp_payment_installment")}:</label>
    <div class="controls">
	    <input type="hidden" name="payment_data[processor_params][installment]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][installment]" id="installment" value="true" {if $processor_params.installment == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="revo">{__("jp_cc_revo")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][revo]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][revo]" id="revo" value="true" {if $processor_params.revo == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="payquick">{__("jp_remise_payquick")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][payquick]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][payquick]" id="payquick" value="true" {if $processor_params.payquick == "true"} checked="checked"{/if} />
	<br />{__("jp_remise_result_url_notice")|replace:"[result_url]":$r_url}
    </div>
</div>

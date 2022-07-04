{* $Id: epsilon.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_text_epsilon_notice")}</p>
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
	<label class="control-label" for="contract_code">{__("jp_epsilon_contract_code")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][contract_code]" id="contract_code" value="{$processor_params.contract_code}" size="8" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="url_production">{__("jp_epsilon_url_production")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][url_production]" id="url_production" value="{$processor_params.url_production}" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="url_test">{__("jp_epsilon_url_test")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][url_test]" id="url_test" value="{$processor_params.url_test}" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="order_url_production">{__("jp_epsilon_order_url_production")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_url_production]" id="order_url_production" value="{$processor_params.order_url_production}" />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="order_url_test">{__("jp_epsilon_order_url_test")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][order_url_test]" id="order_url_test" value="{$processor_params.order_url_test}" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="cc">{__("jp_payment_cc")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][cc]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][cc]" id="cc" value="true" {if $processor_params.cc == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="cvs">{__("jp_payment_cvs")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][cvs]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][cvs]" id="cvs" value="true" {if $processor_params.cvs == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="jnb">{__("jp_payment_jnb")}:</label>
    <div class="controls">
	    <input type="hidden" name="payment_data[processor_params][jnb]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][jnb]" id="jnb" value="true" {if $processor_params.jnb == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="rakutenbank">{__("jp_payment_rakutenbank")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][rakutenbank]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][rakutenbank]" id="rakutenbank" value="true" {if $processor_params.rakutenbank == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="pez">{__("jp_payment_pez")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][pez]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][pez]" id="pez" value="true" {if $processor_params.pez == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="wm">{__("jp_payment_webmoney")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][wm]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][wm]" id="wm" value="true" {if $processor_params.wm == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="bitcash">{__("jp_payment_bitcash")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][bitcash]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][bitcash]" id="bitcash" value="true" {if $processor_params.bitcash == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="chocom">{__("jp_payment_chocom")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][chocom]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][chocom]" id="chocom" value="true" {if $processor_params.chocom == "true"} checked="checked"{/if} />
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="paypal">{__("jp_payment_paypal")}:</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][paypal]" value="false" />
	    <input type="checkbox" name="payment_data[processor_params][paypal]" id="paypal" value="true" {if $processor_params.paypal == "true"} checked="checked"{/if} />
    </div>
</div>

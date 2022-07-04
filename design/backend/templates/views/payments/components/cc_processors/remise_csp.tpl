{* $Id: remise_csp.tpl by tommy from cs-cart.jp 2013 *}

{assign var="notify_url" value="`$config.http_location`/jp_extras/remise_csp/csp.php"}

<p>{__("jp_text_remise_csp_notice")}</p>
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
	<label class="control-label" for="mode">{__("jp_remise_s_paydate")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][s_paydate]" id="s_paydate">
            {section name=cnt start=1 loop=31}
            <option value="{$smarty.section.cnt.index}" {if $processor_params.s_paydate == $smarty.section.cnt.index}selected="selected"{/if}>{$smarty.section.cnt.index}</option>
            {/section}
        </select>
    </div>
</div>

<div class="control-group">
	<label class="control-label" for="mode">{__("jp_remise_csp_notify_url")}:</label>
    <div class="controls">
        {__("jp_remise_csp_notify_url_notice")|replace:"[notify_url]":$notify_url}
    </div>
</div>

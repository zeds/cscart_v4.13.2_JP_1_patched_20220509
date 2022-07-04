{* $Id: zeus.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_zeus_notice")}</p>
<hr />

<div class="control-group">
    <label class="control-label" for="clientip">{__("jp_zeus_clientip")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][clientip]" id="clientip" value="{$processor_params.clientip}" size="12" />
    </div>
</div>
{* $Id: telecomcredit.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_text_telecomcredit_notice")}</p>
<hr />

<div class="control-group">
    <label class="control-label" for="clientip">{__("jp_telecomcredit_clientip")}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][clientip]" id="clientip" value="{$processor_params.clientip}" class="input-text" size="5" />
    </div>
</div>

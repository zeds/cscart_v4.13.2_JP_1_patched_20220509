{* $Id: digital_check_medy.tpl by tommy from cs-cart.jp 2013 *}

<p>{__('jp_digital_check_medy_notice')}</p>
<hr />

<div class="control-group">
	<label class="control-label" for="ip">{__('jp_digital_check_ip')}:</label>
    <div class="controls">
	    <input type="text" name="payment_data[processor_params][ip]" id="ip" value="{$processor_params.ip}" class="input-text" size="10" />
    </div>
</div>
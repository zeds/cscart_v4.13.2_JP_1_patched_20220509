{* $Id: update_fields.tpl by takahashi from cs-cart.jp 2018 *}

<div class="form-field">
	<label for="sonypayment_carrier_rb_use_rbdate">{__("jp_sonypayment_carrier_rb_use_rbdate")}:</label>
	<input type="hidden" name="{$prefix}[sonypayment_carrier_rb_use_rbdate]" value="N" /><input type="checkbox" id="sonypayment_carrier_rb_use_rbdate" name="{$prefix}[sonypayment_carrier_rb_use_rbdate]" value="Y" {if $sonypayment_carrier_rb_product.use_rbdate == "Y"}checked="checked"{/if} class="checkbox" />
</div>

<div class="form-field">
	<label for="sonyc_rb_first_payment_day">{__("jp_sonypayment_carrier_rb_first_payment_day")}:</label>
    <select name="product_data[sonyc_rb_first_payment_day]" id="sonyc_rb_first_payment_day">
        <option {if $sonypayment_carrier_rb_product.payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_registered_day')})</option>
        {for $day=1 to 28}
            <option {if $sonypayment_carrier_rb_product.payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')}</option>
        {/for}
        <option {if $sonypayment_carrier_rb_product.payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
    </select>
</div>

<div class="form-field">
	<label for="sonypayment_carrier_rb_payment_day">{__("jp_sonypayment_carrier_rb_payment_day")}:</label>
	<select name="{$prefix}[sonypayment_carrier_rb_payment_day]" id="sonypayment_carrier_rb_payment_day">
		<option {if $sonypayment_carrier_rb_product.payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_carrier_day')})</option>
		{for $day=1 to 28}
			<option {if $sonypayment_carrier_rb_product.payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')}</option>
		{/for}
		<option {if $sonypayment_carrier_rb_product.payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
	</select>
</div>

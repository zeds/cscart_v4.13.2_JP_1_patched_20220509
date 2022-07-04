{* $Id: update_fields.tpl by tommy from cs-cart.jp 2013 *}

<div class="form-field">
	<label for="smbc_rb_enable_1st_payment">{__("jp_smbc_rb_enable_1st_payment")}:</label>
	<input type="hidden" name="{$prefix}[smbc_rb_enable_1st_payment]" value="N" /><input type="checkbox" id="smbc_rb_enable_1st_payment" name="{$prefix}[smbc_rb_enable_1st_payment]" value="Y" {if $smbc_rb_product.enable_1st_payment == "Y"}checked="checked"{/if} class="checkbox" />
</div>

<div class="form-field">
	<label for="smbc_rb_1st_payment_amount">{__("jp_smbc_rb_seikyuu_kingaku1")} ({$currencies.$primary_currency.symbol}) :</label>
	<input type="text" id="smbc_rb_1st_payment_amount" name="{$prefix}[smbc_rb_1st_payment_amount]" size="10" value="{$smbc_rb_product.first_payment_amount|default:"0"}" class="input-text-medium" />
</div>

<hr />

<div class="form-field">
	<label for="smbc_rb_charge_timing">{__("jp_smbc_rb_charge_timing")}:</label>
	<select name="{$prefix}[smbc_rb_charge_timing]" id="smbc_rb_charge_timing">
		<option {if $smbc_rb_product.charge_timing == 0 || !$smbc_rb_product.charge_timing}selected="selected"{/if} value=0>{__("jp_smbc_rb_this_month")}</option>
		<option {if $smbc_rb_product.charge_timing == 1}selected="selected"{/if} value=1>{__("jp_smbc_rb_next_month")}</option>
		<option {if $smbc_rb_product.charge_timing == 2}selected="selected"{/if} value=2>{__("jp_smbc_rb_2month_later")}</option>
		<option {if $smbc_rb_product.charge_timing == 3}selected="selected"{/if} value=3>{__("jp_smbc_rb_3month_later")}</option>
	</select>
</div>

<hr />

<div class="form-field">
	<label for="smbc_rb_duration_type">{__("jp_smbc_rb_seikyuu_hoho")}:</label>
	<select name="{$prefix}[smbc_rb_duration_type]" id="smbc_rb_duration_type">
		<option {if $smbc_rb_product.duration_type == 1 || !$smbc_rb_product.duration_type}selected="selected"{/if} value=1>{__("jp_smbc_rb_monthly")}</option>
		<option {if $smbc_rb_product.duration_type == 2}selected="selected"{/if} value=2>{__("jp_smbc_rb_yearly")}</option>
	</select>
</div>

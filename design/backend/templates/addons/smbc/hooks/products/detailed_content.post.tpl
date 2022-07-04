{* $Id: detailed_content.post.tpl by tommy from cs-cart.jp 2013 *}

{if $addons.subscription_payment_jp.status == 'A'}
{include file="common/subheader.tpl" title=__("jp_smbc_rb_payment") target="#acc_jp_smbc_rb_payment"}

<div id="acc_jp_smbc_rb_payment" class="in collapse">

    <div class="control-group">
        <label class="control-label" for="smbc_rb_enable_1st_payment">{__("jp_smbc_rb_enable_1st_payment")}:</label>
        <div class="controls">
            <input type="hidden" name="product_data[smbc_rb_enable_1st_payment]" value="N" /><input type="checkbox" id="smbc_rb_enable_1st_payment" name="product_data[smbc_rb_enable_1st_payment]" value="Y" {if $smbc_rb_product.enable_1st_payment == "Y"}checked="checked"{/if} class="checkbox" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="smbc_rb_1st_payment_amount">{__("jp_smbc_rb_seikyuu_kingaku1")} ({$currencies.$primary_currency.symbol}) :</label>
        <div class="controls">
            <input type="text" id="smbc_rb_1st_payment_amount" name="product_data[smbc_rb_1st_payment_amount]" size="10" value="{$smbc_rb_product.first_payment_amount|default:"0"}" class="input-text-medium" />
        </div>
    </div>

    <hr />

    <div class="control-group">
        <label class="control-label" for="smbc_rb_charge_timing">{__("jp_smbc_rb_charge_timing")}:</label>
        <div class="controls">
            <select name="product_data[smbc_rb_charge_timing]" id="smbc_rb_charge_timing">
                <option {if $smbc_rb_product.charge_timing == 0 || !$smbc_rb_product.charge_timing}selected="selected"{/if} value=0>{__("jp_smbc_rb_this_month")}</option>
                <option {if $smbc_rb_product.charge_timing == 1}selected="selected"{/if} value=1>{__("jp_smbc_rb_next_month")}</option>
                <option {if $smbc_rb_product.charge_timing == 2}selected="selected"{/if} value=2>{__("jp_smbc_rb_2month_later")}</option>
                <option {if $smbc_rb_product.charge_timing == 3}selected="selected"{/if} value=3>{__("jp_smbc_rb_3month_later")}</option>
            </select>
        </div>
    </div>

    <hr />

    <div class="control-group">
        <label class="control-label" for="smbc_rb_duration_type">{__("jp_smbc_rb_seikyuu_hoho")}:</label>
        <div class="controls">
            <select name="product_data[smbc_rb_duration_type]" id="smbc_rb_duration_type">
                <option {if $smbc_rb_product.duration_type == 1 || !$smbc_rb_product.duration_type}selected="selected"{/if} value=1>{__("jp_smbc_rb_monthly")}</option>
                <option {if $smbc_rb_product.duration_type == 2}selected="selected"{/if} value=2>{__("jp_smbc_rb_yearly")}</option>
            </select>
        </div>
    </div>

</div>
{/if}

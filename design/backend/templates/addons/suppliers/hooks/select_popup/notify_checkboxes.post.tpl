{* Modified by tommy from cs-cart.jp 2017 *}
{if $notify && $order_info.have_suppliers}
    {assign var="jp_notify_supplier_checked" value=$addons.localization_jp.jp_notify_supplier_default}
	<li><a><label for="{$prefix}_{$id}_notify_supplier">
        <input type="checkbox" name="__notify_supplier" id="{$prefix}_{$id}_notify_supplier" value="Y" {if $jp_notify_supplier_checked == 'Y'}checked="checked" {/if}onclick="Tygh.$('input[name=__notify_supplier]').prop('checked', this.checked);" />
        {__("notify_supplier")}</label></a>
    </li>
{/if}
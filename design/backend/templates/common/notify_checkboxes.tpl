{* Modified by tommy from cs-cart.jp 2017 *}
{hook name="select_popup:notify_checkboxes"}
    {$name_prefix=$name_prefix|default:"__notify"}

    {if $notify}
        <li class="divider"></li>
        {assign var="jp_notify_customer_checked" value=$addons.localization_jp.jp_notify_customer_default}
        <li><a><label for="{$prefix}_{$id}_notify">
            <input type="checkbox" name="{$name_prefix}_user" id="{$prefix}_{$id}_notify" value="{"YesNo::YES"|enum}" {if $notify_customer_status == true} {if $jp_notify_customer_checked == 'Y'}checked="checked" {/if} {/if} onclick="Tygh.$('input[name={$name_prefix}_user]').prop('checked', this.checked);" />
            {$notify_text|default:__("notify_customer")}</label></a>
        </li>
    {/if}
    {if $notify_department}
        {assign var="jp_notify_admin_checked" value=$addons.localization_jp.jp_notify_admin_default}
        <li><a><label for="{$prefix}_{$id}_notify_department">
            <input type="checkbox" name="{$name_prefix}_department" id="{$prefix}_{$id}_notify_department" value="{"YesNo::YES"|enum}" {if $notify_department_status == true} {if $jp_notify_admin_checked == 'Y'}checked="checked" {/if} {/if} onclick="Tygh.$('input[name={$name_prefix}_department]').prop('checked', this.checked);" />
            {__("notify_orders_department")}</label></a>
        </li>
    {/if}
    {if "MULTIVENDOR"|fn_allowed_for && $notify_vendor}
        {assign var="jp_notify_vendor_checked" value=$addons.localization_jp.jp_notify_vendor_default}
        <li><a><label for="{$prefix}_{$id}_notify_vendor">
            <input type="checkbox" name="{$name_prefix}_vendor" id="{$prefix}_{$id}_notify_vendor" value="{"YesNo::YES"|enum}" {if $notify_vendor_status == true} {if $jp_notify_vendor_checked == 'Y'}checked="checked" {/if} {/if} onclick="Tygh.$('input[name={$name_prefix}_vendor]').prop('checked', this.checked);" />
            {__("notify_vendor")}</label></a>
        </li>
    {/if}            
{/hook}

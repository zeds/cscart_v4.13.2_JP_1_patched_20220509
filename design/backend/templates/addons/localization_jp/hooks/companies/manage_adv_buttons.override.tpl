{* $Id: manage_adv_buttons.override.tpl by takahashi from cs-cart.jp 2021 *}
{* ショップフロントの追加は ULTIMATE のみ可能 *}
{if 'MULTIVENDOR'|fn_allowed_for}
    {if $is_companies_limit_reached}
        {$title_suffix = ""|fn_get_product_state_suffix}
        {$promo_popup_title = __("ultimate_or_storefront_license_required.`$title_suffix`", ["[product]" => $smarty.const.PRODUCT_NAME])}

        {include file="common/tools.tpl"
        tool_override_meta="btn cm-dialog-opener cm-dialog-auto-height"
        tool_href="functionality_restrictions.ultimate_or_storefront_license_required"
        prefix="top"
        hide_tools=true
        title=$add_vendor_text
        icon="icon-plus"
        meta_data="data-ca-dialog-title='{$promo_popup_title}'"}
    {else}
        {include file="common/tools.tpl"
        tool_href="companies.add"
        prefix="top"
        hide_tools=true
        title=$add_vendor_text
        icon="icon-plus"
        }
    {/if}
{else}
    <span></span>
{/if}
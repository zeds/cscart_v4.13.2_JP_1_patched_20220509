{* Modified by tommy from cs-cart.jp 2017 *}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {$settings.Company.company_name|unescape}: {__("order")} #{$order_info.order_id} {__("jp_supplier_arrange_delivery")}
{else}
    {$supplier.data.name}: {__("order")} #{$order_info.order_id} {$order_status.email_subj}
{/if}
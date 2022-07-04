{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {__("customer")}<br /><br />
{else}
    {__("dear")} {__("customer")},<br /><br />
{/if}

{__("back_in_stock_notification_header")}<br /><br />

<b><a href="{$url}">{$product.name nofilter}</a></b><br /><br />

{__("back_in_stock_notification_footer")}<br />

{include file="common/letter_footer.tpl"}
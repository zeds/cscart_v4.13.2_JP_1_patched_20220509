{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {$order_info.firstname}{__("dear")}<br /><br />
{else}
    {__("dear")} {$order_info.firstname},<br /><br />
{/if}

{$return_status.email_header nofilter}<br /><br />

<b>{__("packing_slip")}:</b><br />

{include file="addons/rma/slip.tpl"}

{include file="common/letter_footer.tpl"}
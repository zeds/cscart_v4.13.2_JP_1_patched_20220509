{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
{$order_info.firstname}{__("dear")}<br /><br />

{__("text_applied_promotions")}<br />
{$promotion_data.name}<br /><br />
<b>{$bonus_data.coupon_code}</b><br />
{$promotion_data.detailed_description nofilter}
<br /><br />
{else}
{__("hello")} {$order_info.firstname}<br /><br />

{__("text_applied_promotions")}<br />

{$promotion_data.name}<br /><br />
{$promotion_data.detailed_description nofilter}<br />

<b>{$bonus_data.coupon_code}</b>
{/if}
{include file="common/letter_footer.tpl"}
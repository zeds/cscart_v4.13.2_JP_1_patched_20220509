{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE != "ja"}
    {__("hello")},<br /><br />
{/if}

{__("text_track_request")}<br /><br />

{if $o_id}
{__("text_track_view_order", ["[order]" => $o_id])}<br />
<a href="{$url}">{$url|puny_decode}</a><br />
<br />
{/if}

{__("text_track_view_all_orders")}<br />
<a href="{$track_all_url}">{$track_all_url|puny_decode}</a><br />

{if $smarty.const.CART_LANGUAGE == "ja"}
    <br /><br />
{/if}

{include file="common/letter_footer.tpl"}
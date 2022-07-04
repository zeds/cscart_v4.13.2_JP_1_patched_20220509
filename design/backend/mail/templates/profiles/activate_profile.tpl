{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {__("text_new_user_activation", ["[user_login]" => $user_data.email, "[url]" => $_url, "[url_text]" => $_url|puny_decode])}
{else}
    {__("hello")},<br /><br />

{__("text_new_user_activation", ["[user_login]" => $user_data.email, "[url]" => $url, "[url_text]" => $url|puny_decode])}
{/if}

{include file="common/letter_footer.tpl" user_type='A'}
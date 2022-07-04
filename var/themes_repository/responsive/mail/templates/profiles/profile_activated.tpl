{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {if $user_data.firstname}{$user_data.firstname}{else}{$user_data.user_type|fn_get_user_type_description|lower}{/if}{__("dear")}<br /><br />
    {__("text_profile_activated")}
{else}
    {__("hello")}&nbsp;{if $user_data.firstname}{$user_data.firstname}{else}{$user_data.user_type|fn_get_user_type_description|lower}{/if},<br /><br />
    {__("text_profile_activated")}
{/if}

{include file="common/letter_footer.tpl"}
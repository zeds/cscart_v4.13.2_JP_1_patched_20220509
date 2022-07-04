{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {if $user_data.firstname}{$user_data.firstname}{else}{$user_data.user_type|fn_get_user_type_description|lower}{/if}{__("dear")}<br><br>
{else}
    {__("dear")} {if $user_data.firstname}{$user_data.firstname}{else}{$user_data.user_type|fn_get_user_type_description|lower}{/if},<br><br>
{/if}

{__("update_profile_notification_header")}<br><br>

{if $api_access_status == "enabled"}
    {__("api_access_has_been_enabled")}<br><br>
{elseif $api_access_status == "disabled"}
    {__("api_access_has_been_disabled")}<br><br>
{/if}

{hook name="profiles:update_profile"}
{/hook}

{include file="profiles/profiles_info.tpl"}

{include file="common/letter_footer.tpl"}
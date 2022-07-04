{* $Id: my_account_menu.post.tpl by tommy from cs-cart.jp 2014 *}

{if $auth.user_id && $auth.user_id > 0}
<li><a href="{"sln_card_info.view"|fn_url}" rel="nofollow">{__("jp_sln_ccreg_registered_card")}</a></li>
{/if}

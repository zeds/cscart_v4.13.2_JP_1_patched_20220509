{* $Id: my_account_menu.post.tpl by tommy from cs-cart.jp 2015 *}

{if $auth.user_id && $auth.user_id > 0}
    <li><a href="{"gmomp_card_info.view.view"|fn_url}" rel="nofollow">{__("jp_gmo_multipayment_ccreg_registered_card")}</a></li>
{/if}

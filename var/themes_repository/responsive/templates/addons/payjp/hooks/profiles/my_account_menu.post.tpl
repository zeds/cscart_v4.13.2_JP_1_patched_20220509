{* $Id: my_account_menu.post.tpl by takahashi from cs-cart.jp 2020 *}

{if $auth.user_id && $auth.user_id > 0}
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"payjp_card_info.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_payjp_ccreg_registered_card")}</a></li>
{/if}

{* $Id: my_account_menu.post.tpl by takahashi from cs-cart.jp 2019 *}

{if $auth.user_id && $auth.user_id > 0}
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"sonys_card_info.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_sonys_registered_card")}</a></li>
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"sonys_subpay_list.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_sonys_subpay_list")}</a></li>
{/if}

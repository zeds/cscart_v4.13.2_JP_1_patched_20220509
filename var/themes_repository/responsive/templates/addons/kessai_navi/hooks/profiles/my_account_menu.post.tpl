{* $Id: my_account_menu.post.tpl by tommy from cs-cart.jp 2014 *}

{if $auth.user_id && $auth.user_id > 0}
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"kessai_navi_card_info.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_knv_registered_card")}</a></li>
{/if}

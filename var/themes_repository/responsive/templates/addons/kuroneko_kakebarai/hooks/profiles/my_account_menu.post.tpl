{* $Id: my_account_menu.post.tpl by takahashi from cs-cart.jp 2018 *}
{if $auth.user_id && $auth.user_id > 0 && $auth.user_id|fn_kuroneko_kakebarai_is_kakebarai_user && fn_kuroneko_kakebarai_is_payment_registered('A')}
<li class="ty-account-info__item ty-dropdown-box__item"><a class="ty-account-info__a" href="{"krnkkb_user_info.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_kuroneko_kakebarai_user_info")}</a></li>
{/if}

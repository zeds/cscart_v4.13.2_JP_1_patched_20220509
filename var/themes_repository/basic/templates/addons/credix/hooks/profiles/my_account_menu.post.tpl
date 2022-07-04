{* $Id: my_account_menu.post.tpl by tommy from cs-cart.jp 2014 *}
{if $auth.user_id && $auth.user_id > 0}
{if $auth.user_id|fn_crdx_get_qc_info}
<li><a href="{"credix_card_info.view"|fn_url}" rel="nofollow" class="underlined">{__("jp_credix_qc_registered_card")}</a></li>
{/if}
{/if}

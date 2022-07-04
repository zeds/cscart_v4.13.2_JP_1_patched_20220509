{* $Id: my_account_menu.post.tpl by tommy from cs-cart.jp 2013 *}

{if $auth.user_id && $auth.user_id > 0}
<li><a href="{"digital_check_card_info.view"|fn_url}" rel="nofollow">{__('jp_digital_check_uid_registered_card')}</a></li>
{/if}

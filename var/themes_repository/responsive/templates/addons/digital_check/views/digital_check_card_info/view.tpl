{* $Id: view.tpl by tommy from cs-cart.jp 2013 *}

{if $uid_info}
<p>{__('jp_digital_check_uid_click_to_delete')}</p>
<div class="buttons-container jp_digital_check_buttons-container">
	{include file="buttons/button.tpl" but_text=__('jp_digital_check_uid_delete_card_info') but_href="digital_check_card_info.delete" but_meta="cm-confirm"}
</div>
{else}
	<p class="no-items">{__('jp_digital_check_uid_no_card_info')}</p>
{/if}

{capture name="mainbox_title"}{__('jp_digital_check_uid_registered_card')}{/capture}

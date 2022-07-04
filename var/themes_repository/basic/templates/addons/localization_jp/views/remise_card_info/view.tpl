{* $Id: view.tpl by tommy from cs-cart.jp 2014 *}

{if $payquick_info}
<p>{__("jp_remise_payquick_click_to_delete")}</p>
<div class="buttons-container remise_buttons-container">
	{include file="buttons/button.tpl" but_text=__("jp_remise_payquick_delete_card_info") but_href="remise_card_info.delete" but_meta="cm-confirm"}
</div>
{else}
	<p class="no-items">{__("jp_remise_payquick_no_card_info")}</p>
{/if}

{capture name="mainbox_title"}{__("jp_remise_payquick_registered_card")}{/capture}

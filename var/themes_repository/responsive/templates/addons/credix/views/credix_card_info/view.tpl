{* $Id: view.tpl by tommy from cs-cart.jp 2014 *}

{if $qc_exists == 'Y'}
<p>{__("jp_credix_qc_click_to_delete")}</p>
<div class="buttons-container remise_buttons-container">
	{include file="buttons/button.tpl" but_text=__("jp_credix_qc_delete_card_info") but_href="credix_card_info.delete" but_meta="cm-confirm"}
</div>
{else}
	<p class="no-items">{__("jp_credix_qc_no_card_info")}</p>
{/if}

{capture name="mainbox_title"}{__("jp_credix_qc_registered_card")}{/capture}

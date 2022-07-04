{* $Id: view.tpl by tommy from cs-cart.jp 2014 *}

{if $sln_registered_card}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr valign="top">
	<td>
		{if $sln_registered_card.card_number}
		<div class="sln_card_info">
			<label>{__("card_number")} : {$sln_registered_card.card_number}</label>
		</div>
		{/if}
		{if $sln_registered_card.card_exp}
        <div class="sln_card_info">
			<label>{__("expiry_date")} : {$sln_registered_card.card_exp}</label>
		</div>
		{/if}
	</td>
</tr>
</table>
<div class="buttons-container">
	{include file="buttons/button.tpl" but_text=__("jp_sln_ccreg_delete_card_info") but_href="sln_card_info.delete" but_meta="cm-confirm"}
</div>

{else}
	<p class="no-items">{__("jp_sln_ccreg_no_card_info")}</p>
{/if}

{capture name="mainbox_title"}{__("jp_sln_ccreg_registered_card")}{/capture}

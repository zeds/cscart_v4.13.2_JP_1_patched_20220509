{* $Id: view.tpl by tommy from cs-cart.jp 2016 *}

{if $registered_card}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr valign="top">
	<td>
		{if $registered_card.maskingCardNo}
		<div class="form-field">
			<label>{__("card_number")} : {$registered_card.maskingCardNo}</label>
		</div>
		{/if}
		{if $registered_card.cardExp}
		<div class="form-field">
			<label>{__("expiry_date")} : {$registered_card.cardExp}</label>
		</div>
		{/if}
	</td>
</tr>
</table>
<div class="buttons-container cm-confirm">
	{include file="buttons/button.tpl" but_text=__("jp_kuroneko_webcollect_ccreg_delete_card_info") but_href="krnkwc_card_info.delete"}
</div>

{else}
	<p class="no-items">{__("jp_kuroneko_webcollect_ccreg_no_card_info")}</p>
{/if}

{capture name="mainbox_title"}{__("jp_kuroneko_webcollect_ccreg_registered_card")}{/capture}

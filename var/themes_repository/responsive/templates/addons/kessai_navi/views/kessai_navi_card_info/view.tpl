{* $Id: view.tpl by tommy from cs-cart.jp 2014 *}

{if $knv_registered_card}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr valign="top">
	<td>
		{if $knv_registered_card.cnumber}
		<div class="form-field">
			<label>{__("card_number")} : {$knv_registered_card.cnumber}</label>
		</div>
		{/if}
		{if $knv_registered_card.expirydate}
		<div class="form-field">
			<label>{__("expiry_date")} : {$knv_registered_card.expirydate}</label>
		</div>
		{/if}
	</td>
</tr>
</table>
<div class="buttons-container cm-confirm">
	{include file="buttons/button.tpl" but_text=__("jp_knv_delete_card_info") but_href="kessai_navi_card_info.delete"}
</div>

{else}
	<p class="no-items">{__("jp_knv_no_card_info")}</p>
{/if}

{capture name="mainbox_title"}{__("jp_knv_registered_card")}{/capture}

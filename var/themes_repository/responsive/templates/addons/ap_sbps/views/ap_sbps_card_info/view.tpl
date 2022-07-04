{if $registered_card}
	<table cellpadding="0" cellspacing="0" width="100%" border="0">
		<tr valign="top">
			<td>
				{if $registered_card.cc_number}
					<div class="form-field">
						<label>{__('card_number')} : {$registered_card.cc_number}</label>
					</div>
				{/if}
				{if $registered_card.cc_expiration}
					<div class="form-field">
						<label>{__('expiry_date')} : {$registered_card.cc_expiration}</label>
					</div>
				{/if}
			</td>
		</tr>
	</table>
	<div class="buttons-container cm-confirm">
		{include file='buttons/button.tpl' but_text=__('sbps_delete_card_info') but_href='ap_sbps_card_info.delete'}
	</div>
{else}
	<p class="no-items">{__('sbps_no_card_info')}</p>
{/if}

{capture name='mainbox_title'}{__('sbps_registered_card')}{/capture}

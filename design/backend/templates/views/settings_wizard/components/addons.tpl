{* Modified by takahashi from cs-cart.jp 2021 *}
{foreach from=$wizard_addons_list item="addon"}
	<div class="table-responsive-wrapper">
		<table class="table table-addons table-wizard table--relative table-responsive table-responsive-w-titles">
		    <tr>
		        <td class="addon-icon" data-th="&nbsp;">
		            <div class="bg-icon">
		                {if $addon.has_icon}
		                    <img src="{$images_dir}/addons/{$addon.addon_name}/icon.png" width="38" height="38" border="0" alt="{$addon.name}" title="{$addon.name}" >
		                {/if}
		            </div>
		        </td>
                {* Modified by takahashi from cs-cart.jp 2021 BOF *}
		        <td width="85%" data-th="&nbsp;">
                {* Modified by takahashi from cs-cart.jp 2021 EOF *}
		            <div class="object-group-link-wrap">
		                <span class="unedited-element block">{$addon.name}</span><br>
		                <span class="row-status object-group-details">{$addon.description}</span>
		            </div>
		        </td>
                {* Modified by takahashi from cs-cart.jp 2021 BOF *}
		        <td width="15%" data-th="&nbsp;">
                {* Modified by takahashi from cs-cart.jp 2021 EOF *}
		            <input type="hidden" name="addons[{$addon.addon_name}]" value="N">
		            <label for="addon_{$addon.addon_name}" class="checkbox">
		                <input id="addon_{$addon.addon_name}" type="checkbox" name="addons[{$addon.addon_name}]" value="Y">
		                {__("install")}
		            </label>     
		        </td>
		    </tr>
		</table>
	</div>
{/foreach}
{* $Id: manage.tpl by ari from cs-cart.jp 2015 *}

{if $smarty.request.highlight}
{assign var="highlight" value=","|explode:$smarty.request.highlight}
{/if}

{literal}
<script>
//<![CDATA[
function fn_create_link(carrier) {
	var service_id, zone_id;
	service_id = $("#"+carrier+"_service_id").val();
	zone_id = $("#"+carrier+"_shipping_zone").val();
	jQuery.ajaxRequest(index_script + '?dispatch=shipping_rates_jp.manage' + 
		'&carrier=' + carrier +
		'&service=' + service_id +
		'&zone=' + zone_id, {result_ids: 'ajax_service_zone', force_exec: true}
	);
}
//]]>
</script>
{/literal}

{capture name="mainbox"}

{capture name="tabsbox"}

{assign var="t_id" value=$carrier_id}

{foreach from=$jp_carriers item=carriers key="ukey"}
<div id="content_{$ukey}">
<form class="cm-ajax" action="{if $user_info.user_type == 'V'}{$config.vendor_index}{else}{$config.admin_index}{/if}" method="GET" name="{$ukey}_service_rate" target="_self">
	<input type="hidden" name="result_ids" value="ajax_service_zone_rate" />
	<input type="hidden" id="carrier" name="carrier" value="{$ukey}" />
	<table class="table-middle">
		<tr>
			<th class="left">
				{__("jp_shipping_service_name")}:
				{foreach from=$jp_carrier_services item=services key="skey"}
					{if $skey == $ukey}
					<select name="{$ukey}_service_id" id="{$ukey}_service_id" class="jp_shipping_service_id">
					{foreach from=$services item=sitem}
						{if $skey == $default_carrier}
						<option value="{$sitem.service_code}" {if $default_service == $sitem.service_code}selected="selected"{/if}>{$sitem.service_name}</option>
						{else}
						<option value="{$sitem.service_code}">{$sitem.service_name}</option>
						{/if}
					{/foreach}
					</select>
					{/if}
				{/foreach}
				{__("jp_shipping_origination")}:
				{foreach from=$jp_carrier_zones item=zones key="skey"}
					{if $skey == $ukey}
						{* EMS 以外の配送設定 *}
						{if $skey != 'jpems'}
							<select name="{$ukey}_shipping_zone" id="{$ukey}_shipping_zone" class="jp_shipping_zone">
							{foreach from=$zones item=zitem}
								{if $skey == $default_carrier}
								<option value="{$zitem.zone_id}" {if $default_zone == $zitem.zone_id}selected="selected"{/if}>{$zitem.zone_name}</option>
								{else}
								<option value="{$zitem.zone_id}">{$zitem.zone_name}</option>
								{/if}
							{/foreach}
							</select>
						{else}
							{* EMS 配送設定 *}
							<select name="{$ukey}_shipping_zone" id="{$ukey}_shipping_zone">
							{foreach from=$zones item=zitem}
								{if $zitem.zone_id == 52}
									{if $skey == $default_carrier}
									<option value="{$zitem.zone_id}" {if $default_zone == $zitem.zone_id}selected="selected"{/if}>{$zitem.zone_name}</option>
									{else}
									<option value="{$zitem.zone_id}">{$zitem.zone_name}</option>
									{/if}
								{/if}
							{/foreach}
							</select>
						{/if}
					{/if}
				{/foreach}			
				<input id="{$ukey}_load_button" class="btn" type="submit" name="dispatch[shipping_rates_jp.manage]" value={__("show")} />
			</th>
		</tr>
	</table>
</form>
</div>
{/foreach}

{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox track=true active_tab=$t_id}

{include file="addons/localization_jp/views/shipping_rates_jp/ajax_manage.tpl"}

{/capture}

{capture name="add_button"}
    {$smarty.capture.add_button}
    <span class="btn-group" id="tools_translations_save_button">
            {include file="buttons/save.tpl" but_name="dispatch[shipping_rates_jp.update]" but_role="submit-link" but_target_form="rate_form"}
        </span>
{/capture}

{capture name="buttons"}
    {$smarty.capture.add_button nofilter}
{/capture}

{include file="common/mainbox.tpl" title="`$settings_title`" content=$smarty.capture.mainbox buttons=$smarty.capture.buttons}

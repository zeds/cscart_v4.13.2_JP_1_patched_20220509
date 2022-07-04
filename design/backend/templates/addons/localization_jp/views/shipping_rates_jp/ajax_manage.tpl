{* $Id: ajax_manage.tpl by ari from cs-cart.jp 2015 *}

<div id="ajax_service_zone_rate" class="table-wrapper">
	<form name="rate_form" action="{if $user_info.user_type == 'V'}{$config.vendor_index}{else}{$config.admin_index}{/if}" method="POST" class="cm-form-highlight cm-ajax">
        <table class="table" width="100%">
        {if empty($jp_carrier_rates)}
            <tr>
                <th class="center">{__("jp_shipping_select_service_and_origin")}</th>
            </tr>
        {elseif !empty($jp_carrier_rates)}
            <tr>
                <th class="left" colspan="{$current_zones_count}">{__("jp_shipping_carrier")}：［<span style="color:#A74401">{$jp_carrier_name}</span>］　{__("jp_shipping_service_name")}：［<span style="color:#A74401">{$jp_carrier_services_name}</span>］　{__("jp_shipping_origination")}：［<span style="color:#A74401">{$jp_carrier_zone_name}</span>］
                    <input type="hidden" name="carrier" value="{$default_carrier}" />
                    <input type="hidden" name="service" value="{$default_service}" />
                    <input type="hidden" name="zone" value="{$default_zone}" />
                </th>
            </tr>
            <tr>
                {* EMS 対応 EMS配送ではサイズを表示しない *}
                {if $default_carrier != 'jpems'}
                <th class="center">{__("jp_shipping_size")}</th>
                {/if}
                <th class="center">
                {__("jp_shipping_weight")}
                </th>
                {foreach from=$current_zones item=item name="sections"}
                <th class="center">{$item.zone_name}</th>
                {/foreach}
            </tr>
            {foreach from=$zone_rates item=rates_table key="rkey"}
            <tr>
                {* EMS 対応 EMS配送ではサイズを表示しない *}
                {if $default_carrier != 'jpems'}
                <td class="right jp_shipping_size">{$rates_table.size}</td>
                {/if}
                {* EMS 対応 *}
                {if $default_carrier == 'jpems'}
                {math equation=$rates_table.weight/100 assign=weight}
                <!--{$weight|escape|number_format:2}-->
                <td class="center jp_shipping_weight">{$weight}</td>
                {else}
                <td class="right jp_shipping_weight">{$rates_table.weight}</td>
                {/if}
                {foreach from=$rates_table.rates item=rate key="rkey"}
                <td class="center"><input id="{$rates_table.size}_{$rates_table.weight}_{$rkey}" type="text" name="{$rates_table.size}_{$rates_table.weight}_{$rkey}" class="jp_input_shipping_rate" size="5" maxlength="5" value="{$rate}" /></td>
                {/foreach}
            </tr>
            {/foreach}
        {/if}
        </table>
    </form>
</div>

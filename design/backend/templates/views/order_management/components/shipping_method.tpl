{* Modified by tommy from cs-cart.jp 2017 *}

{hook name="order_management:shipping_method"}
<div class="control-group">
    <div class="control-label">
        <h4 class="subheader">{__("shipping_method")}</h4>
    </div>
</div>
    {if $product_groups}
        {foreach from=$product_groups key=group_key item=group}
            <div class="control-group">
            <label class="control-label"> {$group.name|default:__("none")}</label>
            {if $group.shippings && !$group.shipping_no_required}
                <div class="controls">
                    <select name="shipping_ids[{$group_key}]" class="cm-submit cm-ajax cm-skip-validation" data-ca-dispatch="dispatch[order_management.update_shipping]">
                    {foreach from=$group.shippings item=shipping}
                        <option value="{$shipping.shipping_id}" {if $cart.chosen_shipping.$group_key == $shipping.shipping_id}selected="selected"{/if}>{$shipping.shipping} ({$shipping.delivery_time}) - {include file="common/price.tpl" value=$shipping.rate}</option>
                    {/foreach}
                    </select>
                </div>
                {* Modified by tommy from cs-cart.jp 2017 BOF *}
                {if $cart.chosen_shipping.$group_key }
                    {assign var="delivery_timing_shipping_id" value=$cart.chosen_shipping.$group_key}
                {else}
                    {assign var="delivery_timing_shipping_id" value=$shipping.shipping_id}
                {/if}

                {if $cart.order_id > 0 && $shipping.shipping_id > 0}
                    {assign var="previous_delivery_info" value=$cart.order_id|fn_lcjp_get_order_delivery_info:$delivery_timing_shipping_id:$group_key}
                    {if $previous_delivery_info.delivery_date}
                        {assign var="previous_delivery_date" value=$previous_delivery_info.delivery_date}
                    {/if}
                    {if $previous_delivery_info.delivery_timing}
                        {assign var="previous_delivery_timing" value=$previous_delivery_info.delivery_timing}
                    {/if}
                {/if}

                {assign var="delivery_timing" value=$delivery_timing_shipping_id|fn_lcjp_get_delivery_timing}

                {if $delivery_timing|@count}
                    {if $delivery_timing.delivery_date_array|@count}
                        <div class="control-group">
                            <label class="control-label">{__("jp_delivery_date")}</label>
                            <select id="delivery_date_selected_{$group_key}_{$shipping.shipping_id}" name="delivery_date_selected_{$group_key}_{$delivery_timing_shipping_id}">
                                {foreach from=$delivery_timing.delivery_date_array key="daykey" item="delivery_date_table"}
                                    {if $previous_delivery_date == $delivery_date_table}
                                        {assign var="flg_selected_date_matched" value="Y"}
                                    {/if}
                                    <option value="{$delivery_date_table}" {if $previous_delivery_date == $delivery_date_table}selected="selected"{/if}>{$delivery_date_table}</option>
                                {/foreach}
                                {if !$flg_selected_date_matched || $flg_selected_date_matched != "Y"}
                                    <option value="{$previous_delivery_date}" selected="selected">{$previous_delivery_date}</option>
                                {/if}
                            </select>
                        </div>
                    {/if}

                    {if $delivery_timing.delivery_time_array|@count}
                        <div class="control-group">
                            <label class="control-label">{__("jp_shipping_delivery_time")}</label>
                            <select id="delivery_time_selected_{$group_key}_{$shipping.shipping_id}" name="delivery_time_selected_{$group_key}_{$delivery_timing_shipping_id}">
                                {foreach from=$delivery_timing.delivery_time_array key="timekey" item="delivery_timetable"}
                                    <option value="{$delivery_timetable}" {if $previous_delivery_timing == $delivery_timetable}selected="selected"{/if}>{$delivery_timetable}</option>
                                {/foreach}
                            </select>
                        </div>
                    {/if}
                {/if}
                {* Modified by tommy from cs-cart.jp 2017 EOF *}
            {elseif $group.shipping_no_required}
                {__("no_shipping_required")}
            {elseif $group.shipping_by_marketplace}
                {__("shipping_by_marketplace")}
            {else}
                {__("text_no_shipping_methods")}
                {assign var="is_empty_rates" value="Y"}
            {/if}
            </div>
        {/foreach}
    {else}
        <span class="text-error">{__("text_no_shipping_methods")}</span>
    {/if}
{/hook}
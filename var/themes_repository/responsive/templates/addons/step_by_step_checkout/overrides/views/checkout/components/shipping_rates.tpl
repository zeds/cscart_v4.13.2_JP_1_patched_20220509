{* Modified by tommy from cs-cart.jp 2017 *}

{if $show_header == true}
    {include file="common/subheader.tpl" title=__("select_shipping_method")}
{/if}


{if !$no_form}
    <form {if $use_ajax}class="cm-ajax"{/if} action="{""|fn_url}" method="post" name="shippings_form">
        <input type="hidden" name="redirect_mode" value="checkout" />
        {if $use_ajax}
            <input type="hidden" name="result_ids" value="checkout_totals,checkout_steps" />
        {/if}
{/if}

{hook name="checkout:shipping_rates"}

    <div id="shipping_rates_list">

        {foreach from=$product_groups key="group_key" item=group name="spg"}
            {* Group name *}
            {if !"ULTIMATE"|fn_allowed_for || $product_groups|count > 1}
                <span class="ty-shipping-options__vendor-name">{$group.name}</span>
            {/if}

            {* Products list *}
            {if !"ULTIMATE"|fn_allowed_for || $product_groups|count > 1}
                <ul class="ty-shipping-options__products">
                    {foreach from=$group.products item="product"}
                        {if !(($product.is_edp == 'Y' && $product.edp_shipping != 'Y') || $product.free_shipping == 'Y')}
                            <li class="ty-shipping-options__products-item">
                                {if $product.product}
                                    {$product.product nofilter}
                                {else}
                                    {$product.product_id|fn_get_product_name}
                                {/if}
                            </li>
                        {/if}
                    {/foreach}
                </ul>
            {/if}

            {* Shippings list *}
            {if $group.shippings && !$group.all_edp_free_shipping && !$group.shipping_no_required}

                {foreach from=$group.shippings item="shipping"}

                    {hook name="checkout:shipping_rate"}
                        {if $cart.chosen_shipping.$group_key == $shipping.shipping_id}
                            {assign var="checked" value="checked=\"checked\""}
                            {assign var="strong_begin" value="<strong>"}
                            {assign var="strong_end" value="</strong>"}
                        {else}
                            {assign var="checked" value=""}
                            {assign var="strong_begin" value=""}
                            {assign var="strong_end" value=""}
                        {/if}

                        {if $shipping.delivery_time || $shipping.service_delivery_time}
                            {assign var="delivery_time" value="(`$shipping.service_delivery_time|default:$shipping.delivery_time`)"}
                        {else}
                            {assign var="delivery_time" value=""}
                        {/if}

                        {if $shipping.rate}
                            {capture assign="rate"}{include file="common/price.tpl" value=$shipping.rate}{/capture}
                            {if $shipping.inc_tax}
                                {assign var="rate" value="`$rate` ("}
                                {if $shipping.taxed_price && $shipping.taxed_price != $shipping.rate}
                                    {capture assign="tax"}{include file="common/price.tpl" value=$shipping.taxed_price class="ty-nowrap"}{/capture}
                                    {assign var="rate" value="`$rate``$tax` "}
                                {/if}
                                {assign var="inc_tax_lang" value=__('inc_tax')}
                                {assign var="rate" value="`$rate``$inc_tax_lang`)"}
                            {/if}
                        {elseif fn_is_lang_var_exists("free_shipping")}
                            {assign var="rate" value=__("free_shipping") }
                        {else}
                            {assign var="rate" value="" }
                        {/if}
                    {/hook}

                    {hook name="checkout:shipping_method"}
                        <div class="ty-shipping-options__method">
                            <input type="radio" class="ty-valign ty-shipping-options__checkbox" id="sh_{$group_key}_{$shipping.shipping_id}" name="shipping_ids[{$group_key}]" value="{$shipping.shipping_id}" onclick="fn_calculate_total_shipping_cost();" {$checked} />
                            <div class="ty-shipping-options__group">
                                <label for="sh_{$group_key}_{$shipping.shipping_id}" class="ty-valign ty-shipping-options__title">
                                    <bdi>
                                        {if $shipping.image}
                                            <div class="ty-shipping-options__image">
                                                {include file="common/image.tpl" obj_id=$shipping_id images=$shipping.image class="ty-shipping-options__image"}
                                            </div>
                                        {/if}

                                        {$shipping.shipping} {$delivery_time}
                                        {if $rate} {$rate nofilter}{/if}
                                   </bdi>
                                </label>
                            </div>
                        </div>
                        {if $shipping.description}
                            <div class="ty-checkout__shipping-tips">
                                <p>{$shipping.description nofilter}</p>
                            </div>
                        {/if}
                    {/hook}
                    {* Modified by tommy from cs-cart.jp 2017 BOF *}
                    {assign var="delivery_timing" value=$shipping.shipping_id|fn_lcjp_get_delivery_timing}

                    <div class="jp_shipping_delim">
                        {if $delivery_timing|@count}
                            {if $delivery_timing.delivery_time_array|@count}
                                <div class="jp_delivery_option">
                                    <label for= "delivery_time_selected_{$group_key}_{$shipping.shipping_id}">{__("jp_shipping_delivery_time")}：</label>
                                    <select id="delivery_time_selected_{$group_key}_{$shipping.shipping_id}" name="delivery_time_selected_{$group_key}_{$shipping.shipping_id}">
                                        {foreach from=$delivery_timing.delivery_time_array key="timekey" item="delivery_timetable"}
                                            <option value="{$delivery_timetable}" {if $cart.shipping[$shipping.shipping_id].delivery_info[{$group_key}].delivery_time_selected == $delivery_timetable}selected="selected"{/if}>{$delivery_timetable}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            {/if}
                            {if $delivery_timing.delivery_date_array|@count}
                                <div class="jp_delivery_option">
                                    <label for= "delivery_date_selected_{$group_key}_{$shipping.shipping_id}">{__("jp_delivery_date")}：</label>
                                    <select id="delivery_date_selected_{$group_key}_{$shipping.shipping_id}" name="delivery_date_selected_{$group_key}_{$shipping.shipping_id}">
                                        {foreach from=$delivery_timing.delivery_date_array key="daykey" item="delivery_date_table"}
                                            <option value="{$delivery_date_table}" {if $cart.shipping[$shipping.shipping_id].delivery_info[{$group_key}].delivery_date_selected == $delivery_date_table}selected="selected"{/if}>{$delivery_date_table}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            {/if}
                        {/if}
                    </div>
                {/foreach}
                {* Modified by tommy from cs-cart.jp 2017 EOF *}
                {if $smarty.foreach.spg.last && !$group.all_edp_free_shipping && !$group.shipping_no_required}
                    <p class="ty-shipping-options__total">{__("total")}:&nbsp;{include file="common/price.tpl" value=$cart.display_shipping_cost class="ty-price"}</p>
                {/if}

            {else}
                {if $group.all_free_shipping}
                     <p>{__("free_shipping")}</p>
                {elseif $group.all_edp_free_shipping || $group.shipping_no_required }
                    <p>{__("no_shipping_required")}</p>
                {else}
                    <p class="ty-error-text">
                        {__("text_no_shipping_methods")}
                    </p>
                {/if}
            {/if}

        {foreachelse}
            <p>
                {if !$cart.shipping_required}
                    {__("no_shipping_required")}
                {elseif $cart.free_shipping}
                    {__("free_shipping")}
                {/if}
            </p>
        {/foreach}

    <!--shipping_rates_list--></div>

{/hook}

{if !$no_form}
        <div class="cm-noscript buttons-container ty-center">{include file="buttons/button.tpl" but_name="dispatch[checkout.update_shipping]" but_text=__("select")}</div>
    </form>
{/if}
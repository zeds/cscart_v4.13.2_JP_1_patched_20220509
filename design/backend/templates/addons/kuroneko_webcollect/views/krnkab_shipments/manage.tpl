{* Modified by takahashi from cs-cart.jp 2020 *}

{capture name="mainbox"}

    {capture name="sidebar"}
        {include file="addons/kuroneko_webcollect/views/krnkab_shipments/components/orders_search_form.tpl" dispatch="krnkab_shipments.manage"}
    {/capture}

    <form action="{""|fn_url}" method="post" target="_self" name="krnkwc_cc_orders_list_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
        {assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
        {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}

        {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

        {assign var="page_title" value=__("jp_kuroneko_webcollect_ab_shipments_status_long")}
        {assign var="get_additional_statuses" value=false}
        {assign var="order_status_descr" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}
        {assign var="extra_status" value=$config.current_url|escape:"url"}
        {$statuses = []}
        {assign var="order_statuses" value=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}

        {if $orders}
        <div class="table-wrapper">
            <table width="100%" class="table table-middle">
                <thead>
                <tr>
                    <th class="left">
                        {include file="common/check_items.tpl"}
                    </th>
                    <th width="11%"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("order")}{__("id")}{if $search.sort_by == "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=shipping&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("shipping_method")}{if $search.sort_by == "shipping"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=tracking_number&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("jp_kuroneko_webcollect_slip_no")}{if $search.sort_by == "tracking_number"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="20%"><a class="cm-ajax" href="{"`$c_url`&sort_by=carrier&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("carrier")}{if $search.sort_by == "carrier"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="14%"><a class="cm-ajax" href="{"`$c_url`&sort_by=delivery_service_code&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("jp_kuroneko_webcollect_delivery_service")}{if $search.sort_by == "delivery_service_code"}{$c_icon nofilter}{/if}</a></th>
                    <th width="20%" class="right"><a class="cm-ajax{if $search.sort_by == "shipment_timestamp"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=shipment_timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("created")}{if $search.sort_by == "shipment_timestamp"}{$c_icon nofilter}{/if}</a></th>
                </tr>
                </thead>
                {foreach from=$orders item="o"}
                    {assign var="carrier_data" value=$o.carrier|fn_krnkwc_get_carrier_name}
                    <tr>
                        <td class="left">
                            <input type="checkbox" name="order_ids[{$o.order_id}]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                            <input type="hidden" name="is_registered[{$o.order_id}]" value="{if $o.tracking_number}YES{else}NO{/if}" />
                        <td>
                            {if $o.tracking_number}
                                <a href="{"shipments.manage?order_id=`$o.order_id`"|fn_url}" class="underlined">#{$o.order_id}</a>
                            {else}
                                <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="underlined">#{$o.order_id}</a>
                            {/if}
                        </td>
                        <td>
                            {$o.shipping}
                            <input type="hidden" name="shipping_ids[{$o.order_id}]" value="{$o.shipping_id}" />
                        </td>
                        <td>
                            {if $o.tracking_number}
                                <a href="{$carrier_data.tracking_url|sprintf:$o.tracking_number}" class="underlined" target="blank">{$o.tracking_number}</a>
                            {else}
                                <input type="tel" name="tracking_numbers[{$o.order_id}]" value="{$o.tracking_number}" class="input-medium"/>
                            {/if}
                        </td>
                        <td>
                            {if $o.tracking_number}
                                {$carrier_data.name}
                            {else}
                                {include file="common/carriers.tpl" id="carrier_key" meta="input-small" name="carriers[`$o.order_id`]" carrier=$o.carrier}
                            {/if}
                        </td>
                        <td>
                            {if $o.tracking_number}
                                {__("jp_kuroneko_webcollect_delivery_service_`$o.delivery_service_code`")}
                            {else}
                                <label for="elm_file_agreement_other" class="checkbox">
                                    <input type="checkbox" name="delivery_services[{$o.order_id}]" id="elm_file_agreement_other" value="99"/>
                                    {__("jp_kuroneko_webcollect_delivery_service_99")}</label>
                            {/if}
                        </td>
                        <td class="right">
                            {if $o.tracking_number}
                                {$o.shipment_timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}

        {include file="common/pagination.tpl" div_id=$smarty.request.content_id}

    </form>
{/capture}

{capture name="buttons"}
    {if $orders}
        {capture name="tools_list"}
            <li>{btn type="list" text={__("jp_kuroneko_webcollect_add_shipment")} dispatch="dispatch[krnkab_shipments.add_shipment]" form="krnkwc_cc_orders_list_form" class="cm-process-items cm-confirm"}</li>
            <li>{btn type="list" text={__("jp_kuroneko_webcollect_cancel_shipment")} dispatch="dispatch[krnkab_shipments.cancel_shipment]" form="krnkwc_cc_orders_list_form" class="cm-process-items cm-confirm"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}

{* Modified by takahashi from cs-cart.jp 2019 *}

{capture name="mainbox"}

    {capture name="sidebar"}
        {include file="addons/sonypayment_subpay/views/sonys_subsc_manager/components/orders_search_form.tpl" dispatch="sonys_subsc_manager.details"}
    {/capture}

    <form action="{""|fn_url}" method="post" target="_self" name="sonys_orders_list_form" class="form-horizontal form-edit">

        <input type="hidden" name="subsc_info[subpay_id]" value="{$search.subpay_id}">

        {assign var="deliver_freq" value=$subscriptions.product_id|fn_sonys_get_product_freq}

        <div class="control-group">
            <label for="subsc_status" class="control-label">{__("jp_sonys_subsc_status")}</label>
            <div class="controls">
                <select id="subsc_status" name="subsc_info[status]" >
                    {foreach from=$subsc_status item="status_item" key="status_key"}
                        <option {if $subscriptions.status == $status_key}selected="selected"{/if} value="{$status_key}">{$status_item}</option>
                    {/foreach}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label for="sonys_delivery_time" class="control-label">{__('jp_sonys_deliver_send_time')}:</label>
            <div class="controls">
                <select id="sonys_delivery_time" name="subsc_info[deliver_time]">
                    {if $deliver_freq.deliver_time}
                        {foreach from=$deliver_freq.deliver_time item="deliver_time"}
                            <option {if $subscriptions.deliver_time == $deliver_time}selected="selected"{/if} value="{$deliver_time}">{__("jp_sonys_deliver_send_time_`$deliver_time`")}</option>
                        {/foreach}
                    {/if}
                </select>
            </div>
        </div>

        <div class="control-group">
            <label for="deliver_freq" class="control-label">{__("jp_sonys_deliver_freq")}</label>
            <div class="controls">
                <select id="deliver_freq" name="subsc_info[deliver_freq]" onchange="fn_change_sonys_deliver_send_day(this)">
                    {if $deliver_freq.deliver_day_w}<option {if $subscriptions.deliver_freq == "w"}selected="selected"{/if} value="w">{__("jp_sonys_deliver_freq_w")}</option>{/if}
                    {if $deliver_freq.deliver_day_bw}<option {if $subscriptions.deliver_freq == "bw"}selected="selected"{/if} value="bw">{__("jp_sonys_deliver_freq_bw")}</option>{/if}
                    {if $deliver_freq.deliver_day_m}<option {if $subscriptions.deliver_freq == "m"}selected="selected"{/if} value="m">{__("jp_sonys_deliver_freq_m")}</option>{/if}
                    {if $deliver_freq.deliver_day_bm}<option {if $subscriptions.deliver_freq == "bm"}selected="selected"{/if} value="bm">{__("jp_sonys_deliver_freq_bm")}</option>{/if}
                </select>
            </div>
        </div>

        {assign var="deliver_freq" value=$subscriptions.product_id|fn_sonys_get_product_freq}

        <div class="control-group">
            <label for="sonys_delivery_day" class="control-label">{__('jp_sonys_deliver_send_day')}:</label>
            <div class="controls">
                <div id="jp_sonys_deliver_send_w" class="{if $subscriptions.deliver_freq != "w"}hidden{/if}">
                    <select id="sonys_delivery_day" name="subsc_info[deliver_day_w]">
                        {if $deliver_freq.deliver_day_w}
                            {foreach from=$deliver_freq.deliver_day_w item="deliver_day"}
                                <option value="{$deliver_day}" {if $subscriptions.deliver_day == $deliver_day}selected="selected"{/if}>{__("jp_sonys_deliver_send_day_`$deliver_day`")}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div id="jp_sonys_deliver_send_bw" class="{if $subscriptions.deliver_freq != "bw"}hidden{/if}">
                    <select id="sonys_delivery_day" name="subsc_info[deliver_day_bw]">
                        {if $deliver_freq.deliver_day_bw}
                            {foreach from=$deliver_freq.deliver_day_bw item="deliver_day"}
                                <option value="{$deliver_day}" {if $subscriptions.deliver_day == $deliver_day}selected="selected"{/if}>{__("jp_sonys_deliver_send_day_`$deliver_day`")}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div id="jp_sonys_deliver_send_m" class="{if $subscriptions.deliver_freq != "m"}hidden{/if}">
                    <select id="sonys_delivery_day" name="subsc_info[deliver_day_m]">
                        {if $deliver_freq.deliver_day_m}
                            {foreach from=$deliver_freq.deliver_day_m item="deliver_day"}
                                <option value="{$deliver_day}" {if $subscriptions.deliver_day == $deliver_day}selected="selected"{/if}>{__("jp_sonys_deliver_send_m_day_`$deliver_day`")}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
                <div id="jp_sonys_deliver_send_bm" class="{if $subscriptions.deliver_freq != "bm"}hidden{/if}">
                    <select id="sonys_delivery_day" name="subsc_info[deliver_day_bm]">
                        {if $deliver_freq.deliver_day_bm}
                            {foreach from=$deliver_freq.deliver_day_bm item="deliver_day"}
                                <option value="{$deliver_day}" {if $subscriptions.deliver_day == $deliver_day}selected="selected"{/if}>{__("jp_sonys_deliver_send_m_day_`$deliver_day`")}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
        </div>

        <div class="control-group">
            <label for="ns_date" class="control-label">{__("jp_sonys_next_send_date")}</label>
            <div class="controls">
            {include file="common/calendar.tpl" date_id="ns_date" date_name="subsc_info[next_payment_date]" date_val=$subscriptions.next_payment_date  start_year=$settings.Company.company_start_year extra="onchange=\"Tygh.$('#period_selects').val('C');\"" date_meta=$date_meta}
            </div>
        </div>

        <script>
            // 発送日の切り替え
            function fn_change_sonys_deliver_send_day(object){

                $('#jp_sonys_deliver_send_w').addClass("hidden");
                $('#jp_sonys_deliver_send_bw').addClass("hidden");
                $('#jp_sonys_deliver_send_m').addClass("hidden");
                $('#jp_sonys_deliver_send_bm').addClass("hidden");
                $('#jp_sonys_deliver_send_' + object.value).removeClass("hidden");

            }
        </script>

        <hr/>

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
        {assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
        {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}

        {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

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
                    <th  class="left">
                        {include file="common/check_items.tpl" check_statuses=$order_status_descr}
                    </th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "order_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("status")}{if $search.sort_by == "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=cc_status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("jp_sonys_status")}{if $search.sort_by == "cc_status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by == "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="20%" class="center"><a class="cm-ajax" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("customer")}{if $search.sort_by == "customer"}{$c_icon nofilter}{/if}</a></th>
                    <th>&nbsp;</th>
                    <th width="20%" class="right"><a class="cm-ajax{if $search.sort_by == "total"} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=total&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("total")}</a></th>

                </tr>
                </thead>
                {foreach from=$orders item="o"}
                    <tr>
                        <td class="left">
                            <input type="checkbox" name="order_ids[]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                        <td>
                            <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="underlined">{__("order")} #{$o.order_id}</a>
                            {if $order_statuses_data[$o.status].params.appearance_type == "I" && $o.invoice_id}
                                <p class="small-note">{__("invoice")} #{$o.invoice_id}</p>
                            {elseif $order_statuses_data[$o.status].params.appearance_type == "C" && $o.credit_memo_id}
                                <p class="small-note">{__("credit_memo")} #{$o.credit_memo_id}</p>
                            {/if}
                            {include file="views/companies/components/company_name.tpl" object=$o}
                        </td>
                        <td>{$order_status_descr[$o.status]}</td>
                        <td>{$o.cc_status_code|fn_sonys_get_cc_status_name}</td>
                        <td>{$o.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                        <td class="center">{if $o.user_id}<a href="{"profiles.update?user_id=`$o.user_id`"|fn_url}">{/if}{$o.firstname} {$o.lastname}{if $o.user_id}</a>{/if}</td>
                        <td width="5%" class="center">
                            {capture name="tools_items"}
                                {assign var="current_redirect_url" value=$config.current_url|escape:url}
                                {if $o.cc_status_code == '1Auth'}
                                    <li><a class="cm-confirm" href="{"sonys_subsc_manager.cc_sales_confirm?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_sales_confirm")}</a></li>
                                    <li><a class="cm-confirm" href="{"sonys_subsc_manager.cc_auth_cancel?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_auth_cancel")}</a></li>
                                {elseif $o.cc_status_code == '1Gathering' || $o.cc_status_code == 'sales_confirm'}
                                    <li><a class="cm-confirm" href="{"sonys_subsc_manager.cc_sales_cancel?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_sales_cancel")}</a></li>
                                {/if}
                            {/capture}
                            <div class="hidden-tools">
                                {dropdown content=$smarty.capture.tools_items}
                            </div>
                        </td>
                        <td class="right">
                            {include file="common/price.tpl" value=$o.total}
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
    {if $subscriptions}
    {include file="buttons/save.tpl" but_meta="cm-product-save-buttons" but_role="submit-link" but_name="dispatch[sonys_subsc_manager.subsc_update]" but_target_form="sonys_orders_list_form" save=$id}
    {/if}
    {if $orders}
        {capture name="tools_list"}
            <li>{btn type="list" text={__("jp_sonys_sales_confirm")} dispatch="dispatch[sonys_subsc_manager.bulk_sales_confirm]" form="sonys_orders_list_form" class="cm-process-items cm-confirm"}</li>
            <li>{btn type="list" text={__("jp_sonys_auth_cancel")} dispatch="dispatch[sonys_subsc_manager.bulk_auth_cancel]" form="sonys_orders_list_form" class="cm-process-items cm-confirm"}</li>
            <li>{btn type="list" text={__("jp_sonys_sales_cancel")} dispatch="dispatch[sonys_subsc_manager.bulk_sales_cancel]" form="sonys_orders_list_form" class="cm-process-items cm-confirm"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=__("jp_sonys_subpay_order_history", ["[subpay_id]"=>$search.subpay_id]) sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}

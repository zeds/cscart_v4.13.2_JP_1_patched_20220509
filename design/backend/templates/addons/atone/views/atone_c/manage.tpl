{* Modified by tommy from cs-cart.jp 2016 *}

{capture name="mainbox"}

    {if $runtime.mode == "new"}
        <p>{__("text_admin_new_orders")}</p>
    {/if}

    {capture name="sidebar"}
        {include file="addons/atone/views/atone_c/components/orders_search_form.tpl" dispatch="atone_c.manage"}
    {/capture}

    <form action="{""|fn_url}" method="post" target="_self" name="atone_orders_list_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
        {assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
        {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}

        {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

        {if $incompleted_view}
            {assign var="page_title" value=__("atone_c")}
            {assign var="get_additional_statuses" value=true}
        {else}
            {assign var="page_title" value=__("atone_c")}
            {assign var="get_additional_statuses" value=false}
        {/if}
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
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("date")}{if $search.sort_by == "date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%">{__("atone_provisional_title")}</th>
                    <th width="15%">{__("atone_sales")}</th>
                    <th width="15%">{__("cancel")}</th>
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
                        <td>{$o.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                        <td>{if $o.provisional}{__("atone_provisional")}{else}{__("already_atone_sales")}{/if}</td>
                        <td>{if $o.sales_settled}{__("atone_unsettled")}{else}{__("atone_sales_settled")}{/if}</td>
                        <td>{if $o.refunds}{__("atone_canceled")}{/if}</td>
                        <td class="center">{if $o.user_id}<a href="{"profiles.update?user_id=`$o.user_id`"|fn_url}">{/if}{$o.firstname} {$o.lastname}{if $o.user_id}</a>{/if}</td>
                        <td width="5%" class="center">
                            {capture name="tools_items"}
                            {assign var="current_redirect_url" value=$config.current_url|escape:url}
                                {if $o.sales_settled}
                                    <li><a class="cm-confirm" href="{"atone_c.sales_settled?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("atone_sales")}</a></li>
                                {/if}
                                {if $o.provisional}
									<li><a class="cm-confirm" href="{"atone_c.register?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("atone_register_do")}</a></li>
								{/if}
                                {if !$o.refunds}
                                    <li><a class="cm-confirm" href="{"atone_c.cancel?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("cancel")}</a></li>
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


        {capture name="adv_buttons"}
            {include file="common/tools.tpl" tool_href="order_management.new" prefix="bottom" hide_tools="true" title=__("add_order") icon="icon-plus"}
        {/capture}

    </form>
{/capture}

{capture name="buttons"}
    {if $orders}
        {capture name="tools_list"}
            <li>{btn type="list" text={__("atone_sales")} dispatch="dispatch[atone_c.sales_settled]" form="atone_orders_list_form" class="cm-process-items cm-confirm"}</li>
            <li>{btn type="list" text={__("atone_register_do")} dispatch="dispatch[atone_c.register]" form="atone_orders_list_form" class="cm-process-items cm-confirm"}</li>
            <li>{btn type="list" text={__("cancel")} dispatch="dispatch[atone_c.cancel]" form="atone_orders_list_form" class="cm-process-items cm-confirm"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}

{* Modified by takahashi from cs-cart.jp 2019 *}

{capture name="mainbox"}

    {capture name="sidebar"}
        {include file="addons/sonypayment_subpay/views/sonys_subsc_manager/components/subsc_search_form.tpl" dispatch="sonys_subsc_manager.manage"}
    {/capture}

    <form action="{""|fn_url}" method="post" target="_self" name="sonys_subsc_list_form">

        {include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
        {assign var="c_icon" value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
        {assign var="c_dummy" value="<i class=\"exicon-dummy\"></i>"}

        {assign var="rev" value=$smarty.request.content_id|default:"pagination_contents"}

        {assign var="page_title" value=__("jp_sonys_subsc_manager")}

        {if $subscriptions}
        <div class="table-wrapper">
            <table width="100%" class="table table-middle">
                <thead>
                <tr>
                    <th  class="left">
                        {include file="common/check_items.tpl" check_statuses=$order_status_descr}
                    </th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=subpay_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("id")}{if $search.sort_by == "subpay_id"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="10%">{__("jp_sonys_subsc_cnt")}</th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=next_payment_date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("jp_sonys_next_send_date")}{if $search.sort_by == "next_payment_date"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="15%" class="center"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("jp_sonys_subsc_status")}{if $search.sort_by == "status"}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                    <th width="20%">{__("jp_sonys_order_id_status")}</th>
                    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=user_name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__("customer")}{if $search.sort_by == "user_name"}{$c_icon nofilter}{/if}</a></th>
                    <th width="10%"></th>
                </tr>
                </thead>
                {foreach from=$subscriptions item="s"}
                <tr>
                    <td class="left">
                        <input type="checkbox" name="subpay_ids[]" value="{$s.subpay_id}" class="cm-item cm-item-status-{$o.status|lower}" /></td>
                    <td>
                        <a href="{"sonys_subsc_manager.details?subpay_id=`$s.subpay_id`"|fn_url}" class="underlined">{__("jp_sonys_subscription_payment")} #{$s.subpay_id}</a>
                    </td>
                    <td>{($s.subpay_id|fn_sonys_get_subsc_orders)|count}</td>
                    <td>{$s.next_payment_date|date_format:"`$settings.Appearance.date_format`"}</td>
                    <td class="center">
                        {$subsc_status[$s.status]}
                    </td>
                    {assign var="order_id" value=($s.subpay_id|fn_sonys_get_subsc_orders)|max}
                    {assign var="order_data" value=['order_id'=>$order_id]|fn_get_orders_status}
                    {assign var="order_status_descr" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}
                    <td><a href="{"orders.details?order_id=`$order_id`"|fn_url}" class="underlined">#{$order_id}</a>/{$order_status_descr[$order_data.0.status]}
                    </td>
                    <td>
                        <a href="{"profiles.update&user_id=`$s.user_id`&user_type=C"|fn_url}" class="underlined">{$s.user_name}</a>
                    </td>
                    <td>
                        {capture name="tools_items"}
                            {assign var="current_redirect_url" value=$config.current_url|escape:url}
                            {if $s.status == 'A'}
                                <li><a class="cm-confirm" href="{"sonys_subsc_manager.cc_process?subpay_id=`$s.subpay_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_process")}</a></li>
                                <li><a class="cm-confirm" href="{"sonys_subsc_manager.status_update?subpay_id=`$s.subpay_id`&amp;status_to=P&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_subsc_status_p")}</a></li>
                                <li><a class="cm-confirm" href="{"sonys_subsc_manager.status_update?subpay_id=`$s.subpay_id`&amp;status_to=D&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_subsc_status_D")}</a></li>
                            {elseif $s.status == 'P'}
                                <li><a class="cm-confirm" href="{"sonys_subsc_manager.status_update?subpay_id=`$s.subpay_id`&amp;status_to=A&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_subsc_status_A")}</a></li>
                                <li><a class="cm-confirm" href="{"sonys_subsc_manager.status_update?subpay_id=`$s.subpay_id`&amp;status_to=D&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("jp_sonys_subsc_status_D")}</a></li>
                            {/if}
                        {/capture}
                        <div class="hidden-tools">
                            {dropdown content=$smarty.capture.tools_items}
                        </div>
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
        {capture name="tools_list"}
            <li>{btn type="list" text={__("jp_sonys_bulk_process")} dispatch="dispatch[sonys_subsc_manager.bulk_process]" form="sonys_subsc_list_form" class="cm-process-items cm-confirm"}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{include file="common/mainbox.tpl" title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}

{capture name='mainbox'}
    {if $runtime.mode == 'new'}
        <p>{__('text_admin_new_orders')}</p>
    {/if}

    {capture name='sidebar'}
        {include file='addons/ap_sbps/views/ap_sbps_career_manager/components/orders_search_form.tpl' dispatch='ap_sbps_career_manager.manage'}
    {/capture}

    {include file='addons/ap_sbps/components/ap_sbps_manager_tabs.tpl'}

    <form action="{''|fn_url}" method="post" target="_self" name="ap_sbps_orders_list_form">
        {include file='common/pagination.tpl' save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

        {assign var='c_url' value=$config.current_url|fn_query_remove:'sort_by':'sort_order'}
        {assign var='c_icon' value="<i class=\"exicon-`$search.sort_order_rev`\"></i>"}
        {assign var='c_dummy' value='<i class=\"exicon-dummy\"></i>'}

        {assign var='rev' value=$smarty.request.content_id|default:'pagination_contents'}

        {if $incompleted_view}
            {assign var='page_title' value=__('incompleted_orders')}
            {assign var='get_additional_statuses' value=true}
        {else}
            {assign var='page_title' value=__('orders')}
            {assign var='get_additional_statuses' value=false}
        {/if}
        {assign var='order_status_descr' value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:$get_additional_statuses:true}
        {assign var='extra_status' value=$config.current_url|escape:'url'}
        {$statuses = []}
        {assign var='order_statuses' value=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}

        {if $orders}
            <div class="table-responsive-wrapper">
                <table width="100%" class="table table-middle table-responsive">
                    <thead>
                    <tr>
                        <th class="left mobile-hide">
                            {include file='common/check_items.tpl' check_statuses=$order_status_descr}
                        </th>
                        <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=order_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('id')}{if $search.sort_by == 'order_id'}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('status')}{if $search.sort_by == 'status'}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=payment_status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('sbps_payment_status')}{if $search.sort_by == 'payment_status'}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('date')}{if $search.sort_by == 'date'}{$c_icon nofilter}{else}{$c_dummy nofilter}{/if}</a></th>
                        <th width="20%" class="center"><a class="cm-ajax" href="{"`$c_url`&sort_by=customer&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('customer')}{if $search.sort_by == 'customer'}{$c_icon nofilter}{/if}</a></th>
                        <th>&nbsp;</th>
                        <th width="20%" class="right"><a class="cm-ajax{if $search.sort_by == 'total'} sort-link-{$search.sort_order_rev}{/if}" href="{"`$c_url`&sort_by=total&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id={$rev}>{__('total')}</a></th>
                    </tr>
                    </thead>
                    {foreach from=$orders item='o'}
                        <tr>
                            <td class="left mobile-hide">
                                <input type="checkbox" name="order_ids[]" value="{$o.order_id}" class="cm-item cm-item-status-{$o.status|lower}" />
                            </td>
                            <td data-th="{__('id')}">
                                <a href="{"orders.details?order_id=`$o.order_id`"|fn_url}" class="underlined">{__('order')} #{$o.order_id}</a>
                                {if $order_statuses_data[$o.status].params.appearance_type == 'I' && $o.invoice_id}
                                    <p class="muted">{__('invoice')} #{$o.invoice_id}</p>
                                {elseif $order_statuses_data[$o.status].params.appearance_type == 'C' && $o.credit_memo_id}
                                    <p class="muted">{__('credit_memo')} #{$o.credit_memo_id}</p>
                                {/if}
                                {include file='views/companies/components/company_name.tpl' object=$o}
                            </td>
                            <td data-th="{__('status')}">{$order_status_descr[$o.status]}</td>
                            <td data-th="{__('sbps_payment_status')}">
                                {if $o.payment_status}
                                    {__("sbps_payment_status_`$o.payment_status`")}
                                {/if}
                            </td>
                            <td data-th="{__('date')}">{$o.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                            <td class="center" data-th="{__('customer')}">
                                {if $o.user_id}
                                <a href="{"profiles.update?user_id=`$o.user_id`"|fn_url}">{/if}{$o.firstname} {$o.lastname}{if $o.user_id}</a>
                                {/if}
                            </td>
                            <td width="5%" class="center" data-th="{__('tools')}">
                                {capture name='tools_items'}
                                    {assign var='current_redirect_url' value=$config.current_url|escape:url}
                                    {assign var='exec_permission' value=$smarty.const.SBPS_EXEC_PERMISSION}
                                    {foreach from=$exec_permission[$o.pay_method]['status'][$o.payment_status] key=key item=mode}
                                        {if $mode|substr:0:1 != '_'}
                                            <li><a class="cm-confirm" href="{"ap_sbps_career_manager.`$mode`?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("sbps_`$mode`")}</a></li>
                                        {/if}
                                    {/foreach}
                                    {foreach from=$exec_permission[$o.pay_method]['all'] key=key item=mode}
                                        {if $mode|substr:0:1 != '_'}
                                            <li><a class="cm-confirm" href="{"ap_sbps_career_manager.`$mode`?order_id=`$o.order_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("sbps_`$mode`")}</a></li>
                                        {/if}
                                    {/foreach}
                                {/capture}
                                <div class="hidden-tools">
                                    {dropdown content=$smarty.capture.tools_items}
                                </div>
                            </td>
                            <td class="right" data-th="{__('total')}">
                                {include file='common/price.tpl' value=$o.total}
                            </td>
                        </tr>
                    {/foreach}
                </table>
            </div>
        {else}
            <p class="no-items">{__('no_data')}</p>
        {/if}

        {include file='common/pagination.tpl' div_id=$smarty.request.content_id}
    </form>
{/capture}

{capture name='buttons'}
    {if $orders}
        {capture name='tools_list'}
            <li>{btn type='list' text={__('sbps_sales_confirm')} dispatch='dispatch[ap_sbps_career_manager.sales_confirm]' form='ap_sbps_orders_list_form' class='cm-process-items cm-confirm cm-submit'}</li>
            <li>{btn type='list' text={__('sbps_cancel')} dispatch='dispatch[ap_sbps_career_manager.cancel]' form='ap_sbps_orders_list_form' class='cm-process-items cm-confirm cm-submit'}</li>
        {/capture}
        {dropdown content=$smarty.capture.tools_list}
    {/if}
{/capture}

{include file='common/mainbox.tpl' title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id='manage_orders'}

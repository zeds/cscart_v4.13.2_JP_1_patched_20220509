{* $Id: view.tpl by takahashi from cs-cart.jp 2019 *}
{* Modified by takahashi from cs-cart.jp 2020 *}
{* 配送先住所更新対応 *}

{capture name="section"}
	{include file="addons/sonypayment_subpay/views/sonys_subpay_list/components/subpay_search_form.tpl"}
{/capture}
{include file="common/section.tpl" section_title=__("search_options") section_content=$smarty.capture.section class="ty-search-form" collapse=true}

{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
{if $search.sort_order == "asc"}
	{assign var="sort_sign" value="<i class=\"ty-icon-down-dir\"></i>"}
{else}
	{assign var="sort_sign" value="<i class=\"ty-icon-up-dir\"></i>"}
{/if}
{if !$config.tweaks.disable_dhtml}
	{assign var="ajax_class" value="cm-ajax"}

{/if}

{include file="common/pagination.tpl"}

<table class="ty-table ty-orders-search">
	<thead>
	<tr>
		<th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=subpay_id&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("id")}</a>{if $search.sort_by == "subpay_id"}{$sort_sign nofilter}{/if}</th>
		<th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=user_name&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("customer")}</a>{if $search.sort_by == "user_name"}{$sort_sign nofilter}{/if}</th>
		<th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=product&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("product")}</a>{if $search.sort_by == "product"}{$sort_sign nofilter}{/if}</th>
		<th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=next_payment_date&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("jp_sonys_next_send_date")}</a>{if $search.sort_by == "next_payment_date"}{$sort_sign nofilter}{/if}</th>
		<th><a class="{$ajax_class}" href="{"`$c_url`&sort_by=status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("jp_sonys_subsc_status")}</a>{if $search.sort_by == "status"}{$sort_sign nofilter}{/if}</th>
		{if $addons.sonypayment_subpay.is_user_cancel == 'Y'}
		<th></th>
		{/if}
		{* Modified by takahashi from cs-cart.jp 2020 BOF *}
		{* 配送先住所更新対応 *}
		<th></th>
		{* Modified by takahashi from cs-cart.jp 2020 EOF *}
	</tr>
	</thead>
	{foreach from=$subscriptions item="s"}
		<tr>
			<td class="ty-orders-search__item">{__("jp_sonys_subscription_payment")} #{$s.subpay_id}</td>
			<td class="ty-orders-search__item">{$s.user_name}</td>
			<td class="ty-orders-search__item">{$s.product}</td>
			<td class="ty-orders-search__item">{$s.next_payment_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
			<td class="ty-orders-search__item">{$subsc_status[$s.status]}</td>
			{if $addons.sonypayment_subpay.is_user_cancel == 'Y'}
			<td class="ty-orders-search__item" align="center">
				{if $s.status == "A"}
					{include file="buttons/button.tpl" but_text=__("jp_sonys_subsc_status_p") but_role="action" but_href="sonys_subpay_list.status_update?subpay_id=`$s.subpay_id`&status_to=P"}
				{elseif $s.status == "P"}
					{include file="buttons/button.tpl" but_text=__("jp_sonys_subsc_status_a") but_role="action" but_href="sonys_subpay_list.status_update?subpay_id=`$s.subpay_id`&status_to=A"}
				{/if}
			</td>
			{/if}
			{* Modified by takahashi from cs-cart.jp 2020 BOF *}
			{* 配送先住所更新対応 *}
			<td class="ty-orders-search__item" align="center">
				{include file="buttons/button.tpl" but_text=__("jp_sonys_subsc_change_ship_addr") but_role="action" but_href="sonys_subpay_list.change_ship_address?subpay_id=`$s.subpay_id`"}
			</td>
			{* Modified by takahashi from cs-cart.jp 2020 EOF *}
		</tr>
		{foreachelse}
		<tr class="ty-table__no-items">
			<td colspan="6"><p class="ty-no-items">{__("text_no_orders")}</p></td>
		</tr>
	{/foreach}
</table>

{include file="common/pagination.tpl"}

{capture name="mainbox_title"}{__("orders")}{/capture}

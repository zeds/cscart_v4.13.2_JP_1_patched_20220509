<form action="{""|fn_url}" class="ty-orders-search-options" name="subpay_search_form" method="get">

<div class="clearfix">
    <div class="span4 ty-control-group">
        <label class="ty-control-group__title">{__("product")}</label>
        <input type="text" name="product" value="{$search.product}" size="10" class="ty-search-form__input" />
    </div>

    <div class="span3 ty-control-group">
        <label class="ty-control-group__title">{__("status")}</label>
        <select id="status" name="status">
            <option value=""></option>
            {foreach from=$subsc_status item="status_item" key="status_key"}
                <option {if $search.status == $status_key}selected="selected"{/if} value="{$status_key}">{$status_item}</option>
            {/foreach}
        </select>
    </div>

    <div class="span5 ty-control-group">
        <label class="ty-control-group__title">{__("jp_sonys_next_send_date")}</label>
        {include file="common/calendar.tpl" date_id="f_date" date_name="time_from" date_val=$search.time_from  start_year=$settings.Company.company_start_year extra="onchange=\"Tygh.$('#period_selects').val('C');\"" date_meta=$date_meta}
        &nbsp;&nbsp;{__("jp_sonys_date_from")}&nbsp;&nbsp;
        {include file="common/calendar.tpl" date_id="t_date" date_name="time_to" date_val=$search.time_to  start_year=$settings.Company.company_start_year extra="onchange=\"Tygh.$('#period_selects').val('C');\"" date_meta=$date_meta}
        &nbsp;&nbsp;{__("jp_sonys_date_to")}
    </div>

</div>


<div class="buttons-container ty-search-form__buttons-container">
    {include file="buttons/button.tpl" but_meta="ty-btn__secondary" but_text=__("search") but_name="dispatch[sonys_subpay_list.view]"}
</div>
</form>
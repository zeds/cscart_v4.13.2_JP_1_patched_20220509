{* Modified by takahashi from cs-cart.jp 2019 *}

{if $in_popup}
<div class="adv-search">
    <div class="group">
        {else}
        <div class="sidebar-row">
            <h6>{__("search")}</h6>
            {/if}

            <form action="{""|fn_url}" name="orders_search_form" method="get" class="{$form_meta}">
                {capture name="simple_search"}

                    {if $smarty.request.redirect_url}
                        <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
                    {/if}
                    {if $selected_section != ""}
                        <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
                    {/if}

                    {$extra nofilter}

                    <div class="sidebar-field">
                        <label for="user_name">{__("customer")}</label>
                        <input type="text" name="user_name" id="user_name" value="{$search.user_name}" size="30" />
                    </div>

                    <div class="sidebar-field">
                        <label for="product">{__("product")}</label>
                        <input type="text" name="product" id="email" value="{$search.product}" size="30"/>
                    </div>

                    <div class="sidebar-field">
                        <label for="status">{__("jp_sonys_subsc_status")}</label>
                        <select id="status" name="status">
                            <option value=""></option>
                            {foreach from=$subsc_status item="status_item" key="status_key"}
                                <option {if $search.status == $status_key}selected="selected"{/if} value="{$status_key}">{$status_item}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="sidebar-field">
                        <label for="f_date">{__("jp_sonys_next_send_date")}</label>
                        {include file="common/calendar.tpl" date_id="f_date" date_name="time_from" date_val=$search.time_from  start_year=$settings.Company.company_start_year extra="onchange=\"Tygh.$('#period_selects').val('C');\"" date_meta=$date_meta}
                        &nbsp;&nbsp;{__("jp_sonys_date_from")}
                        {include file="common/calendar.tpl" date_id="t_date" date_name="time_to" date_val=$search.time_to  start_year=$settings.Company.company_start_year extra="onchange=\"Tygh.$('#period_selects').val('C');\"" date_meta=$date_meta}
                        &nbsp;&nbsp;{__("jp_sonys_date_to")}
                    </div>

                {/capture}

                {include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="orders" in_popup=$in_popup not_saved=true no_adv_link=true}

            </form>

            {if $in_popup}
        </div></div>
    {else}
</div><hr>
{/if}
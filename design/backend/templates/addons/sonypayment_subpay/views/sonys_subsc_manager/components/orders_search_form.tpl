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

                    <input type="hidden" name="subpay_id" value="{$search.subpay_id}">

                    {if $smarty.request.redirect_url}
                        <input type="hidden" name="redirect_url" value="{$smarty.request.redirect_url}" />
                    {/if}
                    {if $selected_section != ""}
                        <input type="hidden" id="selected_section" name="selected_section" value="{$selected_section}" />
                    {/if}

                    {$extra nofilter}

                    <div class="sidebar-field">
                        <label for="cname">{__("customer")}</label>
                        <input type="text" name="cname" id="cname" value="{$search.cname}" size="30" />
                    </div>

                    <div class="sidebar-field">
                        <label for="email">{__("email")}</label>
                        <input type="text" name="email" id="email" value="{$search.email}" size="30"/>
                    </div>

                    <div class="sidebar-field">
                        <label for="total_from">{__("total")}&nbsp;({$currencies.$primary_currency.symbol nofilter})</label>
                        <input type="text" class="input-small" name="total_from" id="total_from" value="{$search.total_from}" size="3" /> - <input type="text" class="input-small" name="total_to" value="{$search.total_to}" size="3" />
                    </div>

                {/capture}

                {capture name="advanced_search"}
                        <div class="group form-horizontal">
                            <div class="control-group">
                                <label class="control-label">{__("period")}</label>
                                <div class="controls">
                                    {include file="common/period_selector.tpl" period=$search.period form_name="orders_search_form"}
                                </div>
                            </div>
                        </div>

                        <div class="group">
                            {if $incompleted_view}
                                <input type="hidden" name="status" value="{$smarty.const.STATUS_INCOMPLETED_ORDER}" />
                            {else}
                                <div class="control-group">
                                    <label class="control-label">{__("order_status")}</label>
                                    <div class="controls checkbox-list">
                                        {include file="common/status.tpl" status=$search.status display="checkboxes" name="status" columns=5}
                                    </div>
                                </div>
                            {/if}
                        </div>

                        <div class="group">
                            <div class="control-group">
                                <label class="control-label">{__("ordered_products")}</label>
                                <div class="controls ">
                                    {include file="common/products_to_search.tpl" placement="right"}
                                </div>
                            </div>
                        </div>
                {/capture}

                {include file="common/advanced_search.tpl" simple_search=$smarty.capture.simple_search advanced_search=$smarty.capture.advanced_search dispatch=$dispatch view_type="orders" in_popup=$in_popup not_saved=true}

            </form>

            {if $in_popup}
        </div></div>
    {else}
</div><hr>
{/if}
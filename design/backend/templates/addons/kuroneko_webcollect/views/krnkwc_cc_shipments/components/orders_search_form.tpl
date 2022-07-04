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
                        <label for="shipping">{__("shipping_method")}</label>
                        <input type="text" name="shipping" id="shipping" value="{$search.shipping}" size="30" />
                    </div>

                    <div class="sidebar-field">
                        <label for="tracking_number">{__("jp_kuroneko_webcollect_slip_no")}</label>
                        <input type="text" name="tracking_number" id="tracking_number" value="{$search.tracking_number}" size="30"/>
                    </div>

                    <div class="sidebar-field">
                        <label for="carrier">{__("carrier")}</label>
                        {include file="common/carriers.tpl" id="carrier_key" meta="input-medium" name="carrier" carrier=$search.carrier}
                    </div>

                    <div class="sidebar-field">
                        <label for="delivery_service_code">{__("jp_kuroneko_webcollect_delivery_service")}</label>
                        <select name="delivery_service_code" calss="input-medium">
                            <option value="">-</option>
                            <option value="00" {if $search.delivery_service_code == '00'}selected{/if}>{__("jp_kuroneko_webcollect_delivery_service_00")}</option>
                            <option value="99" {if $search.delivery_service_code == '99'}selected{/if}>{__("jp_kuroneko_webcollect_delivery_service_99")}</option>
                        </select>
                    </div>

                {/capture}

                {capture name="advanced_search"}
                    <div class="group form-horizontal">
                        <div class="control-group">
                            <label class="control-label">{__("created")}</label>
                            <div class="controls">
                                {include file="common/period_selector.tpl" period=$search.period prefix="shipment_" form_name="orders_search_form"}
                            </div>
                        </div>
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
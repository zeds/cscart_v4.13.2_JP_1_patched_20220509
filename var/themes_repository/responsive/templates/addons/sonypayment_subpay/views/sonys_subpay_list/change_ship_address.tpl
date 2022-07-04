{* $Id: change_ship_address.tpl by takahashi from cs-cart.jp 2020 *}

<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>

<form id='form' action="{""|fn_url}" method="post" name="ship_address_form" class="form-horizontal form-edit  cm-disable-empty-files" enctype="multipart/form-data">

    {$_country = $settings.General.default_country}

	<div>
        <div class="control-group">
            <label for="subpay_id" class="ty-control-group__title">{__("id")}</label>
            <div class="controls" id="subpay_id">
                <p>{__("jp_sonys_subscription_payment")} #{$subpay_id}</p>
            </div>
            <input type="hidden" id="subpay_id" name="subpay_ship_addr[subpay_id]" value="{$subpay_id}"/>
        </div>

		<div class="ty-control-group">
			<label for="s_zipcode" class="ty-control-group__title cm-required">{__("zip_postal_code")}</label>
			<input type="text" id="s_zipcode" name="subpay_ship_addr[s_zipcode]" size="32" maxlength="30" value="{$subpay_ship_addr.s_zipcode}" class="ty-input-text" onKeyUp="AjaxZip3.zip2addr(this,'','subpay_ship_addr[s_state]','subpay_ship_addr[s_city]','','subpay_ship_addr[address]');"/>
		</div>

        <div class="ty-control-group">
            <label for="s_state" class="ty-control-group__title cm-required">{__("state")}</label>
            <select id="s_state" name="subpay_ship_addr[s_state]">
                <option value="">- {__("select_state")} -</option>
                {if $states && $states.$_country}
                    {foreach from=$states.$_country item=state}
                        <option {if $subpay_ship_addr.s_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
                    {/foreach}
                {/if}
            </select>
        </div>

        <div class="ty-control-group">
            <label for="s_city" class="ty-control-group__title cm-required">{__("city")}</label>
            <input type="text" id="s_city" name="subpay_ship_addr[s_city]" size="32" maxlength="30" value="{$subpay_ship_addr.s_city}" class="ty-input-text" />
        </div>

        <div class="ty-control-group">
            <label for="s_address" class="ty-control-group__title cm-required">{__("address")}</label>
            <input type="text" id="s_address" name="subpay_ship_addr[s_address]" size="32" maxlength="30" value="{$subpay_ship_addr.s_address}" class="ty-input-text" />
        </div>

        <div class="ty-control-group">
            <label for="s_address_2" class="ty-control-group__title">{__("address_2")}</label>
            <input type="text" id="s_address_2" name="subpay_ship_addr[s_address_2]" size="32" maxlength="30" value="{$subpay_ship_addr.s_address_2}" class="ty-input-text" />
        </div>

        <div class="ty-control-group">
            <label for="same_addr_for_all" class="ty-control-group__title">{__("jp_sonys_subsc_same_for_all")}</label>
            <input type="checkbox" id="same_addr_for_all" name="subpay_ship_addr[same_addr_for_all]" value="Y" class="cm-agreement checkbox"/>
        </div>

    </div>

    <div class="buttons-container cm-confirm">
        {include file="buttons/button.tpl" but_name="dispatch[sonys_subpay_list.update_ship_address]" but_text=__("update")}
    </div>

</form>

{capture name="mainbox_title"}{__("jp_sonys_subsc_change_ship_addr")}{/capture}
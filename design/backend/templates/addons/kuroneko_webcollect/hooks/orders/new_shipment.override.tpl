{* Modified by takahashi from cs-cart.jp 2019 BOF *}
{assign var='processor_data' value=$order_info.order_id|fn_krnkwc_get_processor_data_by_order_id}
<div class="control-group">
    <label class="control-label" for="tracking_number" id="lbl_tracking_number_jp">{if $order_info.pay_by_kuroneko == 'Y'}{__("jp_kuroneko_webcollect_slip_no")}{else}{__("tracking_number")}{/if}</label>
{* Modified by takahashi from cs-cart.jp 2019 EOF *}
    <div class="controls">
        <input id="tracking_number" class="input-small" type="text" name="update_shipping[{$shipping.group_key}][{$shipment_id}][tracking_number]" size="45" value="" />
        <input type="hidden" name="update_shipping[{$shipping.group_key}][{$shipment_id}][shipping_id]" value="{$shipping.shipping_id}" />
    </div>
</div>
<div class="control-group">
    <label class="control-label" for="carrier_key">{__("carrier")}</label>
    <div class="controls">
        {include file="common/carriers.tpl" id="carrier_key" meta="input-small" name="update_shipping[`$shipping.group_key`][`$shipment_id`][carrier]" carrier=$carrier}
    </div>
</div>
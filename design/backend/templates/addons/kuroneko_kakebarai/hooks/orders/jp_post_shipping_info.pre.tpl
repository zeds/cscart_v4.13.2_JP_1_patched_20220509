{* jp_post_shipping_info.pre.tpl by takahashi from cs-cart.jp 2018 *}
{if $order_info.pay_by_kuroneko_kakebarai == 'Y'}
<div class="control-group">
    <label class="control-label" for="carrier_key">{__("jp_kuroneko_kakebarai_send_slip_no")}</label>
    <div class="controls">
        {if $shipments[$shipping.group_key].tracking_number}
            {assign var="krnkkb_send_shipment" value="N"}
        {else}
            {assign var="krnkkb_send_shipment" value="Y"}
        {/if}
        <input type="checkbox" name="update_shipping[{$shipping.group_key}][{$shipment_id}][send_slip_no]" id="krnkkb_send_slip_no" value="Y"{if $krnkkb_send_shipment == 'Y'} checked{/if} />
    </div>
</div>
{/if}
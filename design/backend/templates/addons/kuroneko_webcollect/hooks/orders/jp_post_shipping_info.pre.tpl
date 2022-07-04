{* jp_post_shipping_info.pre.tpl by tommy from cs-cart.jp 2016 *}
{* Modified by takahashi from cs-cart.jp 2019 *}
{* 出荷情報登録仕様変更対応（他社配送） *}
{* Modified by takahashi from cs-cart.jp 2020 *}
{* ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い） *}
{assign var='processor_data' value=$order_info.order_id|fn_krnkwc_get_processor_data_by_order_id}
{if $order_info.pay_by_kuroneko == 'Y'}
<div class="control-group">
    <label class="control-label" for="carrier_key">{if $order_info.pay_by_kuroneko_atobarai == 'Y'}{__("jp_kuroneko_webcollect_ab_send_payment_no")}{else}{__("jp_kuroneko_webcollect_send_slip_no")}{/if}</label>
    <div class="controls">
        {if $shipments[$shipping.group_key].tracking_number}
            {assign var="krnk_send_shipment" value="N"}
        {else}
            {assign var="krnk_send_shipment" value="Y"}
        {/if}
        <input type="checkbox" name="update_shipping[{$shipping.group_key}][{$shipment_id}][send_slip_no]" id="krnkwc_send_slip_no" value="Y"{if $krnk_send_shipment == 'Y'} checked{/if} {if $order_info.pay_by_kuroneko_atobarai != 'Y' && ($processor_data.processor_script == 'krnkwc_cctkn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php')}onchange="fn_display_delivery_service_jp(this);"{/if}/>
    </div>
</div>
{* Modified by takahashi from cs-cart.jp 2020 BOF *}
{* ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い） *}
{if $order_info.pay_by_kuroneko_atobarai == 'Y' || $processor_data.processor_script == 'krnkwc_cctkn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php' }
{* Modified by takahashi from cs-cart.jp 2020 EOF *}
<div class="control-group" id="kuroneko_webcollect_delivery_service_jp">
    <label class="control-label">{__("jp_kuroneko_webcollect_delivery_service")}</label>
    <div class="controls">
        <label for="elm_file_agreement_other" class="checkbox">
            <input type="checkbox" name="update_shipping[{$shipping.group_key}][{$shipment_id}][delivery_service]" id="elm_file_agreement_other_jp" value="99""/>
            {__("jp_kuroneko_webcollect_delivery_service_99")}</label>
    </div>
</div>
{/if}
{/if}


{literal}
<script>
    function fn_display_delivery_service_jp(obj) {
        if( obj.checked ) {
            Tygh.$('#kuroneko_webcollect_delivery_service_jp').show();
        }
        else {
            Tygh.$('#kuroneko_webcollect_delivery_service_jp').hide();
            Tygh.$('#lbl_tracking_number_jp').removeClass('cm-required');
        }
    }
</script>
{/literal}
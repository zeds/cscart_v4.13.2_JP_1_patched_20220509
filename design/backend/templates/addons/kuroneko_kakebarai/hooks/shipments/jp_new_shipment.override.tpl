{* Modified by takahashi from cs-cart.jp 2019 *}
{* 出荷情報登録仕様変更対応（他社配送） *}
{* Modified by takahashi from cs-cart.jp 2020 *}
{* ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い） *}
{* Modified by takahashi from cs-cart.jp 2019 BOF *}
{assign var='processor_data' value=$order_info.order_id|fn_krnkwc_get_processor_data_by_order_id}
<div class="control-group">
    {* Modified by takahashi from cs-cart.jp 2020 BOF *}
    {* ヤマト以外の伝票番号を利用する場合の出荷情報登録対応（後払い） *}
    <label class="control-label {if ($order_info.pay_by_kuroneko == 'Y' && ($processor_data.processor_script == 'krnkwc_cctkn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php')) || $order_info.pay_by_kuroneko_atobarai == 'Y'}cm-required{/if}" for="tracking_number_pu" id="lbl_tracking_number_pu_{$group_id}">{if $order_info.pay_by_kuroneko_kakebarai == 'Y'}{__("jp_kuroneko_kakebarai_slip_no")}{elseif $order_info.pay_by_kuroneko == 'Y'}{__("jp_kuroneko_webcollect_slip_no")}{else}{__("tracking_number")}{/if}</label>
    <div class="controls">
        <input type="text" name="shipment_data[tracking_number]" id="tracking_number_pu" size="10" value="" />
    </div>
</div>
{if $order_info.pay_by_kuroneko == 'Y' || $order_info.pay_by_kuroneko_atobarai == 'Y'}
    <div class="cm-toggle-button">
        <div class="control-group select-field krnkwc-send-slip-no">
            <div class="controls">
                <label for="krnkwc_send_slip_no" class="checkbox">
                    <input type="checkbox" name="shipment_data[send_slip_no]" id="krnkwc_send_slip_no" value="Y"  checked {if $order_info.pay_by_kuroneko_atobarai == 'Y' || ($processor_data.processor_script == 'krnkwc_cctkn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php')}onchange="fn_display_delivery_service(this, '{$group_id}');"{/if}/>
                    {if $order_info.pay_by_kuroneko_atobarai == 'Y'}{__("jp_kuroneko_webcollect_ab_send_payment_no")}{else}{__("jp_kuroneko_webcollect_send_slip_no")}{/if}</label>
            </div>
        </div>
    </div>
    {if $order_info.pay_by_kuroneko_atobarai == 'Y' || ($processor_data.processor_script == 'krnkwc_cctkn.php' || $processor_data.processor_script == 'krnkwc_ccrtn.php')}
        <div class="control-group" id="kuroneko_webcollect_delivery_service_{$group_id}">
            <label class="control-label">{__("jp_kuroneko_webcollect_delivery_service")}</label>
            <div class="controls">
                <label for="elm_file_agreement_other" class="checkbox">
                    <input type="checkbox" name="shipment_data[delivery_service]" id="elm_file_agreement_other_{$group_id}" value="99" onchange="fn_tracking_number_required(this, '{$group_id}');"/>
                    {__("jp_kuroneko_webcollect_delivery_service_99")}</label>
            </div>
        </div>
    {/if}
{/if}
{* Modified by takahashi from cs-cart.jp 2020 *}
{if $order_info.pay_by_kuroneko_kakebarai == 'Y'}
    <div class="cm-toggle-button">
        <div class="control-group select-field krnkkb-send-slip-no">
            <div class="controls">
                <label for="krnkkb_send_slip_no" class="checkbox">
                    <input type="checkbox" name="shipment_data[send_slip_no]" id="krnkkb_send_slip_no" value="Y"  checked />
                    {if $order_info.pay_by_kuroneko_kakebarai == 'Y'}{__("jp_kuroneko_kakebarai_send_slip_no")}{/if}</label>
            </div>
        </div>
    </div>
{/if}
<script>
    function fn_display_delivery_service(obj, group_id) {
        if( obj.checked ) {
            Tygh.$('#kuroneko_webcollect_delivery_service_' + group_id).show();

            fn_tracking_number_required(document.getElementById('elm_file_agreement_other_' + group_id), group_id);
        }
        else {
            Tygh.$('#kuroneko_webcollect_delivery_service_' + group_id).hide();
            Tygh.$('#lbl_tracking_number_pu_' + group_id).removeClass('cm-required');
        }
    }

    function fn_tracking_number_required(obj, group_id) {
        if( obj.checked ) {
            Tygh.$('#lbl_tracking_number_pu_' + group_id).removeClass('cm-required');
        }
        else {
            Tygh.$('#lbl_tracking_number_pu_' + group_id).addClass('cm-required');
        }
    }
</script>
{* Modified by takahashi from cs-cart.jp 2019 EOF *}
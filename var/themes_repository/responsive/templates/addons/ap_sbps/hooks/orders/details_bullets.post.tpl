{if $addons.subscription_payment_jp && $addons.subscription_payment_jp.status == 'A'}
    {if $order_info.is_subscription == '1' && $order_info.rb_status != $smarty.const.RB_STATUS_CANCEL_CONTRACT && $addons.ap_sbps.permit_rb_cancel_contract == 'Y'}
        {include file='buttons/button.tpl' but_meta='ty-btn__text rb_action cm-confirm' but_role='text' but_text=__('sbps_rb_cancel_contract_btn_label') but_href="orders.rb_cancel_contract?order_id=`$order_info.order_id`" but_icon='ty-orders__actions-icon ty-icon-cw'}
    {/if}
{/if}

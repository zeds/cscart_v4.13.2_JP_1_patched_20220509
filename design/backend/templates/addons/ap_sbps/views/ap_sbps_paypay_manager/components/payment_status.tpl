{foreach from=$smarty.const.SBPS_PAYPAY_PAYMENT_STATUS item=payment_status}
    <label>
        <input type="checkbox" name="{$name}[]" value="{$payment_status}"{if $checked_status && in_array($payment_status, $checked_status)} checked="checked"{/if}>{__("sbps_paypay_payment_status_`$payment_status`")}
    </label>
{/foreach}
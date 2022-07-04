{foreach from=$smarty.const.SBPS_PAYMENT_STATUS item=payment_status}
    <label>
        <input type="checkbox" name="{$name}[]" value="{$payment_status}"{if in_array($payment_status, $checked_status)} checked="checked"{/if}>{__("sbps_payment_status_`$payment_status`")}
    </label>
{/foreach}
{* $Id: remise_cc.tpl by tommy from cs-cart.jp 2014 *}

<div class="clearfix">
    {if ($payment_method.processor_params.installment == 'true' || $payment_method.processor_params.revo == 'true') || ($payment_method.processor_params.payquick == 'true' && $auth.user_id && $auth.user_id > 0)}
    <div class="ty-credit-card">
    {/if}
    {if $payment_method.processor_params.installment == 'true' || $payment_method.processor_params.revo == 'true'}
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="remese_cc_payment_type" class="ty-control-group__title cm-required">{__("jp_remise_payment_method")}</label>
            <select id="remese_cc_payment_type" name="payment_info[jp_remise_payment_method]">
                <option value="10">{__("jp_cc_onetime")}</option>
                {if $payment_method.processor_params.installment == 'true'}
                    <option value="61">{__("jp_cc_installment")}</option>
                {/if}
                {if $payment_method.processor_params.revo == 'true'}
                    <option value="80">{__("jp_cc_revo")}</option>
                {/if}
            </select>
        </div>
    {else}
        <input type="hidden" name="payment_info[remese_cc_payment_type]" value="10" />
    {/if}
    {if $payment_method.processor_params.payquick == 'true' && $auth.user_id && $auth.user_id > 0}
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="jp_remise_use_payquick" class="ty-control-group__title cm-required">{__("jp_remise_use_payquick")}</label>
            <p>
                <input type="radio" name="payment_info[jp_remise_use_payquick]" id="payquick_yes" value="Y" checked="checked" class="radio" />{__("yes")}
                &nbsp;&nbsp;
                <input type="radio" name="payment_info[jp_remise_use_payquick]" id="payquick_no" value="N" class="radio" />{__("no")}
            </p>
        </div>
        {__("jp_remise_payquick_desc")}
    {/if}
    {if ($payment_method.processor_params.installment == 'true' || $payment_method.processor_params.revo == 'true') || ($payment_method.processor_params.payquick == 'true' && $auth.user_id && $auth.user_id > 0)}
    </div>
    {/if}
</div>

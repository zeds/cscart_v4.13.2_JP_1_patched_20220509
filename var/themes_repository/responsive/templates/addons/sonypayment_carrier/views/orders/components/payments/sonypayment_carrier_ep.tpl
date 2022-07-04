{* $Id: sonypayment_carrier_ep.tpl by takahashi from cs-cart.jp 2018 *}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-control-group">
            <label for="jp_sonyc_ep_carrier" class="ty-control-group__title cm-required">{__('jp_sonypayment_carrier_crkind')}</label>
            <select id="jp_sonyc_ep_carrier" name="payment_info[carrier]">
                {if $payment_method.processor_params.carrier['01'] == 'true'}
                    <option value="01">{__("jp_sonypayment_carrier_docomo")}</option>
                {/if}
                {if $payment_method.processor_params.carrier['02'] == 'true'}
                    <option value="02">{__("jp_sonypayment_carrier_au")}</option>
                {/if}
                {if $payment_method.processor_params.carrier['03'] == 'true'}
                    <option value="03">{__("jp_sonypayment_carrier_softbank")}</option>
                {/if}
            </select>
        </div>
    </div>
</div>
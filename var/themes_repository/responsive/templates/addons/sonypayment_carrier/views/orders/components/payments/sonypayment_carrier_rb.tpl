{* $Id: sonypayment_carrier_rb.tpl by takahashi from cs-cart.jp 2018 *}

{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{assign var="rb_processor_params" value=$payment.processor_params|unserialize}
<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-control-group">
            <label for="jp_sonyc_rb_carrier" class="ty-control-group__title cm-required">{__('jp_sonypayment_carrier_crkind')}</label>
            <select id="jp_sonyc_rb_carrier" name="payment_info[carrier_rb]">
                {if $rb_processor_params.carrier_rb['01'] == 'true'}
                    <option value="01">{__("jp_sonypayment_carrier_docomo")}</option>
                {/if}
                {if $rb_processor_params.carrier_rb['02'] == 'true'}
                    <option value="02">{__("jp_sonypayment_carrier_au")}</option>
                {/if}
                {if $rb_processor_params.carrier_rb['03'] == 'true'}
                    <option value="03">{__("jp_sonypayment_carrier_softbank")}</option>
                {/if}
            </select>
        </div>
    </div>
</div>
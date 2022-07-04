{* $Id: krnkab.tpl by tommy from cs-cart.jp 2016 *}
{* Modified by takahashi from cs-cart.jp 2018 *}
{* 同梱対応 *}

{* Modified by takahashi from cs-cart.jp 2021 *}
{* スマホタイプ対応 *}

{if $cart.ship_to_another}
    <input type="hidden" name="payment_info[sendDiv]" value=1 />
{else}
    {if $cart.payment_method_data.processor_params.operate == "INCLUDE"}
        <input type="hidden" name="payment_info[sendDiv]" value=2 />
    {else}
        <input type="hidden" name="payment_info[sendDiv]" value=0 />
    {/if}
{/if}

{* Modified by takahashi from cs-cart.jp 2018 *}
{* スマホタイプ BOF *}
{if $payment_method.processor_params.krnkab_payment_type == 'SM' || $payment_method.processor_params.krnkab_payment_type == 'SS'}
<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-control-group">
            <label for="sms_invoice" class="ty-control-group__title">{__("jp_kuroneko_webcollect_ab_sms_invoice")}</label>
            {if $payment_method.processor_params.krnkab_payment_type == 'SS'}
            <p>
                <input type="radio" name="payment_info[sms_invoice]" id="register_yes" value="true" class="radio" onchange="fn_display_phone_textbox(true);"/>{__("yes")}
                &nbsp;&nbsp;
                <input type="radio" name="payment_info[sms_invoice]" id="register_no" value="false" checked="checked" class="radio" onchange="fn_display_phone_textbox(false);" />{__("no")}
            </p>
            {else}
                <input type="hidden" name="payment_info[sms_invoice]" id="register_yes" value="true"/>
            {/if}
        </div>
        <div class="ty-control-group {if $payment_method.processor_params.krnkab_payment_type == 'SS'}hidden{/if}" id="display_smartphone">
            <label for="smartphone" class="ty-control-group__title cm-required">{__("jp_kuroneko_webcollect_ab_smartphone")}</label>
            <input type="tel" name="payment_info[smartphone]" id="smartphone" {if $payment_method.processor_params.krnkab_payment_type == 'SS'}disabled{/if}/>
        </div>
    </div>
</div>

<script>
    function fn_display_phone_textbox(value)
    {
        if (value) {
            (function ($) {
                $(document).ready(function() {
                    $('#display_smartphone').switchAvailability(false);
                    $('#smartphone').prop("disabled", false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_smartphone').switchAvailability(true);
                    $('#smartphone').prop("disabled", true);
                });
            })(jQuery);
        }
    }
</script>
{/if}
{* スマホタイプ EOF *}

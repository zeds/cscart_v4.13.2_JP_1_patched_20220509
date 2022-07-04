{* $Id: smbc_cc.tpl by tommy from cs-cart.jp 2014 *}

{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

<div class="clearfix">
    <div class="credit-card">
        <div class="control-group">
            <label for="credit_card_number_{$id_suffix}" class="cm-required">{__("card_number")}</label>
            <input size="35" type="text" id="credit_card_number_{$id_suffix}" name="payment_info[card_number]" value="" class="cm-cc-number input-text cm-autocomplete-off" />
            <ul class="cc-icons-wrap cc-icons cm-cc-icons">
                <li class="cc-icon cc-default cm-cc-default"><span class="default">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
            </ul>
        </div>

        <div class="control-group">
            <label for="credit_card_month_{$id_suffix}" class="cm-required">{__("valid_thru")}</label>
            <label for="credit_card_year_{$id_suffix}" class="cm-required hidden"></label>
            <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="cm-cc-exp-month input-text-short cm-autocomplete-off" />&nbsp;&nbsp;/&nbsp;&nbsp;<input type="text" id="credit_card_year_{$id_suffix}"  name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="cm-cc-exp-year input-text-short cm-autocomplete-off" />&nbsp;
        </div>

        {if $payment_method.processor_params.use_cvv == 'true'}
        <div class="control-group">
            <label for="credit_card_cvv2_{$id_suffix}" class="cm-required cm-integer">{__("jp_smbc_security_code")}</label>
            <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 input-text-short cm-autocomplete-off" />

            <div class="cvv2">{__("jp_smbc_what_is_security_code")}
                <div class="cvv2-note">

                    <div class="card-info clearfix">
                        <div class="cards-images">
                            <img src="{$images_dir}/visa_cvv.png" alt="" />
                        </div>
                        <div class="cards-description">
                            <h5>{__("visa_card_discover")}</h5>
                            <p>{__("credit_card_info")}</p>
                        </div>
                    </div>
                    <div class="card-info ax clearfix">
                        <div class="cards-images">
                            <img src="{$images_dir}/express_cvv.png" alt="" />
                        </div>
                        <div class="cards-description">
                            <h5>{__("american_express")}</h5>
                            <p>{__("american_express_info")}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        {/if}

        <div class="control-group">
            <label for="jp_cc_method" class="cm-required">{__("jp_cc_method")}</label>
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_smbc_cc_payment_type(this.value);">

                {if $payment_method.processor_params.shiharai_kbn.1 == 'true'}
                    <option value="1">{__("jp_cc_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.shiharai_kbn.61 == 'true'}
                    <option value="61">{__("jp_cc_installment")}</option>
                {/if}
                {if $payment_method.processor_params.shiharai_kbn.91 == 'true'}
                    <option value="91">{__("jp_cc_bonus_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.shiharai_kbn.80 == 'true'}
                    <option value="80">{__("jp_cc_revo")}</option>
                {/if}
            </select>
        </div>

        <div class="control-group hidden" id="display_smbc_cc_splict_count">
            <label for="jp_cc_installment_times" class="cm-required">{__("jp_cc_installment_times")}</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.paycount item=paycount key=paycount_key name="paycounts"}
                    {if $payment_method.processor_params.paycount.$paycount_key == 'true'}
                        <option value="{$paycount_key}">{$paycount_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>

        {if $payment_method.processor_params.register_card_info == 'true' && $auth.user_id && $auth.user_id > 0}
        <div class="control-group">
            <label for="register_card_info" class="cm-required">{__("jp_smbc_cc_register_card_info_use")}</label>
            <p>
            <input type="radio" name="payment_info[register_card_info]" id="register_yes" value="true" checked="checked" class="radio" />{__("yes")}
            &nbsp;&nbsp;
            <input type="radio" name="payment_info[register_card_info]" id="register_no" value="false" class="radio" />{__("no")}
            </p>
        </div>
        {/if}

    </div>
</div>

<script type="text/javascript">
    (function(_, $) {
        $.ceEvent('on', 'ce.commoninit', function() {

            var icons = $('.cm-cc-icons li');

            if($(".cm-cc-number").data('rawMaskFn') == undefined) {

                $(".cm-cc-number").inputmask("9999 9999 9999 9[999]", {
                    placeholder: ' '
                });

                $(".cm-cc-cvv2").inputmask("999[9]", {
                    placeholder: ''
                });

                $(".cm-cc-exp-month, .cm-cc-exp-year").inputmask("99", {
                    placeholder: ''
                });

                $('.cm-cc-number').validateCreditCard(function(result) {
                    icons.removeClass('active');
                    if (result.card_type) {
                        icons.filter('.cm-cc-' + result.card_type.name).addClass('active');

                        if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                            $('.cm-cc-cvv2').parent('label').removeClass('cm-required');
                        } else {
                            $('.cm-cc-cvv2').parent('label').addClass('cm-required');
                        }
                    }
                });
                fn_check_smbc_cc_payment_type($('#jp_cc_method').val());
            }
        });
    })(Tygh, Tygh.$);

    function fn_check_smbc_cc_payment_type(payment_type)
    {
        if (payment_type == '61') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_smbc_cc_splict_count').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_smbc_cc_splict_count').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>

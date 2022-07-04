{* $Id: digital_check_cc_2step.tpl by tommy from cs-cart.jp 2015 *}

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
                <label for="credit_card_cvv2_{$id_suffix}" class="cm-required cm-integer">{__("cvv2")}</label>
                <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 input-text-short cm-autocomplete-off" />

                <div class="cvv2">{__("jp_digital_check_what_is_security_code")}
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
            <label class="cm-required">{__('jp_cc_method')}:</label>
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_digital_check_cc_payment_type(this.value);">
                {if $payment_method.processor_params.paymode.10 == 'true'}
                    <option value="10">{__('jp_cc_onetime')}</option>
                {/if}
                {if $payment_method.processor_params.paymode.61 == 'true'}
                    <option value="61">{__('jp_cc_installment')}</option>
                {/if}
                {if $payment_method.processor_params.paymode.21 == 'true'}
                    <option value="21">{__('jp_digital_check_cc_bonus')}</option>
                {/if}
                {if $payment_method.processor_params.paymode.31 == 'true'}
                    <option value="31">{__('jp_digital_check_cc_bonus_combination')}</option>
                {/if}
                {if $payment_method.processor_params.paymode.80 == 'true'}
                    <option value="80">{__('jp_cc_revo')}</option>
                {/if}
            </select>
        </div>

        <div class="control-group hidden" id="display_digital_check_cc_incount">
            <label for="jp_cc_installment_times" class="cm-required">{__('jp_cc_installment_times')}:</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.incount item=incount key=incount_key name="incounts"}
                    {if $payment_method.processor_params.incount.$incount_key == 'true'}
                        <option value="{$incount_key}">{$incount_key}{__('jp_paytimes_unit')}</option>
                    {/if}
                {/foreach}
            </select>
        </div>

        {if $payment_method.processor_params.use_uid == 'true' && $auth.user_id && $auth.user_id > 0}
        <div class="control-group">
            <label for="use_uid" class="cm-required">{__('jp_digital_check_register_card_info')}:</label>
            <p>
            <input type="radio" name="payment_info[use_uid]" id="register_yes" value="true" checked="checked" class="radio" />{__('yes')}
            &nbsp;&nbsp;
            <input type="radio" name="payment_info[use_uid]" id="register_no" value="false" class="radio" />{__('no')}
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
                fn_check_digital_check_cc_payment_type($('#digital_check_cc_payment_type').val());
            }
        });
    })(Tygh, Tygh.$);

    function fn_check_digital_check_cc_payment_type(payment_type)
    {
        if (payment_type == '61') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_digital_check_cc_incount').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_digital_check_cc_incount').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>

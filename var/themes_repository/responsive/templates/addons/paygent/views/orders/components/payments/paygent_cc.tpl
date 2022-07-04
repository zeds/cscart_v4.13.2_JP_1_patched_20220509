{* $Id: paygent_cc.tpl by tommy from cs-cart.jp 2016 *}

{script src="js/lib/inputmask/jquery.inputmask.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_number_{$id_suffix}" class="ty-control-group__title cm-required">{__("card_number")}</label>
            <input size="35" type="text" id="credit_card_number_{$id_suffix}" name="payment_info[card_number]" value="" class="cm-cc-number ty-credit-card__input cm-autocomplete-off" />
            <ul class="ty-cc-icons cm-cc-icons">
                <li class="ty-cc-icons__item cc-default cm-cc-default"><span class="ty-cc-icons__icon default">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-visa"><span class="ty-cc-icons__icon visa">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-visa_electron"><span class="ty-cc-icons__icon visa-electron">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-mastercard"><span class="ty-cc-icons__icon mastercard">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-maestro"><span class="ty-cc-icons__icon maestro">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-amex"><span class="ty-cc-icons__icon american-express">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-discover"><span class="ty-cc-icons__icon discover">&nbsp;</span></li>
            </ul>
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_month_{$id_suffix}" class="ty-control-group__title cm-required">{__("valid_thru")}</label>
            <label for="credit_card_year_{$id_suffix}" class="cm-required hidden"></label>
            <input type="text" id="credit_card_month_{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="cm-cc-exp-month ty-credit-card__input-short " />&nbsp;&nbsp;/&nbsp;&nbsp;<input type="text" id="credit_card_year_{$id_suffix}"  name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="cm-cc-exp-year ty-credit-card__input-short" />&nbsp;
        </div>

        {if $payment_method.processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_paygent_security_code")}</label>
                <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input" />

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__("jp_paygent_what_is_security_code")}</span>
                    <div class="ty-cvv2-about__note">

                        <div class="ty-cvv2-about__info mb30 clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/visa_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__("visa_card_discover")}</h5>
                                <p>{__("credit_card_info")}</p>
                            </div>
                        </div>
                        <div class="ty-cvv2-about__info clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/express_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__("american_express")}</h5>
                                <p>{__("american_express_info")}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="jp_cc_method" class="ty-control-group__title cm-required">{__('jp_cc_method')}:</label>
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_pygnt_cc_payment_type(this.value);">
                {if $payment_method.processor_params.pygnt_method.10 == 'true'}
                    <option value="10">{__("jp_cc_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.pygnt_method.61 == 'true'}
                    <option value="61">{__("jp_cc_installment")}</option>
                {/if}
                {if $payment_method.processor_params.pygnt_method.23 == 'true'}
                    <option value="23">{__("jp_paygent_cc_bonus")}</option>
                {/if}
                {if $payment_method.processor_params.pygnt_method.80 == 'true'}
                    <option value="80">{__("jp_cc_revo")}</option>
                {/if}
            </select>
        </div>

        <div class="ty-credit-card__control-group ty-control-group hidden" id="display_pygnt_cc_split_count">
            <label for="jp_cc_installment_times" class="ty-control-group__title cm-required">{__("jp_cc_installment_times")}:</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.split_count item=split_count key=split_count_key name="split_counts"}
                    {if $payment_method.processor_params.split_count.$split_count_key == 'true'}
                        <option value="{$split_count_key}">{$split_count_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>

        {if $payment_method.processor_params.register_card_info == 'true' && $auth.user_id && $auth.user_id > 0}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="register_card_info" class="ty-control-group__title cm-required">{__("jp_paygent_register_card_info_use")}</label>
                <p>
                    <input type="radio" name="payment_info[register_card_info]" id="register_yes" value="true" checked="checked" class="radio" />{__("yes")}
                    &nbsp;&nbsp;
                    <input type="radio" name="payment_info[register_card_info]" id="register_no" value="false" class="radio" />{__("no")}
                </p>
            </div>
        {/if}
    </div>
</div>

<script>
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

            }
            fn_check_pygnt_cc_payment_type($('#jp_cc_method').val());
        });
    })(Tygh, Tygh.$);

    function fn_check_pygnt_cc_payment_type(payment_type)
    {
        if (payment_type == '61') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_pygnt_cc_split_count').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_pygnt_cc_split_count').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>


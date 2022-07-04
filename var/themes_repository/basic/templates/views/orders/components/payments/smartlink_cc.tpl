{* $Id: smartlink_cc.tpl by tommy from cs-cart.jp 2014 *}

{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

<div class="clearfix">
    <div class="credit-card">
        <div class="control-group">
            <label for="cc_number{$id_suffix}" class="cm-required">{__("card_number")}</label>
            <input id="cc_number{$id_suffix}" size="35" type="text" name="payment_info[card_number]" value="" class="input-text cm-autocomplete-off" />
            <ul class="cc-icons-wrap cc-icons" id="cc_icons{$id_suffix}">
                <li class="cc-icon cm-cc-default"><span class="default">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
            </ul>
        </div>

        <div class="control-group">
            <label for="cc_name{$id_suffix}" class="cm-required">{__("valid_thru")}</label>
            <input type="text" id="cc_exp_month{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="input-text-short" />&nbsp;&nbsp;/&nbsp;&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="input-text-short" />&nbsp;
        </div>

        {if $payment_method.processor_params.use_cvv == 'true'}
        <div class="control-group">
            <label for="cc_cvv2{$id_suffix}" class="cm-required cm-integer cm-autocomplete-off">{__("jp_sln_security_code")}</label>
            <input id="cc_cvv2{$id_suffix}" type="text" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="input-text-short" />

            <div class="cvv2">{__("jp_sln_cc_what_is_security_code")}
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
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_sln_cc_payment_type(this.value);">

                {if $payment_method.processor_params.paymode.10 == 'true'}
                    <option value="10">{__("jp_cc_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.paymode.61 == 'true'}
                    <option value="61">{__("jp_cc_installment")}</option>
                {/if}
                {if $payment_method.processor_params.paymode.80 == 'true'}
                    <option value="80">{__("jp_cc_bonus_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.paymode.88 == 'true'}
                    <option value="88">{__("jp_cc_revo")}</option>
                {/if}
            </select>
        </div>

        <div class="control-group hidden" id="display_sln_cc_split_count">
            <label for="jp_cc_installment_times" class="cm-required">{__("jp_cc_installment_times")}</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.incount item=incount key=incount_key name="incounts"}
                    {if $payment_method.processor_params.incount.$incount_key == 'true'}
                        <option value="{$incount_key}">{$incount_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>

        {if $payment_method.processor_params.register_card_info == 'true' && $auth.user_id && $auth.user_id > 0}
        <div class="control-group">
            <label for="register_card_info" class="cm-required">{__("jp_sln_cc_register_card_info_use")}</label>
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
        $(document).ready(function() {
            var icons = $('#cc_icons{$id_suffix} li');

            $("#cc_number{$id_suffix}").mask("9999 9999 9999 9?999", {
                placeholder: ' '
            });

            $("#cc_cvv2{$id_suffix}").mask("999?9", {
                placeholder: ''
            });

            $("#cc_exp_month{$id_suffix},#cc_exp_year{$id_suffix}").mask("99", {
                placeholder: ''
            });

            $('#cc_number{$id_suffix}').validateCreditCard(function(result) {
                icons.removeClass('active');
                if (result.card_type) {
                    icons.filter('.cm-cc-' + result.card_type.name).addClass('active');

                    if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                        $('label[for=cc_cvv2{$id_suffix}]').removeClass('cm-required');
                    } else {
                        $('label[for=cc_cvv2{$id_suffix}]').addClass('cm-required');
                    }
                }
            });
        });
    })(Tygh, Tygh.$);

    function fn_check_sln_cc_payment_type(payment_type)
    {
        if (payment_type == '61') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_sln_cc_split_count').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_sln_cc_split_count').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>

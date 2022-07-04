{* $Id: paygent_cc.tpl by tommy from cs-cart.jp 2016 *}

{if $paygent_is_changable == 'Y'}

{else}
    {script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

    <div class="clearfix">
        <div class="credit-card">
            <div class="control-group">
                <label for="cc_number{$id_suffix}" class="control-label cm-cc-number cm-autocomplete-off cm-required">{__("card_number")}</label>
                <div class="controls">
                    <input id="cc_number{$id_suffix}" size="35" type="text" name="payment_info[card_number]" value="{$card_item.card_number}" class="input-big" />
                </div>
                <ul class="cc-icons-wrap cc-icons unstyled" id="cc_icons{$id_suffix}">
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
                <label for="cc_exp_month{$id_suffix}" class="control-label cm-cc-date cm-required">{__("valid_thru")}</label>
                <div class="controls clear">
                    <div class="cm-field-container nowrap">
                        <input type="text" id="cc_exp_month{$id_suffix}" name="payment_info[expiry_month]" value="{$card_item.expiry_month}" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" name="payment_info[expiry_year]" value="{$card_item.expiry_year}" size="2" maxlength="2" class="input-small" />
                    </div>
                </div>
            </div>

            {if $payment_method.processor_params.use_cvv == 'true'}
                <div class="control-group cvv-field">
                    <label for="cc_cvv2{$id_suffix}" class="control-label cm-integer cm-autocomplete-off">{__("jp_paygent_security_code")}</label>
                    <div class="controls">
                        <input id="cc_cvv2{$id_suffix}" type="text" name="payment_info[cvv2]" value="" size="4" maxlength="4"/>
                        <div class="cvv2">{__("jp_paygent_what_is_security_code")}
                            <div class="popover fade bottom in">
                                <div class="arrow"></div>
                                <h3 class="popover-title">{__("jp_paygent_what_is_security_code")}</h3>
                                <div class="popover-content">
                                    <div class="cvv2-note">
                                        <div class="card-info clearfix">
                                            <div class="cards-images">
                                                <img src="{$images_dir}/visa_cvv.png" border="0" alt="" />
                                            </div>
                                            <div class="cards-description">
                                                <strong>{__("visa_card_discover")}</strong>
                                                <p>{__("credit_card_info")}</p>
                                            </div>
                                        </div>
                                        <div class="card-info ax clearfix">
                                            <div class="cards-images">
                                                <img src="{$images_dir}/express_cvv.png" border="0" alt="" />
                                            </div>
                                            <div class="cards-description">
                                                <strong>{__("american_express")}</strong>
                                                <p>{__("american_express_info")}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div
                    </div>
                </div>
            {/if}

            <div class="control-group">
                <label for="jp_cc_method" class="control-label">{__("jp_cc_method")}</label>
                <div class="controls">
                    <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_pygnt_cc_payment_type(this.value);">
                        {if $payment_method.processor_params.pygnt_method.10 == 'true'}
                            <option value="10">{__("jp_cc_onetime")}</option>
                        {/if}
                        {if $payment_method.processor_params.pygnt_method.61 == 'true'}
                            <option value="61">{__("jp_cc_installment")}</option>
                        {/if}
                        {if $payment_method.processor_params.pygnt_method.23 == 'true'}
                            <option value="23">{__("jp_paygent_multipayment_cc_bonus")}</option>
                        {/if}
                        {if $payment_method.processor_params.pygnt_method.80 == 'true'}
                            <option value="80">{__("jp_cc_revo")}</option>
                        {/if}
                    </select>
                </div>
            </div>

            <div class="control-group hidden" id="display_pygnt_cc_splict_count">
                <label for="jp_cc_installment_times" class="control-label">{__("jp_cc_installment_times")}</label>
                <div class="controls">
                    <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                        {foreach from=$payment_method.processor_params.paytimes item=paytimes key=paytimes_key name="paytimess"}
                            {if $payment_method.processor_params.paytimes.$paytimes_key == 'true'}
                                <option value="{$paytimes_key}">{$paytimes_key}{__("jp_paytimes_unit")}</option>
                            {/if}
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function(_, $) {
            $(document).ready(function() {

                var icons = $('#cc_icons{$id_suffix} li');
                var ccNumberInput = $("#cc_number{$id_suffix}");

                ccNumberInput.validateCreditCard(function(result) {
                    if (result.card_type) {
                        icons.filter('.cm-cc-' + result.card_type.name).addClass('active');
                    }
                });
                fn_check_pygnt_cc_payment_type($('#jp_cc_method').val());
            });
        })(Tygh, Tygh.$);

        function fn_check_pygnt_cc_payment_type(payment_type)
        {
            if (payment_type == '2') {
                (function ($) {
                    $(document).ready(function() {
                        $('#display_pygnt_cc_splict_count').switchAvailability(false);
                    });
                })(jQuery);
            } else {
                (function ($) {
                    $(document).ready(function() {
                        $('#display_pygnt_cc_splict_count').switchAvailability(true);
                    });
                })(jQuery);
            }
        }
    </script>
{/if}

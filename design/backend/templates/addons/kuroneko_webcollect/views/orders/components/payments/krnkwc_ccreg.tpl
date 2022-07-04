{* $Id: krnkwc_ccreg.tpl by tommy from cs-cart.jp 2016 *}

{if $krnkwc_is_changable == 'Y'}

{else}
    <div class="clearfix">

        <div class="credit-card">

            <div class="control-group">
                <label for="registered_cc_number" class="control-label">{__("card_number")}</label>
                {$krnkwc_registered_card.maskingCardNo}
            </div>

            <div class="control-group cvv-field">
                <label for="cc_cvv2" class="control-label cm-integer cm-autocomplete-off cm-required">{__("jp_kuroneko_webcollect_security_code")}</label>
                <div class="controls">
                    <input id="cc_cvv2" type="text" name="payment_info[cvv2]" value="" size="4" maxlength="4"/>
                    <div class="cvv2">{__("jp_kuroneko_webcollect_what_is_security_code")}
                        <div class="popover fade bottom in">
                            <div class="arrow"></div>
                            <h3 class="popover-title">{__("jp_kuroneko_webcollect_what_is_security_code")}</h3>
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
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label for="pay_way" class="control-label">{__("jp_cc_method")}</label>
                <div class="controls">
                    <select id="pay_way" name="payment_info[pay_way]" onchange="fn_check_krnkwc_cc_payment_type(this.value);">
                        {if $payment_method.processor_params.pay_way.1 == 'true'}
                            <option value="1">{__("jp_cc_onetime")}</option>
                        {/if}
                        {if $payment_method.processor_params.pay_way.2 == 'true'}
                            <option value="2">{__("jp_cc_installment")}</option>
                        {/if}
                        {if $payment_method.processor_params.pay_way.0 == 'true'}
                            <option value="0">{__("jp_cc_revo")}</option>
                        {/if}
                    </select>
                </div>
            </div>

            <div class="control-group hidden" id="display_krnkwc_cc_split_count">
                <label for="jp_cc_installment_times" class="control-label cm-required">{__("jp_cc_installment_times")}</label>
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
        function fn_check_krnkwc_cc_payment_type(payment_type)
        {
            if (payment_type == '2') {
                (function ($) {
                    $(document).ready(function() {
                        $('#display_krnkwc_cc_split_count').switchAvailability(false);
                    });
                })(jQuery);
            } else {
                (function ($) {
                    $(document).ready(function() {
                        $('#display_krnkwc_cc_split_count').switchAvailability(true);
                    });
                })(jQuery);
            }
        }
    </script>
{/if}
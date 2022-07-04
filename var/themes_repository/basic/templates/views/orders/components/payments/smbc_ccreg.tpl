{* $Id: smbc_ccreg.tpl by tommy from cs-cart.jp 2014 *}

<div class="clearfix">
    <div class="credit-card">
        <div class="control-group">
            <label for="registered_cc_number{$id_suffix}">{__("card_number")}</label>
            {$registered_card.card_number}
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
    </div>
</div>

<script type="text/javascript">
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

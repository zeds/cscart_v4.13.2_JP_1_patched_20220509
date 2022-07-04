{* $Id: smbc_ccreg.tpl by tommy from cs-cart.jp 2014 *}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="registered_cc_number_{$id_suffix}" class="ty-control-group__title">{__("card_number")}</label>
            {$registered_card.card_number}
        </div>

        {if $payment_method.processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_smbc_security_code")}</label>
                <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cc-numeric cc-henkan" />

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__("jp_smbc_what_is_security_code")}</span>
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

        {if $payment_method.processor_params.shiharai_kbn.61 == 'true'}
        <div class="ty-credit-card__control-group ty-control-group hidden" id="display_smbc_cc_splict_count">
            <label for="jp_cc_installment_times" class="ty-control-group__title cm-required">{__('jp_cc_installment_times')}:</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.paycount item=paycount key=paycount_key name="paycounts"}
                    {if $payment_method.processor_params.paycount.$paycount_key == 'true'}
                        <option value="{$paycount_key}">{$paycount_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
        {/if}
    </div>
</div>

<script>
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

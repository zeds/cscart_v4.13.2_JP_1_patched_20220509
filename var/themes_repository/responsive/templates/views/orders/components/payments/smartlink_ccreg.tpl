{* $Id: smartlink_ccreg.tpl by tommy from cs-cart.jp 2014 *}
{* Modified by takahashi from cs-cart.jp 2017 *}
{* 一括払いのみ指定した場合に注文を確定できないバグを修正 *}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="registered_cc_number_{$id_suffix}" class="ty-control-group__title">{__("card_number")}</label>
            {$sln_registered_card.card_number}
        </div>

        {if $payment_method.processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_sln_security_code")}</label>
                <input type="password" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cc-numeric cc-henkan" />

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__("jp_sln_cc_what_is_security_code")}</span>
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
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_sln_ccreg_payment_type(this.value);">
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

        {if $payment_method.processor_params.paymode.61 == 'true'}
        <div class="ty-credit-card__control-group ty-control-group hidden" id="display_sln_cc_split_count">
            <label for="jp_cc_installment_times" class="ty-control-group__title cm-required">{__('jp_cc_installment_times')}:</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.incount item=incount key=incount_key name="incounts"}
                    {if $payment_method.processor_params.incount.$incount_key == 'true'}
                        <option value="{$incount_key}">{$incount_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
        {/if}
    </div>
</div>

<script>
    function fn_check_sln_ccreg_payment_type(payment_type)
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

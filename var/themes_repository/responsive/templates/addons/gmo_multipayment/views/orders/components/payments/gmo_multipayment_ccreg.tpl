{* $Id: gmo_multipayment_ccreg.tpl by tommy from cs-cart.jp 2015 *}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="ty-credit-card">
        {if $payment_method.processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_gmo_multipayment_security_code")}</label>
                <input type="text" id="credit_card_cvv2_{$id_suffix}" name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cc-numeric cc-henkan" />

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__("jp_gmo_multipayment_what_is_security_code")}</span>
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
            <select id="jp_cc_method" name="payment_info[jp_cc_method]" onchange="fn_check_gmomp_cc_payment_type(this.value);">
                {if $payment_method.processor_params.gmomp_method.1 == 'true'}
                    <option value="1">{__("jp_cc_onetime")}</option>
                {/if}
                {if $payment_method.processor_params.gmomp_method.2 == 'true'}
                    <option value="2">{__("jp_cc_installment")}</option>
                {/if}
                {if $payment_method.processor_params.gmomp_method.3 == 'true'}
                    <option value="3">{__("jp_gmo_multipayment_cc_bonus")}</option>
                {/if}
                {if $payment_method.processor_params.gmomp_method.5 == 'true'}
                    <option value="5">{__("jp_cc_revo")}</option>
                {/if}
            </select>
        </div>

        {if $payment_method.processor_params.gmomp_method.2 == 'true'}
        <div class="ty-credit-card__control-group ty-control-group hidden" id="display_gmomp_cc_splict_count">
            <label for="jp_cc_installment_times" class="ty-control-group__title cm-required">{__('jp_cc_installment_times')}:</label>
            <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                {foreach from=$payment_method.processor_params.paytimes item=paytimes key=paytimes_key name="paytimess"}
                    {if $payment_method.processor_params.paytimes.$paytimes_key == 'true'}
                        <option value="{$paytimes_key}">{$paytimes_key}{__("jp_paytimes_unit")}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
        {/if}
    </div>
</div>

<script>
    (function(_, $) {
        $.ceEvent('on', 'ce.commoninit', function() {
            fn_check_gmomp_cc_payment_type($('#jp_cc_method').val());
        });
    })(Tygh, Tygh.$);

    function fn_check_gmomp_cc_payment_type(payment_type)
    {
        if (payment_type == '2') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_gmomp_cc_splict_count').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_gmomp_cc_splict_count').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>

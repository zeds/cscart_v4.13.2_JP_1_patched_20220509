{script src='js/lib/creditcardvalidator_jp/jquery.numeric.min.js'}
{script src='js/lib/creditcardvalidator_jp/jquery.creditCardValidator.js'}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix cc_form_jp">
    <div class="ty-credit-card cm-cc_form_{$id_suffix}">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="ap_sbps_dealings_type" class="ty-control-group__title cm-required">{__('jp_cc_method')}:</label>
            <select id="ap_sbps_dealings_type" name="payment_info[dealings_type]">
                {if $payment_method.processor_params.dealings_type.10 == 'true'}
                    <option value="10">{__('jp_cc_onetime')}</option>
                {/if}
                {if $payment_method.processor_params.dealings_type.21 == 'true'}
                    <option value="21">{__('sbps_cc_bonus')}</option>
                {/if}
                {if $payment_method.processor_params.dealings_type.61 == 'true'}
                    <option value="61">{__('jp_cc_installment')}</option>
                {/if}
                {if $payment_method.processor_params.dealings_type.80 == 'true'}
                    <option value="80">{__('jp_cc_revo')}</option>
                {/if}
            </select>
        </div>

        {if $payment_method.processor_params.dealings_type.61 == 'true'}
            <div class="ty-credit-card__control-group ty-control-group hidden" id="ap_sbps_divide_times_container">
                <label for="ap_sbps_divide_times" class="ty-control-group__title cm-required">{__('jp_cc_installment_times')}:</label>
                <select id="ap_sbps_divide_times" name="payment_info[divide_times]">
                    {foreach from=$payment_method.processor_params.divide_times item=value key=divide_times}
                        {if $payment_method.processor_params.divide_times.$divide_times == 'true'}
                            <option value="{$divide_times}">{$divide_times}{__('jp_paytimes_unit')}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        {/if}
    </div>
</div>

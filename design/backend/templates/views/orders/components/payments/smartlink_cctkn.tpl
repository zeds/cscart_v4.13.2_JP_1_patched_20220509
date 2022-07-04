{* $Id: smartlink_cctkn.tpl by takahashi from cs-cart.jp 2017 *}
{* Modified by takahashi from cs-cart.jp 2018 *}
{* トークン決済に対応 *}

{if $payment_method.processor_params.mode == "test"}
    <script src="https://www.test.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$payment_method.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
{elseif $payment_method.processor_params.mode == "live"}
    <script src="https://www.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$payment_method.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
{/if}
{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

<div class="clearfix">
    <div class="credit-card">
        <div class="control-group">
            <label for="cc_number{$id_suffix}" class="control-label">{__("card_number")}</label>
            <div class="controls">
                <input id="cc_number{$id_suffix}" size="35" type="text" data-name="payment_info[card_number]" value="" class="input-big cm-autocomplete-off" />
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
            <label for="cc_exp_month{$id_suffix}" class="control-label">{__("valid_thru")}</label>
            <div class="controls clear">
                <div class="cm-field-container nowrap">
                    <input type="text" id="cc_exp_month{$id_suffix}" data-name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" data-name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="input-small" />
                </div>
            </div>
        </div>

        <input type='hidden' value='' id='token' name=payment_info[token] />
        <input type="hidden" name="result_ids" value="{$result_ids}" />
        <input type="hidden" id = "dispatch" name="dispatch" value="order_management.place_order" />

        {if $payment_method.processor_params.use_cvv == 'true'}
        <div class="control-group cvv-field">
            <label for="cc_cvv2{$id_suffix}" class="control-label cm-integer cm-autocomplete-off">{__("jp_sln_security_code")}</label>
            <div class="controls">
                <input id="cc_cvv2{$id_suffix}" type="text" data-name="payment_info[cvv2]" value="" size="4" maxlength="4"/>
                <div class="cvv2">{__("jp_sln_cc_what_is_security_code")}
                    <div class="popover fade bottom in">
                        <div class="arrow"></div>
                        <h3 class="popover-title">{__("jp_sln_cc_what_is_security_code")}</h3>
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
        </div>

        <div class="control-group hidden" id="display_sln_cc_split_count">
            <label for="jp_cc_installment_times" class="control-label">{__("jp_cc_installment_times")}</label>
            <div class="controls">
                <select id="jp_cc_installment_times" name="payment_info[jp_cc_installment_times]">
                    {foreach from=$payment_method.processor_params.incount item=incount key=incount_key name="incounts"}
                        {if $payment_method.processor_params.incount.$incount_key == 'true'}
                            <option value="{$incount_key}">{$incount_key}{__("jp_paytimes_unit")}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        </div>

    </div>
</div>

<script>
    var btnname = "";
    var checkoutForm = $('#jp_payments_form_id');

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

        $('input.btn').on('click', function(){
            btnname = this.name;

            if(btnname == "dispatch[order_management.place_order]") {
                checkoutForm.off('submit', submitHandler);
                checkoutForm.on('submit', submitHandler);
                document.getElementById('dispatch').value = "order_management.place_order";
            }
            else if(btnname == "dispatch[order_management.place_order.save]") {
                checkoutForm.off('submit', submitHandler);
                document.getElementById('dispatch').value = "order_management.place_order.save";
            }
        });
    })(Tygh, Tygh.$);

    // チェックアウトフォームのsubmitイベントハンドラ
    function submitHandler(event) {
        event.preventDefault();

        var cardNoVal = document.getElementById('cc_number{$id_suffix}').value.replace(/\s+/g, "");
        var cardYearVaal = document.getElementById('cc_exp_year{$id_suffix}').value;
        var cardMonthVal = document.getElementById('cc_exp_month{$id_suffix}').value;

        var scCdObj = document.getElementById('cc_cvv2{$id_suffix}');
        var scCdVal = '';

        // セキュリティコードが表示されている場合
        if(scCdObj) {
            scCdVal = scCdObj.value;
        }
        
        SpsvApi.spsvCreateToken(cardNoVal, cardYearVaal, cardMonthVal, scCdVal, '', '', '', '', '');
    }

    function setToken(token, card) {
        document.getElementById('cc_number{$id_suffix}').value = '';
        document.getElementById('cc_exp_year{$id_suffix}').value = '';
        document.getElementById('cc_exp_month{$id_suffix}').value = '';

        var scCdObj = document.getElementById('cc_cvv2{$id_suffix}');
        // セキュリティコードが表示されている場合
        if(scCdObj) {
            scCdObj.value = '';
        }

        document.getElementById('token').value = token;

        checkoutForm.get(0).submit();
    }

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

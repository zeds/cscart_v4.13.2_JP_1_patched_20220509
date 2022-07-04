{* $Id: paygent_ccregtkn.tpl by tommy from cs-cart.jp 2016 *}

{if $paygent_is_changable == 'Y'}

{else}
    {if $payment_method.processor_params.mode == "test"}
        {script src="https://sandbox.paygent.co.jp/js/PaygentToken.js"}
    {elseif $payment_method.processor_params.mode == "live"}
        {script src="https://token.paygent.co.jp/js/PaygentToken.js"}
    {/if}
    {script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

    <div class="clearfix">
        <div class="credit-card">
            {if $payment_method.processor_params.use_cvv == 'true'}
                <div class="control-group cvv-field">
                    <label for="cc_cvv2{$id_suffix}" class="control-label cm-integer cm-autocomplete-off">{__("jp_paygent_security_code")}</label>
                    <div class="controls">
                        <input id="cc_cvv2{$id_suffix}" type="text" data-name="payment_info[cvv2]" value="" size="4" maxlength="4"/>
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

            <input type='hidden' value='' id='token' name=payment_info[token] />
            <input type='hidden' value='' id='errorCode' name=payment_info[errorCd] />
            <input type='hidden' value='' id='errorMsg' name=payment_info[errorMsg] />
            <input type="hidden" name="result_ids" value="{$result_ids}" />
            <input type="hidden" id = "dispatch" name="dispatch" value="order_management.place_order" />

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
        var btnname = "";
        var checkoutForm = $('#jp_payments_form_id');

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

            // フォームのValidationチェック
            var isFormVal = checkoutForm.ceFormValidator('check');

            var scCdObj = document.getElementById('cc_cvv2{$id_suffix}');
            var scCdVal = '';

            // セキュリティコードが表示されている場合
            if(scCdObj) {
                scCdVal = scCdObj.value;

                // フォームのValidationがOKの場合
                if (isFormVal) {
                    var paygentToken = new PaygentToken(); //PaygentTokenオブジェクトの生成
                    paygentToken.createCvcToken(
                        '{$addons.paygent.merchant_id}',
                        '{$payment_method.processor_params.token_key}',
                        {
                            cvc: scCdVal
                        }, execPurchase //第４引数：コールバック関数(トークン取得後に実⾏)
                    );
                }
            }
        }

        function execPurchase(response) {

            if (response.result == '0000') { //トークン処理結果が正常の場合

                var scCdObj = document.getElementById('cc_cvv2{$id_suffix}');
                // セキュリティコードが表示されている場合
                if(scCdObj) {
                    scCdObj.value = '';
                }

                document.getElementById('token').value = response.tokenizedCardObject.token;

            }
            else  {
                var message_code = response.result;
                var message_text = 'セキュリティコード情報が不正です';

                // エラーコードとメッセージをセット
                document.getElementById('errorCode').value = message_code;
                document.getElementById('errorMsg').value = message_text;
            }

            checkoutForm.get(0).submit();
        }

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

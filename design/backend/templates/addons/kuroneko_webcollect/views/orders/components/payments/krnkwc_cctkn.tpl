{* $Id: krnkwc_cctkn.tpl by takahashi from cs-cart.jp 2017 *}

{if $krnkwc_is_changable == 'Y'}

{else}
    {if $payment_method.processor_params.mode == "test"}
        <script class="webcollect-embedded-token" src="https://ptwebcollect.jp/test_gateway/token/js/embeddedTokenLib.js" ></script>
    {elseif $payment_method.processor_params.mode == "live"}
        <script class="webcollect-embedded-token" src="https://api.kuronekoyamato.co.jp/api/token/js/embeddedTokenLib.js" ></script>
    {/if}
    {script src="js/addons/kuroneko_webcollect/sha256.js"}
    {script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

    <div class="clearfix">
        <div class="credit-card">
            <div class="control-group">
                <label for="cc_number{$id_suffix}" class="control-label cm-cc-number cm-autocomplete-off cm-required">{__("card_number")}</label>
                <div class="controls">
                    <input id="cc_number{$id_suffix}" size="35" type="text" data-name="payment_info[card_number]" value="{$card_item.card_number}" class="input-big" />
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
                        <input type="text" id="cc_exp_month{$id_suffix}" data-name="payment_info[expiry_month]" value="{$card_item.expiry_month}" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" data-name="payment_info[expiry_year]" value="{$card_item.expiry_year}" size="2" maxlength="2" class="input-small" />
                    </div>
                </div>
            </div>

            <div class="control-group">
                <label for="credit_card_name_{$id_suffix}" class="control-label cm-required">{__("cardholder_name")}</label>
                <div class="controls clear">
                    <input size="35" type="text" id="credit_card_name_{$id_suffix}" data-name="payment_info[card_owner]" value="" class="cm-cc-name ty-credit-card__input ty-uppercase" />
                </div>
            </div>            

            <div class="control-group cvv-field">
                <label for="cc_cvv2{$id_suffix}" class="control-label cm-integer cm-autocomplete-off cm-required">{__("jp_kuroneko_webcollect_security_code")}</label>
                <div class="controls">
                    <input id="cc_cvv2{$id_suffix}" type="text" data-name="payment_info[cvv2]" value="" size="4" maxlength="4"/>
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

            <input type='hidden' value='' id='token' name=payment_info[token] />
            <input type='hidden' value='' id='card_code_api' name=payment_info[card_code_api] />
            <input type='hidden' value='' id='authKey' name=payment_info[authKey] />
            <input type='hidden' value='' id='errorCode' name=payment_info[errorCd] />
            <input type='hidden' value='' id='errorMsg' name=payment_info[errorMsg] />
            <input type="hidden" name="result_ids" value="{$result_ids}" />
            <input type="hidden" id = "dispatch" name="dispatch" value="order_management.place_order" />

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
                fn_check_krnkwc_cc_payment_type($('#jp_cc_method').val());
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

            var cardNoObj = document.getElementById('cc_number{$id_suffix}');
            var cardYearObj = document.getElementById('cc_exp_year{$id_suffix}');
            var cardMonthObj = document.getElementById('cc_exp_month{$id_suffix}');
            var cardOwnerObj = document.getElementById('credit_card_name_{$id_suffix}');
            var cvvObj = document.getElementById('cc_cvv2{$id_suffix}');
            var ccregObj = document.getElementById('register_yes');
            var tokenObj = document.getElementById('token');
            var cardCdAPIObj = document.getElementById('card_code_api');
            var authKeyObj = document.getElementById('authKey');
            var errCdObj = document.getElementById('errorCode');
            var errMsgObj = document.getElementById('errorMsg');

            // コールバック関数（「正常」の場合）
            var callbackSuccess = function(response) {
                //カード会社コード(API 用)をセット
                cardCdAPIObj.value = get_card_code_api(cardNoObj.value.replace(/\s+/g, ""));

                // カード情報がリクエストパラメータに含まれないようにする
                cardNoObj.value = "";
                cardYearObj.value = "";
                cardMonthObj.value = "";
                cardOwnerObj.value = "";
                cardOwnerObj.value = "";
                cvvObj.value = "";

                // form に発行したトークンを追加する
                tokenObj.value = response.token;

                // form をサブミットする
                checkoutForm.get(0).submit();
            };

            // コールバック関数（「異常」の場合）
            var callbackFailure = function(response) {
                //エラー情報を取得
                var errorInfo = response.errorInfo;

                var errCdValue = '';
                var errMsgValue = '';

                //エラーの数だけ処理を繰り返す
                for (var i = 0; i<errorInfo.length; i++) {
                    //メッセージを格納
                    if(i > 0){
                        errCdValue += "|";
                        errMsgValue += "|";
                    }

                    errCdValue += errorInfo[i].errorCode;
                    errMsgValue += errorInfo[i].errorMsg;
                }

                errCdObj.value = errCdValue;
                errMsgObj.value = errMsgValue;

                // form をサブミットする
                checkoutForm.get(0).submit();
            };

            // フォームのValidationチェック
            var isFormVal = checkoutForm.ceFormValidator('check');

            // フォームのValidationがOKの場合
            if(isFormVal) {
                // オプション サービス区分
                var pOptServDiv = '00';
                if(ccregObj){
                    if(ccregObj.checked){
                        pOptServDiv = '01';
                    }
                }

                // 認証区分 (authFlg) の設定
                // 3D セキュア
                var threeDsecFlg = {$payment_method.processor_params.tdflag};
                // 3D セキュアなし、セキュリティコード認証あり
                var pAuthFlg = "2";
                // 3D セキュアあり、セキュリティコード認証あり
                if (threeDsecFlg) {
                    pAuthFlg = "3";
                }

                // チェックサムの作成
                var pMemberID = "{$auth.user_id}";
                var pAuthKey = gererate_auth_key();
                authKeyObj.value = pAuthKey;
                var accessKey = "{$addons.kuroneko_webcollect.access_key}";
                var key = pMemberID + pAuthKey + accessKey + pAuthFlg;
                var shaObj = new jsSHA(key, "ASCII");
                var pCheckSum = shaObj.getHash("SHA-256", "HEX");

                // トークン発行API へ渡すパラメータ
                var createTokenInfo = {
                    traderCode: "{$addons.kuroneko_webcollect.trader_code}",
                    authDiv: pAuthFlg,
                    optServDiv: pOptServDiv,
                    memberId: pMemberID,
                    authKey: pAuthKey,
                    checkSum: pCheckSum,
                    cardNo: cardNoObj.value.replace(/\s+/g, ""),
                    cardOwner: cardOwnerObj.value,
                    cardExp: cardMonthObj.value + cardYearObj.value,
                    securityCode: cvvObj.value
                };
                // ｗｅｂコレクトが提供するJavaScript 関数を実行し、トークンを発行する。
                WebcollectTokenLib.createToken(createTokenInfo, callbackSuccess, callbackFailure);
            }
        }

        // カード会社コード (API 用) を取得
        function get_card_code_api(card_no) {
            // カード番号の先頭12桁を取得
            var twelve_digit = card_no.substr(0 , 12);

            // テスト用カード番号の場合
            if(twelve_digit >= '000000000000' && twelve_digit <= '000000000020' ){
                // VISAのコードを返す
                return 9;
            }

            {literal}
            // VISA
            if( card_no.match(/(^4[0-9]{12}(?:[0-9]{3})?$)/) ){
                return 9;
                // Master
            }else if( card_no.match(/(^5[1-5][0-9]{14}$)/) ) {
                return 10;
                // Amex
            }else if( card_no.match(/(^3[47][0-9]{13}$)/) ) {
                return 12;
                // Diners
            }else if( card_no.match(/(^3(?:0[0-5]|[68][0-9])[0-9]{11}$)/) ) {
                return 2;
                // JCB
            }else if( card_no.match(/(^(?:2131|1800|35\d{3})\d{11}$)/) ) {
                return 3;
                // その他
            }else{
                return 0;
            }
            {/literal}
        }

        // 認証キーの生成
        function gererate_auth_key()
        {
            // 生成する文字列の長さ
            var length = 8;

            {literal}
            // 乱数を生成
            var ransu = function(n){
                var str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
                    + '!#$%^&*-+_=(){}@;:?/';
                str = str.split('');
                var s = '';
                for (var i = 0; i < n; i++) {
                    s += str[Math.floor(Math.random() * str.length)];
                }
                return s;
            };

            var code = '';
            var i=0;

            while (code=ransu(length)) {
                if(code.match(/^(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}$/i)){
                    break;
                }
                i++;
            }
            {/literal}

            return code;
        }

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




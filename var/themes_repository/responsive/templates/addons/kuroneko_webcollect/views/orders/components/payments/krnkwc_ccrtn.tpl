{* $Id: krnkwc_ccrtn.tpl by takahashi from cs-cart.jp 2019 *}

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="registered_cc_number_{$id_suffix}" class="ty-control-group__title">{__("card_number")}</label>
            {$krnkwc_registered_card.maskingCardNo}
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_kuroneko_webcollect_security_code")}</label>
            <input type="text" id="credit_card_cvv2_{$id_suffix}" data-name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input" />

            <div class="ty-cvv2-about">
                <span class="ty-cvv2-about__title">{__("jp_kuroneko_webcollect_what_is_security_code")}</span>
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

        <input type='hidden' value='' id='token' name=payment_info[token] />
        <input type='hidden' value='' id='errorCode' name=payment_info[errorCd] />
        <input type='hidden' value='' id='errorMsg' name=payment_info[errorMsg] />

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="pay_way" class="ty-control-group__title cm-required">{__('jp_cc_method')}:</label>
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

        <div class="ty-credit-card__control-group ty-control-group hidden" id="display_krnkwc_cc_splict_count">
            <label for="jp_cc_installment_times" class="ty-control-group__title cm-required">{__('jp_cc_installment_times')}:</label>
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

<script>
    var checkoutForm = $('#jp_payments_form_id_{$tab_id}');

    (function(_, $) {
        $.ceEvent('on', 'ce.commoninit', function() {
            // 新チェックアウト方式に対応
            if(document.getElementById('litecheckout_payments_form')){
                checkoutForm = $('#litecheckout_payments_form');
            }
            checkoutForm.off('submit', submitHandler);
            checkoutForm.on('submit', submitHandler);

            fn_check_krnkwc_cc_payment_type($('#jp_cc_method').val());
        });
    })(Tygh, Tygh.$);

    // 新チェックアウト方式に対応
    var isSubmit = false;

    // チェックアウトフォームのsubmitイベントハンドラ
    function submitHandler(event) {
        // 新チェックアウト方式に対応
        if(!document.getElementById('credit_card_cvv2_{$id_suffix}')) {
            return;
        }

        event.preventDefault();

        var cvvObj = document.getElementById('credit_card_cvv2_{$id_suffix}');
        var tokenObj = document.getElementById('token');
        var errCdObj = document.getElementById('errorCode');
        var errMsgObj = document.getElementById('errorMsg');

        // コールバック関数（「正常」の場合）
        var callbackSuccess = function(response) {

            /* 新チェックアウト方式に対応
            // カード情報がリクエストパラメータに含まれないようにする
            cvvObj.value = "";
             */

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
        // 新チェックアウト方式に対応
        if(isFormVal && !isSubmit) {
            isSubmit = true;

            // オプション サービス区分
            var pOptServDiv = '01';

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
            var pAuthKey = "{$auth.user_id|fn_krnkwc_gererate_auth_key nofilter}";
            {assign var="registered_card_info" value=$auth.user_id|fn_krnkwc_get_registered_card_info}
            var pCardKey = "{$registered_card_info.cardKey}";
            var pLastCreditDate = "{$registered_card_info.lastCreditDate}";
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
                cardKey: pCardKey,
                lastCreditDate: pLastCreditDate,
                checkSum: pCheckSum,
                securityCode: cvvObj.value
            };
            // ｗｅｂコレクトが提供するJavaScript 関数を実行し、トークンを発行する。
            WebcollectTokenLib.createToken(createTokenInfo, callbackSuccess, callbackFailure);
        }
    }

    function fn_check_krnkwc_cc_payment_type(payment_type)
    {
        if (payment_type == '2') {
            (function ($) {
                $(document).ready(function() {
                    $('#display_krnkwc_cc_splict_count').switchAvailability(false);
                });
            })(jQuery);
        } else {
            (function ($) {
                $(document).ready(function() {
                    $('#display_krnkwc_cc_splict_count').switchAvailability(true);
                });
            })(jQuery);
        }
    }
</script>
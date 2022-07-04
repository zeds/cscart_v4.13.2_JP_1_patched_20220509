{* $Id: smartlink_cctkn.tpl by tommy from cs-cart.jp 2017 *}
{* Modified by takahashi from cs-cart.jp 2017 *}
{* トークン決済に対応 *}
{* Modified by takahashi from cs-cart.jp 2019 *}
{* マーケットプレイス版対応 *}

{script src="js/lib/creditcardvalidator_jp/jquery.numeric.min.js"}
{script src="js/lib/creditcardvalidator_jp/jquery.creditCardValidator.js"}
<style>
    .ty-cc-icons {
        bottom: 38px;
    }
</style>
{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

<div class="clearfix">
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_number_{$id_suffix}" class="ty-control-group__title cm-required cm-cc-number cc-number_{$id_suffix} cm-cc-number-check-length-jp cc-numeric">{__("card_number")}</label>
            <input size="35" type="tel" id="credit_card_number_{$id_suffix}" data-name="payment_info[card_number]" value="" class="ty-credit-card__input cm-autocomplete-off cc-numeric cc-henkan" />
            <ul class="ty-cc-icons cm-cc-icons cc-icons_{$id_suffix}">
            <li class="ty-cc-icons__item cc-default cm-cc-default"><span class="ty-cc-icons__icon default">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-visa"><span class="ty-cc-icons__icon visa">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-visa_electron"><span class="ty-cc-icons__icon visa-electron">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-mastercard"><span class="ty-cc-icons__icon mastercard">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-maestro"><span class="ty-cc-icons__icon maestro">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-amex"><span class="ty-cc-icons__icon american-express">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-discover"><span class="ty-cc-icons__icon discover">&nbsp;</span></li>
                <li class="ty-cc-icons__item cm-cc-jcb"><span class="ty-cc-icons__icon jcb">&nbsp;</span></li>
            </ul>
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_month_{$id_suffix}" class="cm-required ty-control-group__title cm-cc-date cc-date_{$id_suffix} cm-cc-exp-month cm-cc-exp-month-jp">{__("valid_thru")}</label>
            <label for="credit_card_year_{$id_suffix}" class="cm-cc-date cm-cc-exp-year cc-year_{$id_suffix} cm-cc-exp-year-jp hidden"></label>
            <input type="tel" id="credit_card_month_{$id_suffix}" data-name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;&nbsp;/&nbsp;&nbsp;
            <input type="tel" id="credit_card_year_{$id_suffix}"  data-name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;
        </div>

        <div id="token_area"></div>

        {if $payment_method.processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_sln_security_code")}</label>
                <input type="password" id="credit_card_cvv2_{$id_suffix}" data-name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cm-autocomplete-off cc-numeric cc-henkan" />

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

        {if $payment_method.processor_params.paymode.61 == 'true'}
        <div class="ty-credit-card__control-group ty-control-group {if $payment_method.processor_params.paymode.10 == 'true'}hidden{/if}" id="display_sln_cc_split_count">
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

        {if $payment_method.processor_params.register_card_info == 'true' && $auth.user_id && $auth.user_id > 0}
            <div class="ty-credit-card__control-group ty-control-group">
            <label for="register_card_info" class="ty-control-group__title cm-required">{__("jp_sln_cc_register_card_info_use")}</label>
            <p>
            <input type="radio" name="payment_info[register_card_info]" id="register_yes" value="true" checked="checked" class="radio" />{__("yes")}
            &nbsp;&nbsp;
            <input type="radio" name="payment_info[register_card_info]" id="register_no" value="false" class="radio" />{__("no")}
            </p>
        </div>
        {/if}

    </div>
</div>

<script>
    var checkoutForm = $('#jp_payments_form_id_{$tab_id}');

    (function(_, $) {
        var ccFormId = '{$id_suffix}';
        var ccBrand = '';

        $(function () {
            //数値だけ受付
            $(".cc-numeric").numeric({
                negative: false
            });
        });


        //全角＞半角変換
        $('.cc-henkan').change(function(){
            var icons           = $('.cc-icons_' + ccFormId + ' li');
            var ccNumber        = $(".cc-number_" + ccFormId);
            var ccNumberInput   = $("#" + ccNumber.attr("for"));

            var text  = $(this).val();
            var hen = text.replace(/[Ａ-Ｚａ-ｚ０-９]/g,function(s){
                return String.fromCharCode(s.charCodeAt(0)-0xFEE0);
            });

            hen = hen.replace(/[^0-9]/g, "");
            $(this).val(hen);

            ccNumberInput.validateCreditCard(function(result) {
                if (result.card_type) {
                    icons.removeClass('active');
                    icons.filter(' .cm-cc-' + result.card_type.name).addClass('active');
                }
            });
        });


        $.ceEvent('on', 'ce.commoninit', function() {

            // 新チェックアウト方式に対応
            if(document.getElementById('litecheckout_payments_form')){
                checkoutForm = $('#litecheckout_payments_form');
            }
            checkoutForm.off('submit', submitHandler);
            checkoutForm.on('submit', submitHandler);

            var icons           = $('.cc-icons_' + ccFormId + ' li');
            var ccNumber        = $(".cc-number_" + ccFormId);
            var ccNumberInput   = $("#" + ccNumber.attr("for"));
            var ccMax = 0;//カードブランド別最大数
            var ccCv2           = $(".cc-cvv2_" + ccFormId);

            //カード入力が表示されている時だけ
            if ($(ccNumberInput).is(':visible')) {
                ccNumberInput.validateCreditCard(function(result) {
                    icons.removeClass('active');
                    //カードブランド
                    if (result.card_type) {
                        var userInput = ccNumberInput.val();
                        var userInputLenght = userInput.length;
                        ccBrand = result.card_type.name;
                        icons.filter(' .cm-cc-' + result.card_type.name).addClass('active');

                        if(result.card_type.name == 'visa') {
                            ccMax = result.card_type.valid_length[3];//最大数
                        }else{
                            ccMax = result.card_type.valid_length[0];//最大数
                        }

                        if(userInputLenght >= ccMax){
                            userInput = userInput.substring(0, ccMax);//許容文字数にする
                            ccNumberInput.val(userInput);	//フォーム文に許容文字数を設定
                        }

                        if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                            ccCv2.removeClass("cm-required");
                        } else {
                            ccCv2.addClass("cm-required");
                        }
                    }
                });
            }

            fn_check_sln_cc_payment_type($('#jp_cc_method').val());
        });


        //カード有効期限チェック YY（YYの方だけで監視する）
        $.ceFormValidator('registerValidator', {
            class_name: 'cm-cc-exp-year-jp',
            message: _.tr('error_validator_cc_exp_jp'),
            func: function(id) {
                var input = $('#' + id);
                var flag = false;//RETURN

                var yy_val = input.val();
                var mm_val = $('#credit_card_month_{$id_suffix}').val();

                if(yy_val.length == 2 && mm_val.length == 2){
                    flag = check_exp_date(yy_val, mm_val);
                }
                return flag;
            }
        });



        //カード長さチェック
        $.ceFormValidator('registerValidator', {
            class_name: 'cm-cc-number-check-length-jp',
            message: _.tr('error_validator_cc_check_length_jp'),
            func: function(id) {
                var input = $('#' + id);
                var flag = false;//RETURN
                var valid_length = 16;
                $(input).validateCreditCard(function(result) {
                    if (result.card_type) {
                        //桁数
                        if(result.card_type.name == 'amex'){
                            valid_length = 15;
                        }else if(result.card_type.name == 'diners_club_international') {
                            valid_length = 14;
                        }

                        if(input.val().length == valid_length){
                            flag = true;
                        }else{
                            flag = false;
                        }
                    }
                });
				if(flag){
                    $('.ty-cc-icons').css('bottom', '38px');
                }
                else{
                    $('.ty-cc-icons').css('bottom', '64px');
                }
                return flag;
            }
        });
    })(Tygh, Tygh.$);

    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 BOF
    // マーケットプレイス版対応
    ///////////////////////////////////////////////
    // グローバル変数
    // カード情報
    var cardNoVal = '';
    var cardYearVaal = '';
    var cardMonthVal = '';
    var scCdVal = '';

    // 出品者の数
    var global_cnt = 1;
    var vendor_cnt = {$vendor_cnt};
    ///////////////////////////////////////////////
    // Modified by takahashi from cs-cart.jp 2019 EOF
    ///////////////////////////////////////////////

    // 新チェックアウト方式に対応
    var isSubmit = false;

    // チェックアウトフォームのsubmitイベントハンドラ
    function submitHandler(event) {
        // 新チェックアウト方式に対応
        if(!document.getElementById('credit_card_number_{$id_suffix}')) {
            return;
        }

        event.preventDefault();

        // フォームのValidationチェック
        var isFormVal = checkoutForm.ceFormValidator('check');

        cardNoVal = document.getElementById('credit_card_number_{$id_suffix}').value.replace(/\s+/g, "");
        cardYearVaal = document.getElementById('credit_card_year_{$id_suffix}').value;
        cardMonthVal = document.getElementById('credit_card_month_{$id_suffix}').value;

        var scCdObj = document.getElementById('credit_card_cvv2_{$id_suffix}');

        // セキュリティコードが表示されている場合
        if(scCdObj) {
            scCdVal = scCdObj.value;
        }

        // フォームのValidationがOKの場合
        if(isFormVal){
            SpsvApi.spsvCreateToken(cardNoVal, cardYearVaal, cardMonthVal, scCdVal, '', '', '', '', '');
        }
    }

    function setToken(token, card) {
        /* 新チェックアウト方式に対応
        document.getElementById('credit_card_number_{$id_suffix}').value = '';
        document.getElementById('credit_card_year_{$id_suffix}').value = '';
        document.getElementById('credit_card_month_{$id_suffix}').value = '';

        var scCdObj = document.getElementById('credit_card_cvv2_{$id_suffix}');
        // セキュリティコードが表示されている場合
        if(scCdObj) {
            scCdObj.value = '';
        }
        */

        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 BOF
        // マーケットプレイス版対応
        ///////////////////////////////////////////////
        // トークンの数だけトークン用のhiddenタグを生成
        document.getElementById('token_area').innerHTML += "<input type='hidden' value='" + token + "' id='token' name=payment_info[token][] />";

        // 次回以降のお買い物でこのカード情報を使用する
        var register_yes = false;
        if(document.getElementById('register_yes')){
            register_yes = document.getElementById('register_yes').checked;
        }

        // 出品者の数だけトークンを生成した、あるいは次回以降のお買い物でこのカード情報を使用する場合
        // 新チェックアウト方式に対応
        if( (global_cnt == vendor_cnt || register_yes == true) && !isSubmit ) {
            isSubmit = true;
            checkoutForm.get(0).submit();
        }

        // マーケットプレイス版で次回以降のお買い物でこのカード情報を使用しない場合は出品者の数だけトークンを発行
        if( global_cnt < vendor_cnt && register_yes != true ) {
            SpsvApi.spsvCreateToken(cardNoVal, cardYearVaal, cardMonthVal, scCdVal, '', '', '', '', '');
            global_cnt += 1;
        }
        ///////////////////////////////////////////////
        // Modified by takahashi from cs-cart.jp 2019 EOF
        ///////////////////////////////////////////////
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

    //現在YYMM
    function get_now_yymm() {
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth() + 1;
        var yy = y.toString().slice(-2);
        var mm = ("0" + m).slice(-2);

        return yy + mm;
    }

    //有効期限が今月より未来か
    function check_exp_date(yy, mm) {
        var yymm_val = yy + mm;
        var now_yymm = get_now_yymm();
        //var now_mm = get_now_yymm("mm");
        if(mm < 13 && yymm_val >= now_yymm){
            return true;
        }else{
            return false;
        }
        return true;
    }
</script>

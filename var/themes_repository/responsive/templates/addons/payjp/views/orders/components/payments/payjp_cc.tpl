{* $Id: payjp_cc.tpl by takahashi from cs-cart.jp 2020 *}

{script src="js/lib/creditcardvalidator_jp/jquery.numeric.min.js"}
{script src="js/lib/creditcardvalidator_jp/jquery.creditCardValidator.js"}
<style>
    .ty-cc-icons {
        bottom: 38px;
    }
</style>
<script>
    //<![CDATA[
    (function(_, $) {
        $(_.doc).on('change', '.payjp-cm-select-credit-card', function() {
            var self = $(this);
            if (self.val() == 0) {
                $('#payjp_new_cc').show();
                $('#payjp_new_cc :input').prop('disabled', false);
            } else {
                $('#payjp_new_cc').hide();
                $('#payjp_new_cc :input').prop('disabled', true);
            }
            $.ceDialog('get_last').ceDialog('reload');
        });
        
        $(_.doc).on('click', '#litecheckout_place_order', function() {
            var self = $('.payjp_pf-cm-select-credit-card');
            if (self.val() == 0) {
                $('#payjp_pf_new_cc :input').prop('disabled', false);
            } else {
                $('#payjp_pf_new_cc :input').prop('disabled', true);
            }
        });
    }(Tygh, Tygh.$));
    //]]>
</script>

{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}

{assign var="payjp_card" value=$auth.user_id|fn_payjp_get_registered_card_info}

<div class="clearfix">

{if $payjp_card}
    <div class="ty-credit-card">
        <div class="ty-credit-card__control-group ty-control-group">
            <div class="ty-control-group" id="select_card_wrapper">
                <label for="payjp_card" class="ty-control-group__title">{__("credit_card")}</label>
                <select name="payment_info[is_regcard]" id="payjp_card" class="payjp-cm-select-credit-card select2-checkout">
                    <option value="Y">{$payjp_card['brand']} {$payjp_card['card_number']} {__("jp_payjp_valid_thru")} {$payjp_card['card_valid_term']}</option>
                    <option value="">{__('jp_payjp_use_new_credit_card')}</option>
                </select>
            </div>
        </div>
    </div>
{/if}

    <input type='hidden' value='' id='token' name=payment_info[token] />
    <input type='hidden' value='' id='errorMsg' name=payment_info[errorMsg] />

    <div id="payjp_new_cc" class="ty-credit-card {if $payjp_card}hidden{/if}">
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

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_payjp_security_code")}</label>
            <input type="tel" id="credit_card_cvv2_{$id_suffix}" data-name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cc-numeric cc-henkan" />

            <div class="ty-cvv2-about">
                <span class="ty-cvv2-about__title">{__("jp_payjp_what_is_security_code")}</span>
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

    </div>
</div>

<script src="https://js.pay.jp/"></script>
<script>Payjp.setPublicKey('{$addons.payjp.public_key}');</script>
{if $payjp_card}
<script>
    $('#payjp_new_cc :input').prop('disabled', true);
</script>
{/if}
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
            if (ccNumberInput) {
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

    // 新チェックアウト方式に対応
    var isSubmit = false;

    // チェックアウトフォームのsubmitイベントハンドラ
    function submitHandler(event) {
        // 新チェックアウト方式に対応
        if(!document.getElementById('credit_card_cvv2_{$id_suffix}')) {
            return;
        }

        var payjp_card = document.getElementById('payjp_card');
        var payjp_card_value = "";
        if(payjp_card){
            payjp_card_value = payjp_card.value;
        }
        // 登録済みカードを使わない場合
        if(payjp_card_value == "") {

        event.preventDefault();
        checkoutForm.find("input[type=submit]").prop("disabled", true);

            var cardNoVal = document.getElementById('credit_card_number_{$id_suffix}').value.replace(/\s+/g, "");
            var cardMonthVal = document.getElementById('credit_card_month_{$id_suffix}').value;
            var cardYearVal = '20' + document.getElementById('credit_card_year_{$id_suffix}').value;
            var secCdVal = document.getElementById('credit_card_cvv2_{$id_suffix}').value;

            // フォームのValidationチェック
            var isFormVal = checkoutForm.ceFormValidator('check');

            // フォームのValidationがOKの場合
            // 新チェックアウト方式に対応
            if (isFormVal && !isSubmit) {
                isSubmit = true;

                // カード情報をセット
                var cardObject = {
                    number: cardNoVal,
                    cvc: secCdVal,
                    exp_month: cardMonthVal,
                    exp_year: cardYearVal,
                };

                Payjp.createToken(cardObject, getToken);
            }
        }
    }

    function getToken (s, response) {

        if (response.error) {
            // エラーメッセージをセット
            document.getElementById('errorMsg').value = response.error.message;
        }
        else {
            var token = response.id;

            // トークン情報をセット
            document.getElementById('token').value = token;
        }

        checkoutForm.get(0).submit();
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

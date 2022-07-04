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
            <label for="ap_sbps_cc_card_num" class="ty-control-group__title cm-required cm-cc-number cc-number_{$id_suffix} cm-cc-number-check-length-jp cc-numeric">{__('card_number')}</label>
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
            <input size="35" type="tel" id="ap_sbps_cc_card_num" value="" class="ty-credit-card__input cm-focus cm-autocomplete-off cc-numeric cc-henkan" />
        </div>

        <div class="ty-credit-card__control-group ty-control-group">
            <label for="ap_sbps_card_thru" class="ty-control-group__title cm-cc-exp-year-jp cm-required">{__('valid_thru')}</label>
            <div id="ap_sbps_card_thru">
                <input type="tel" id="ap_sbps_card_month" value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;&nbsp;/&nbsp;&nbsp;
                <input type="tel" id="ap_sbps_card_year"  value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;
            </div>
        </div>

        {if $rb_processor_params.use_cvv == 'true'}
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="ap_sbps_security_code" class="ty-control-group__title cm-required cm-cc-cvv2 cm-autocomplete-off">{__('sbps_security_code')}</label>
                <input type="tel" id="ap_sbps_security_code" value="" size="4" maxlength="4" class="ty-credit-card__cvv-field-input cc-numeric cc-henkan cm-autocomplete-off cc-numeric cc-henkan" />

                <div class="ty-cvv2-about">
                    <span class="ty-cvv2-about__title">{__('sbps_what_is_security_code')}</span>
                    <div class="ty-cvv2-about__note">

                        <div class="ty-cvv2-about__info mb30 clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/visa_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__('visa_card_discover')}</h5>
                                <p>{__('credit_card_info')}</p>
                            </div>
                        </div>
                        <div class="ty-cvv2-about__info clearfix">
                            <div class="ty-cvv2-about__image">
                                <img src="{$images_dir}/express_cvv.png" alt="" />
                            </div>
                            <div class="ty-cvv2-about__description">
                                <h5 class="ty-cvv2-about__description-title">{__('american_express')}</h5>
                                <p>{__('american_express_info')}</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        {/if}

        <input type="hidden" id="ap_sbps_token" name="payment_info[token]" value="">
        <input type="hidden" id="ap_sbps_token_key" name="payment_info[token_key]" value="">
    </div>
</div>

{assign var='token_js_url' value=$smarty.const.SBPS_TOKEN_JS_URL}
<script type="text/javascript" src="{$token_js_url[$rb_processor_params.mode]}"></script>
<script type="text/javascript">
    is_submit = false;

    Tygh.$.ceEvent('on', 'ce.commoninit', function(e_init) {
        const cc_form_id = '{$id_suffix}';
        const cc_number_id = document.querySelector('.cc-number_' + cc_form_id) ? document.querySelector('.cc-number_' + cc_form_id).getAttribute('for') : null;
        const cc_number = cc_number_id ? document.getElementById(cc_number_id) : null;
        const cc_henkan = document.querySelectorAll('.cc-henkan');
        const icons = document.querySelectorAll('.cc-icons_' + cc_form_id + ' li');

        let form = $('#jp_payments_form_id_{$tab_id}');

        if (document.getElementById('litecheckout_payments_form')) {
            form = $('#litecheckout_payments_form');
        }

        form.off('submit', submitHandler);
        form.on('submit', submitHandler);

        // カード入力が表示されている時だけ
        if (cc_number && $(cc_number).is(':visible')) {
            $(cc_number).validateCreditCard(function(r) {
                Array.prototype.forEach.call(icons, function(e, index) {
                    e.classList.remove('active');
                });

                // カードブランド
                if (r.card_type) {
                    let cc_max = 0; //カードブランド別最大数

                    Array.prototype.forEach.call(icons, function(e, index) {
                        if (e.classList.contains('cm-cc-' + r.card_type.name)) {
                            e.classList.add('active');
                        }
                    });

                    if(r.card_type.name === 'visa') {
                        cc_max = r.card_type.valid_length[3]; //最大数
                    } else {
                        cc_max = r.card_type.valid_length[0]; //最大数
                    }

                    if(cc_number.value.length >= cc_max){
                        cc_number.value = cc_number.value.substring(0, cc_max); //フォーム文に許容文字数を設定
                    }
                }
            });

            if (cc_henkan) {
                Array.prototype.forEach.call(cc_henkan, function (e_henkan, index) {
                    e_henkan.addEventListener('change', function(e_change) {
                        const icons = document.querySelectorAll('.cc-icons_' + cc_form_id + ' li');
                        const cc_number_id = document.querySelector('.cc-number_' + cc_form_id) ? document.querySelector('.cc-number_' + cc_form_id).getAttribute('for') : null;
                        const cc_number = cc_number_id ? document.getElementById(cc_number_id) : null;

                        e_change.target.value = e_change.target.value.replace(/[Ａ-Ｚａ-ｚ０-９]/g, function(s) {
                            return String.fromCharCode(s.charCodeAt(0) - 0xFEE0);
                        }).replace(/[^0-9]/g, '');

                        if (cc_number) {
                            $(cc_number).validateCreditCard(function(r) {
                                if (r.card_type) {
                                    Array.prototype.forEach.call(icons, function(e, index) {
                                        e.classList.remove('active');

                                        if (e.classList.contains('cm-cc-' + r.card_type.name)) {
                                            e.classList.add('active');
                                        }
                                    });
                                }
                            });
                        }
                    });
                });
            }
        }
    });

    // カード有効期限チェック YY（YYの方だけで監視する）
    Tygh.$.ceFormValidator('registerValidator', {
        class_name: 'cm-cc-exp-year-jp',
        message: Tygh.tr('error_validator_cc_exp_jp'),
        func: function(id) {
            let flag = false;

            const yy_val = document.getElementById('ap_sbps_card_year').value;
            const mm_val = document.getElementById('ap_sbps_card_month').value;

            if(yy_val.length === 2 && mm_val.length === 2){
                flag = checkExpDate(yy_val, mm_val);
            }

            return flag;
        }
    });

    // カード長さチェック
    Tygh.$.ceFormValidator('registerValidator', {
        class_name: 'cm-cc-number-check-length-jp',
        message: Tygh.tr('error_validator_cc_check_length_jp'),
        func: function(id) {
            const input = document.getElementById(id);

            let flag = false;
            let valid_length = 16;

            $(input).validateCreditCard(function(r) {
                if (r.card_type) {
                    if(r.card_type.name === 'amex') {
                        valid_length = 15;
                    } else if(r.card_type.name === 'diners_club_international') {
                        valid_length = 14;
                    }

                    flag = input.value.length === valid_length;
                }
            });

            //カードイメージの調整
            if(flag || ('{$rb_processor_params.mode}' === 'test' && input.value.length > 0)) {
                document.querySelector('.ty-cc-icons').style.bottom = '23px';
                flag = true;
            } else {
                document.querySelector('.ty-cc-icons').style.bottom = '45px';
            }

            return flag;
        }
    });

    function submitHandler(e) {
        if(!document.getElementById('ap_sbps_cc_card_num')) {
            return;
        }

        e.preventDefault();

        // フォームのValidationチェック
        const is_form_val = $(e.target).ceFormValidator('check');

        // フォームのValidationがOKの場合
        if(is_form_val && !is_submit) {
            is_submit = true;

            const $ap_sbps_security_code = document.getElementById('ap_sbps_security_code');

            com_sbps_system.generateToken({
                merchantId : '{$rb_processor_params.merchant_id}',
                serviceId : '{$rb_processor_params.service_id}',
                ccNumber : document.getElementById('ap_sbps_cc_card_num').value,
                ccExpiration : '20' + document.getElementById('ap_sbps_card_year').value + document.getElementById('ap_sbps_card_month').value,
                securityCode: $ap_sbps_security_code === null ? '' : document.getElementById('ap_sbps_security_code').value
            }, function(r) {
                if (r.result === 'OK') {
                    document.getElementById('ap_sbps_token').value = r.tokenResponse.token;
                    document.getElementById('ap_sbps_token_key').value = r.tokenResponse.tokenKey;
                    e.target.submit();
                } else {
                    console.log('ERROR');
                }
            });
        }
    }

    // 現在YYMM
    function getNowYymm() {
        const now = new Date();
        return now.getFullYear().toString().slice(-2) + ('0' + (now.getMonth() + 1)).slice(-2);
    }

    // 有効期限が今月より未来か
    function checkExpDate(yy, mm) {
        return (mm !== '00' && mm < 13 && ((yy + mm) >= getNowYymm()));
    }
</script>

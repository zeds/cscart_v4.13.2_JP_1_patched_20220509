<script class="cm-ajax-force">
    // 新チェックアウト方式に対応
    var form_name;
    if(document.getElementById('litecheckout_payments_form')){
        form_name = "litecheckout_payments_form";
    }
    else{
        form_name = "{$form_name}";
    }

    (function(_, $) {
        //言語変数を定義
        _.tr({
            'card_invalid': '{__("square_msg_card_not_valid")|escape:"javascript"}',
            'cvv_invalid': '{__("square_msg_cvv_not_valid")|escape:"javascript"}',
            'exp_invalid': '{__("square_msg_exp_not_valid")|escape:"javascript"}',
        });

        $(_.doc).ready(function(){
            if (typeof (_.square_script_processed) == 'undefined') {
                $.getScript('//js.{$smarty.const.SQUARE_API_URL}/v2/paymentform', function(){
                    fn_process_square_scripts();
                    _.square_script_processed = true;
                });
            } else {
                fn_process_square_scripts();
            }
        });
        
        function fn_process_square_scripts() {
            
            var applicationId = '{$addons.sd_square_payment.app_id|escape:"javascript"}';
            var locationId = '{$addons.sd_square_payment.location_id|escape:"javascript"}';
            var show_postal = '{$addons.sd_square_payment.show_postal_code|escape:"javascript"}';
            
            var squareFormParams = {
                applicationId: applicationId,
                locationId: locationId,
                inputClass: 'sq-input',
                inputStyles: [{
                    fontSize: '13px',
                    padding: '4px 8px',
                    lineHeight: '30px',
                    fontFamily: "Arial"
                }],
                cardNumber: {
                    elementId: 'sq_card_number',
                    placeholder: '•••• •••• •••• ••••'
                },
                cvv: {
                    elementId: 'sq_cvv',
                    placeholder: 'CVV'
                },
                expirationDate: {
                    elementId: 'sq_expiration_date',
                    placeholder: 'MM/YY'
                },
                postalCode: {
                    elementId: 'sq_postal_code'
                },
                applePay: false,
                masterpass: false,
                callbacks: {
                    methodsSupported: function (methods) {

                    },
                    createPaymentRequest: function () {

                        var paymentRequestJson ;
                        /* ADD CODE TO SET/CREATE paymentRequestJson */
                        return paymentRequestJson ;
                    },
                    cardNonceResponseReceived: function(errors, nonce, cardData) {
                        // 新チェックアウト方式に対応
                        var form = $('form[name="' + form_name + '"]');
                        form.removeClass('cm-square-errors cm-square-valid');
                        if (errors) {
                            var error_message = '';
                            
                            errors.forEach(function(error) {
                                if(error.message == "Credit card number is not valid"){
                                    error.message = _.tr('card_invalid');
                                }
                                if(error.message == "CVV is not valid"){
                                    error.message = _.tr('cvv_invalid');
                                }
                                if(error.message == "Expiration date is not valid"){
                                    error.message = _.tr('exp_invalid');
                                }

                                error_message = error_message + '<br />  ' + error.message;
                            });
                            
                            form.data('caSquareDataErrorMessage', error_message);
                            form.addClass('cm-square-errors');
                        } else {
                            $('#card_nonce').val(nonce);
                            form.addClass('cm-square-valid');
                            $('#{$button_id}').click();
                        }
                    },
                    unsupportedBrowserDetected: function() {

                    },
                    paymentFormLoaded: function() {
                        
                    }
                }
            };
            
            if (show_postal != 'Y') {
                squareFormParams.postalCode = false;
            }
            
            _.paymentForm = new SqPaymentForm(squareFormParams);
            _.paymentForm.build();

        }
    }(Tygh, Tygh.$));
</script>
<script>
    // 新チェックアウト方式に対応
    var form_name;
    if(document.getElementById('litecheckout_payments_form')){
        form_name = "litecheckout_payments_form";
    }
    else{
        form_name = "{$form_name}";
    }

    (function(_, $) {
        // 新チェックアウト方式に対応
        $.ceEvent('on', 'ce.formpre_' + form_name, function(form, clicked_elm) {
            if ($('input[name="payment_info[card_nonce]"]').length) {
                var card_id_input = $('input[name="payment_info[card_id]"]:checked');
                if (card_id_input.length && card_id_input.val()) {
                    return true;
                }
                
                if (!form.hasClass('cm-square-valid')) {
                    _.paymentForm.requestCardNonce();
                    setTimeout(function() {
                        if (form.hasClass('cm-square-errors')) {
                            var error_message = form.data('caSquareDataErrorMessage');
                            form.data('caSquareDataErrorMessage', '');
                            if (error_message && !$('#card_nonce').val()) {
                                $.ceNotification('show', {
                                    type: 'E',
                                    title: _.tr('error'),
                                    message: error_message
                                });
                            }
                            return false;
                        }
                        else{
                            return true;
                        }
                    }, 1000);
                }
            }
            return true;
        });
    }(Tygh, Tygh.$));
</script>
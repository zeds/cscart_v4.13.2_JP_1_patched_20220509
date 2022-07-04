{if !$cart.square_order_id}
<div id="sq_ccbox">
    <div class="control-group">
        <label class="control-label cm-required">{__("addons.sd_square_payment.card_number")}:</label>
        <div class="controls">
            <div id="sq_card_number"></div>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label cm-required">{__("addons.sd_square_payment.cvv")}:</label>
        <div class="controls">
            <div id="sq_cvv"></div>
        </div>
    </div>
    <div class="control-group">
        <label class="control-label cm-required">{__("addons.sd_square_payment.expiration_date")}:</label>
        <div class="controls">
            <div id="sq_expiration_date"></div>
        </div>
    </div>
    {if $addons.sd_square_payment.show_postal_code == "Y"}
        <div class="control-group">
            <label class="control-label cm-required">{__("addons.sd_square_payment.postal_code")}:</label>
            <div class="controls">
                <div id="sq_postal_code"></div>
            </div>
        </div>
    {/if}
    <div class="control-group">
        <label class="control-label cm-required" for="cardholder_name">{__("addons.sd_square_payment.cardholder")}:</label>
        <div class="controls">
            <input type="text" name="payment_info[cardholder_name]" id="cardholder_name" class="sq-input">
        </div>
    </div>
    <input type="hidden" id="card_nonce" name="payment_info[card_nonce]">
</div>
<script class="cm-ajax-force">
    (function(_, $) {
        //言語変数を定義
        _.tr({
            'card_invalid': '{__("square_msg_card_not_valid")|escape:"javascript"}',
            'cvv_invalid': '{__("square_msg_cvv_not_valid")|escape:"javascript"}',
            'exp_invalid': '{__("square_msg_exp_not_valid")|escape:"javascript"}',
        });

        $(_.doc).ready(function(){
            if (typeof (_.square_script_processed) == 'undefined') {
                $.getScript('//js.squareup.com/v2/paymentform', function(){
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
                        var form = $('form[name="om_cart_form"]');
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
                            $('input[name="dispatch[order_management.place_order]"]').click();
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
    (function(_, $) {
        $.ceEvent('on', 'ce.formpre_om_cart_form', function(form, clicked_elm) {
            if ($('input[name="payment_info[card_nonce]"]').length) {
                if ($(clicked_elm).attr('name') != "dispatch[order_management.place_order]") {
                    return true;
                }
                if (!form.hasClass('cm-square-valid')) {
                    _.paymentForm.requestCardNonce();
                    setTimeout(function() {
                        if (form.hasClass('cm-square-errors')) {
                            var error_message = form.data('caSquareDataErrorMessage');
                            form.data('caSquareDataErrorMessage', '');
                            if (error_message) {
                                $.ceNotification('show', {
                                    type: 'E',
                                    title: _.tr('error'),
                                    message: error_message
                                });
                            }
                        }
                    }, 1000);
                    return false;
                }
            }
            return true;
        });
    }(Tygh, Tygh.$));
</script>
{else}
    <p>
        {__("addons.sd_square_payment.transaction_already_exists")}
    </p>
{/if}
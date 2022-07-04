{* pay_with_amazon_button.tpl by tommy from cs-cart.jp 2016 *}
<div id="AmazonPayButton" class="ty-amazon-button ty-amazon-button-{if $obj_id}product{else}checkout{/if}"></div>
{if $addons.amazon_checkout.add_to_cart == "Y" && $obj_id}
    <script type='text/javascript'>
        (function(_, $) {
            var cartButton = $('#button_cart_'+'{$obj_prefix}'+'{$obj_id}');
            $.ceEvent('on', 'ce.commoninit', function() {
                $('#AmazonPayButton img').addClass('cm-external-click').attr('data-ca-external-click-id', 'button_cart_'+'{$obj_prefix}'+'{$obj_id}');
                $('#AmazonPayButton img').on('click', function() {
                    cartButton.addClass('cm-amazon-click');
                });
            });
            $.ceEvent('on', 'ce.formajaxpost_product_form_'+'{$obj_prefix}'+'{$obj_id}', function(response, params) {
                if (cartButton.hasClass('cm-amazon-click')) {
                    $.each(response.notifications, function(index, notification) {
                        if (notification.type == 'I') {
                            delete response.notifications[index];
                        }
                    });

                    if ($('#ajax_loading_box').hasClass('ty-ajax-loading-box_text_block')) {
                        params.keep_status_box = true;
                    }
                }
                cartButton.removeClass('cm-amazon-click');
            });
        }(Tygh, Tygh.$));
    </script>
{/if}

<script type='text/javascript'>
    (function(_, $) {
        function fn_amazon_login() {
            var authRequest,
                loginOptionsScope = "profile postal_code payments:widget payments:shipping_address",
                loginOptionsPopup = "true",
                loginOptions = { scope: loginOptionsScope, popup: loginOptionsPopup },
                url = fn_url('checkout.checkout.amazon_checkout&check_amount=1');

                authRequest = amazon.Login.authorize(loginOptions, function(response) {
                    if ( response.error ) {
                        authRequest = amazon.Login.authorize(loginOptions, url);
                    } else {
                        $.toggleStatusBox('show', {
                            statusContent: '<span class="ty-ajax-loading-box-with__text-wrapper">' + '{__("amazon_please_wait")}' + '</span>',
                            statusClass: 'ty-ajax-loading-box_text_block',
                        });
                        setTimeout(function () {
                            window.location = url;
                        }, {$addons.amazon_checkout.delay_before_redirect|default:2000});
                    }
                });
        }

        if(document.getElementById('AmazonPayButton')) {
            OffAmazonPayments.Button("AmazonPayButton", "{$addons.amazon_checkout.merchant_id|escape:'javascript'}", {
                type: "{$addons.amazon_checkout.button_type|escape:'javascript'}",
                color: "{$addons.amazon_checkout.button_color|escape:'javascript'}",
                size: "{$addons.amazon_checkout.button_size|escape:'javascript'}",
                useAmazonAddressBook: true,
                authorization: function () {
                    fn_amazon_login();
                },
                onError: function (error) {
                    $.ceNotification('show', {
                        type: 'E',
                        title: '{__("error")}',
                        message: error.getErrorMessage()
                    });
                }
            });
        }
    }(Tygh, Tygh.$));
</script>

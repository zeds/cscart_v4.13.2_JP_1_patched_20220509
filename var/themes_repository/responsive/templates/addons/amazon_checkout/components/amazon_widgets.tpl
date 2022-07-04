{* amazon_widgets.tpl by tommy from cs-cart.jp 2016 *}

{include file="common/subheader.tpl" title=__("shipping_address")}
<div id="addressBookWidgetDiv" style="width:100%; height:300px;margin:3%"></div>
{include file="common/subheader.tpl" title=__("payment_methods")}
<div id="walletWidgetDiv" style="width:100%; height:300px;margin:3%"></div>
{assign var="return_url" value="checkout.cart"|fn_url}
<script>
    var $ = Tygh.$; _ = Tygh;
    new OffAmazonPayments.Widgets.AddressBook({
        sellerId: '{$addons.amazon_checkout.merchant_id|escape:"javascript"}',
        onOrderReferenceCreate: function (orderReference) {
            orderReferenceId = orderReference.getAmazonOrderReferenceId();
            Tygh.$.ceAjax('request', fn_url("amazon_checkout.create_order_reference"), {
                method: 'post',
                result_ids: 'checkout*,cart_status*,cart_items,payment-methods',
                full_render: true,
                data: {
                    orderReferenceId: orderReferenceId,
                }
            });
        },
        onAddressSelect: function (widget) {
            
            Tygh.$.ceAjax('request', fn_url("amazon_checkout.change_address"), {
                method: 'post',
                result_ids: 'checkout*,cart_status*,cart_items,payment-methods',
                full_render: true,
            });
        },
        design: {
            designMode: 'responsive'
        },
        onError: function (error) {
            $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: error.getErrorMessage()
            });
            fn_show_login_button();
        }
    }).bind("addressBookWidgetDiv");

    new OffAmazonPayments.Widgets.Wallet({
        sellerId: '{$addons.amazon_checkout.merchant_id|escape:"javascript"}',
        onPaymentSelect: function () {
            
        },
        design: {
            designMode: 'responsive'
        },
        onError: function (error) {
            $.ceNotification('show', {
                type: 'E',
                title: _.tr('error'),
                message: error.getErrorMessage()
            });
            fn_show_login_button();
        }
    }).bind("walletWidgetDiv");
</script>
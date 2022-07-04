{* us.tpl by tommy from cs-cart.jp 2016 *}

{if $addons.amazon_checkout.test_mode == "Y"}
    <script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/sandbox/js/Widgets.js?sellerId={$addons.amazon_checkout.merchant_id|escape:"javascript"}'></script>
{else}
    <script type='text/javascript' src='https://static-na.payments-amazon.com/OffAmazonPayments/us/js/Widgets.js?sellerId={$addons.amazon_checkout.merchant_id|escape:"javascript"}'></script>
{/if}
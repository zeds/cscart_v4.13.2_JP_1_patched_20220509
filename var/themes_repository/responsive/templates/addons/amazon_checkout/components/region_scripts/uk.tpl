{* uk.tpl by tommy from cs-cart.jp 2016 *}

{if $addons.amazon_checkout.test_mode == "Y"}
    <script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/sandbox/js/Widgets.js?sellerId={$addons.amazon_checkout.merchant_id|escape:"javascript"}'></script>
{else}
    <script type='text/javascript' src='https://static-eu.payments-amazon.com/OffAmazonPayments/uk/js/Widgets.js?sellerId={$addons.amazon_checkout.merchant_id|escape:"javascript"}'></script>
{/if}
{* jp.tpl by tommy from cs-cart.jp 2016 *}

{if $addons.amazon_checkout.test_mode == "Y"}
    <script type='text/javascript' src='https://static-fe.payments-amazon.com/OffAmazonPayments/jp/sandbox/lpa/js/Widgets.js'></script>
{else}
    <script type='text/javascript' src='https://static-fe.payments-amazon.com/OffAmazonPayments/jp/lpa/js/Widgets.js'></script>
{/if}

{* $Id: head_scripts.post.tpl by takahashi from cs-cart.jp 2017 *}
{if $runtime.controller == "checkout" && $runtime.mode == "checkout"}
    {if isset($payment_method.processor_params.mode) }
        {if $payment_method.processor_params.mode == "test"}
            <script src="https://sandbox.paygent.co.jp/js/PaygentToken.js" ></script>
        {elseif $payment_method.processor_params.mode == "live"}
            <script src="https://token.paygent.co.jp/js/PaygentToken.js" ></script>
        {/if}
    {/if}
{/if}

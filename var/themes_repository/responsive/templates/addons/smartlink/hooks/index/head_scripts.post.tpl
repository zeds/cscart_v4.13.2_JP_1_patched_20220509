{* $Id: head_scripts.post.tpl by takahashi from cs-cart.jp 2017 *}
{if isset($payment_method.processor_params.token_ninsyo_code) }
    {if $payment_method.processor_params.token_ninsyo_code && $runtime.controller == "checkout" && $runtime.mode == "checkout"}
        {if $payment_method.processor_params.mode == "test"}
            <script src="https://www.test.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$payment_method.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
        {elseif $payment_method.processor_params.mode == "live"}
            <script src="https://www.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$payment_method.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
        {/if}
    {/if}
{/if}

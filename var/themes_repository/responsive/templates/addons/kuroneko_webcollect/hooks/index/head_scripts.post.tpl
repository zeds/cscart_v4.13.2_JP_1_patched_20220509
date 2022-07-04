{* $Id: head_scripts.post.tpl by takahashi from cs-cart.jp 2017 *}
{if $runtime.controller == "checkout" && $runtime.mode == "checkout"}
    {if isset($payment_method.processor_params.mode) }
        {if $payment_method.processor_params.mode == "test"}
            <script class="webcollect-embedded-token" src="https://ptwebcollect.jp/test_gateway/token/js/embeddedTokenLib.js" ></script>
        {elseif $payment_method.processor_params.mode == "live"}
            <script class="webcollect-embedded-token" src="https://api.kuronekoyamato.co.jp/api/token/js/embeddedTokenLib.js" ></script>
        {/if}
    {/if}
    {script src="js/addons/kuroneko_webcollect/sha256.js"}
{/if}

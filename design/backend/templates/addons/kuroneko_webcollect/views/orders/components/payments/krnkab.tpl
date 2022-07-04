{* $Id: krnkab.tpl by tommy from cs-cart.jp 2016 *}
{* Modified by takahashi from cs-cart.jp 2018 *}
{* “¯«‘Î‰ *}

{if $cart.ship_to_another}
    <input type="hidden" name="payment_info[sendDiv]" value=1 />
{else}
    {if $payment_method.processor_params.operate == "INCLUDE"}
        <input type="hidden" name="payment_info[sendDiv]" value=2 />
    {else}
        <input type="hidden" name="payment_info[sendDiv]" value=0 />
    {/if}
{/if}

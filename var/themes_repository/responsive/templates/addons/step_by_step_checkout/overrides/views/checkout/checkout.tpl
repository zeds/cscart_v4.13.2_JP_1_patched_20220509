{* Modified by takahashi from cs-cart.jp 2019 *}
{* Amazon Pay アドオンに対応 *}
{script src="js/tygh/exceptions.js"}
{script src="js/tygh/checkout.js"}

{$smarty.capture.checkout_error_content nofilter}

{* Modified by takahashi from cs-cart.jp 2019 BOF *}
{if $show_amazon_checkout}
    {include file="addons/amazon_checkout/components/amazon_checkout.tpl"}
    {capture name="mainbox_title"}<span class="ty-checkout__title">{__("amazon_checkout_page")}&nbsp;<i class="ty-checkout__title-icon ty-icon-lock"></i></span>{/capture}
{else}
{include file="views/checkout/components/checkout_steps.tpl"}

{capture name="mainbox_title"}<span class="ty-checkout__title">{__("secure_checkout")}&nbsp;<i class="ty-checkout__title-icon ty-icon-lock"></i></span>{/capture}
{/if}
{* Modified by takahashi from cs-cart.jp 2019 EOF *}
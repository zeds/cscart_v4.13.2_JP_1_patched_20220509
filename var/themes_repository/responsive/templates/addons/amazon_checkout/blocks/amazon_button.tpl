{* amazon_button.tpl by tommy from cs-cart.jp 2016 *}
{if $show_amazon_checkout}
    {include file="buttons/button.tpl" but_id="amazon_logout" but_meta="ty-btn__big ty-btn" but_text=__("amazon_logout") but_role="act"}
    {assign var="return_url" value="checkout.checkout"|fn_url}
    <script>
        (function(_, $) {
            $.ceEvent('on', 'ce.commoninit', function() {
                $('#amazon_logout').on('click', function() {
                    amazon.Login.logout();
                    location.href='{$return_url}';
                });
            });
        }(Tygh, Tygh.$));
    </script>
{else}
    {include file="addons/amazon_checkout/buttons/pay_with_amazon_button.tpl"}
{/if}
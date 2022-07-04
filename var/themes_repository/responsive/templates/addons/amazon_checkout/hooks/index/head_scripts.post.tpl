{* head_scripts.post.tpl by tommy from cs-cart.jp 2016 *}

{if $addons.amazon_checkout.client_id}
    <script type='text/javascript'>
        window.onAmazonLoginReady = function () {
            amazon.Login.setClientId('{$addons.amazon_checkout.client_id|escape:"javascript"}');
            amazon.Login.setUseCookie(true);
        };
    </script>
    {include file="addons/amazon_checkout/components/region_scripts/`$addons.amazon_checkout.region`.tpl"}
    {assign var="amzn_return_current_url" value=$config.current_url|escape:url}
    <script>
        (function(_, $) {
            $.ceEvent('on', 'ce.commoninit', function() {
                $('#jp-btn-signout').on('click', function() {
                    amazon.Login.logout();
                    location.href='{$return_current_url}';
                });
            });
        }(Tygh, Tygh.$));
    </script>
    {if $amazon_force_logout && $amazon_force_logout == 'Y'}
        <script>
            (function(_, $) {
                amazon.Login.logout();
            }(Tygh, Tygh.$));
        </script>
    {/if}
{/if}
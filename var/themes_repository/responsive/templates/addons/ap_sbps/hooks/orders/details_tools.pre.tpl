{if $addons.subscription_payment_jp && $addons.subscription_payment_jp.status == 'A'}
    {if $order_info.is_subscription == '1'}
        <p class="rb-title">{__('subscription_payment')}</p>
    {/if}
{/if}
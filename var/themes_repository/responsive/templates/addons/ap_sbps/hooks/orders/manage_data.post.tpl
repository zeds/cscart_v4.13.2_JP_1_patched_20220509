{if $addons.subscription_payment_jp && $addons.subscription_payment_jp.status == 'A'}
    <td>
        {if $o.is_subscription == '1'}
            {__('subscription_payment')}<br />{__("sbps_rb_status_`$o.rb_status`")}
        {/if}
    </td>
{/if}
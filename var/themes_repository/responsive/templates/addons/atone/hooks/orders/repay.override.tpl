&nbsp
{if $repays}
	{assign var="p_data" value=$order_info.payment_id|fn_get_payment_method_data}
    {if !$p_data.processor_id|in_array:$repays && $settings.Checkout.repay == "Y" && $payment_methods}
        {include file="views/orders/components/order_repay.tpl"}
	{/if}
{else}
	{if $settings.Checkout.repay == "Y" && $payment_methods}
    	{include file="views/orders/components/order_repay.tpl"}
    {/if}
{/if}
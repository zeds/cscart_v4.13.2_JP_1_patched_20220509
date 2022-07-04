{* $Id: order_confirmation.post.tpl by tommy from cs-cart.jp 2013 *}

{if $digital_check_payment_info }
	{$digital_check_payment_info|replace:"\n":"<br />"|default:"-" nofilter}
{/if}

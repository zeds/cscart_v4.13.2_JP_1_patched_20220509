{if $details_page && $addons.amazon_checkout.add_to_cart == "Y"}
    {include file="addons/amazon_checkout/buttons/pay_with_amazon_button.tpl" style="inline" obj_id=$obj_id obj_prefix=$obj_prefix}
{/if}
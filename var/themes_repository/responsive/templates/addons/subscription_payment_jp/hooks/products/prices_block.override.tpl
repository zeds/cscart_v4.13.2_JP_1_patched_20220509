{* $Id: prices_block.override.tpl by tommy from cs-cart.jp 2015 *}

{if $product.product_id|fn_subpay_jp_is_subscription_products_by_id}
    {assign var="subpay_info" value=$product.product_id|fn_subpay_jp_get_subscription_product_info}
    {if $product.price|floatval || $product.zero_price_action == "P" || ($hide_add_to_cart_button == "Y" && $product.zero_price_action == "A")}
        {if $show_price_values && $show_clean_price && $settings.Appearance.show_prices_taxed_clean == "Y" && $product.taxed_price}
            {if $product.clean_price != $product.taxed_price && $product.included_tax}
                {assign var="jp_price_for_display" value=$product.taxed_price}
            {else}
                {assign var="jp_price_for_display" value=$product.price}
            {/if}
        {else}
            {assign var="jp_price_for_display" value=$product.price}
        {/if}

        <span class="ty-price{if !$product.price|floatval && !$product.zero_price_action} hidden{/if}" id="line_discounted_price_{$obj_prefix}{$obj_id}">{if $details_page}{/if}{if $subpay_info.price_prefix}<span class="jp-price_prefix">{$subpay_info.price_prefix}</span>{/if}{include file="common/price.tpl" value=$jp_price_for_display span_id="discounted_price_`$obj_prefix``$obj_id`" class="ty-price-num"}{if $subpay_info.price_suffix}<span class="jp-price_suffix">{$subpay_info.price_suffix}</span>{/if}</span>
        {if $details_page}{if $subpay_info.description}<span class="jp-subpay-product-remarks">{$subpay_info.description|unescape nofilter}</span>{/if}{/if}
    {elseif $product.zero_price_action == "A" && $show_add_to_cart}
        {assign var="base_currency" value=$currencies[$smarty.const.CART_PRIMARY_CURRENCY]}
        <span class="ty-price-curency"><span class="ty-price-curency__title">{__("enter_your_price")}:</span>
                    <div class="ty-price-curency-input">
                        {if $base_currency.after != "Y"}{$base_currency.symbol}{/if}
                        <input class="ty-price-curency__input" type="text" size="3" name="product_data[{$obj_id}][price]" value="" />
                    </div>
            {if $base_currency.after == "Y"}{$base_currency.symbol}{/if}</span>
    {elseif $product.zero_price_action == "R"}
        <span class="ty-no-price">{__("contact_us_for_price")}</span>
        {assign var="show_qty" value=false}
    {/if}
{/if}

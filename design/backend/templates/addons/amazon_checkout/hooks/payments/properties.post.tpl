{* properties.post.tpl by tommy from cs-cart.jp 2016 *}
<div class="control-group">
    <label class="control-label" for="elm_payment_is_amazon_payment_{$id}">{__("is_amazon_payment")}:</label>
    <div class="controls">
        <input id="elm_payment_is_amazon_payment_{$id}" type="checkbox" name="payment_data[is_amazon_payment]" value="Y" {if $payment.is_amazon_payment == "Y"}checked{/if}>
    </div>
</div>
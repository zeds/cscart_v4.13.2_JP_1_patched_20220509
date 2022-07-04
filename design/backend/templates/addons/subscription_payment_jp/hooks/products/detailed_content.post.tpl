{* $Id: detailed_content.post.tpl by tommy from cs-cart.jp 2013 *}

{include file="common/subheader.tpl" title=__("jp_subpay_subscription_payment") target="#acc_jp_subpay_subscription_payment"}

<div id="acc_jp_subpay_subscription_payment" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="subpay_jp_subscription_product">{__("jp_subpay_subscription_product")}</label>
        <div class="controls">
            <input type="hidden" name="product_data[subpay_jp_subscription_product]" value="N" />
            <input type="checkbox" id="subpay_jp_subscription_product" name="product_data[subpay_jp_subscription_product]" value="Y" {if $subpay_jp_product.is_subscription == "Y"}checked="checked"{/if}" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="subpay_jp_price_prefix">{__("jp_subpay_price_prefix")}</label>
        <div class="controls">
            <input type="text" id="subpay_jp_price_prefix" name="product_data[subpay_jp_price_prefix]" size="20" maxlength="255" value="{$subpay_jp_product.price_prefix}" class="input-text" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="subpay_jp_price_suffix">{__("jp_subpay_price_suffix")}</label>
        <div class="controls">
            <input type="text" id="subpay_jp_price_suffix" name="product_data[subpay_jp_price_suffix]" size="20" maxlength="255" value="{$subpay_jp_product.price_suffix}" class="input-text" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="subpay_jp_description">{__("description")}</label>
        <div class="controls">
            <textarea id="subpay_jp_description" name="product_data[subpay_jp_description]"  cols="40" rows="4" class="cm-wysiwyg input-textarea">{$subpay_jp_product.description}</textarea>
        </div>
    </div>
</div>

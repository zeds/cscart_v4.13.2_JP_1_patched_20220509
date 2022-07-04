{* amazon_checkout.tpl by tommy from cs-cart.jp 2016 *}

<div id="amazon_checkout_{$block.block_id}">
<form name="checkout" id="form_amazon_form" action="{""|fn_url}" method="post">
    <div class="ty-float-left ty-amazon-checkout" id="amazon_chekout">
        <div class="amazon-widgets">
            {include file="addons/amazon_checkout/components/amazon_widgets.tpl"}
        </div>
        <div class="amazon-button-login hidden">
            {include file="addons/amazon_checkout/buttons/pay_with_amazon_button.tpl" no_init=true}
        </div>
    </div>
    {assign var="allow_place_order" value=$cart|fn_allow_place_order:$auth}

    <div class="ty-float-right ty-amazon-checkout">
        {include file="common/subheader.tpl" title=__("shipping_methods")}
        <div class="checkout__block" id="checkout_shipping_methods">
        {hook name="checkout:select_shipping"}
            {if !$cart.shipping_failed}
                {include file="views/checkout/components/shipping_rates.tpl" no_form=true display="radio"}
            {else}
                <p class="ty-error-text">{__("text_no_shipping_methods")}</p>
            {/if}
        {/hook}
        <!--checkout_shipping_methods--></div>
        <div class="checkout__block" id="checkout_payment_methods">
            {if !$allow_place_order}
                {include file="views/checkout/components/final_section.tpl" is_payment_step=true}
            {else}
                {if !$auth.user_id}
                <div class="ty-control-group">
                    <label>
                        <input type="checkbox" name="register" id="register" value="Y" {if $addons.amazon_checkout.create_account == "Y"}checked="checked"{/if}>
                        {__("amazon_register")}
                    </label>
                </div>
                {/if}
                {include file="views/checkout/components/customer_notes.tpl"}
                <div class="clearfix hidden">
                    {include file="views/checkout/components/payments/payments_list.tpl" payments=$payment_methods|reset payment_id=$cart.payment_id}
                </div>
                <div id="amazon_place_order_section" class="ty-checkout-buttons ty-checkout-buttons__submit-order ty-right">
                    {include file="buttons/button.tpl" but_name="dispatch[checkout.place_order.amazon_checkout]" but_id="amazon_place_order" but_text=__("place_order") but_role="submit" but_meta="cm-checkout-place-order ty-btn__primary ty-btn__big ty-btn"}
                </div>
            {/if}
            
        <!--checkout_payment_methods--></div>
    </div>
    
    <div class="clearfix"></div>
</form>


<!--amazon_checkout_{$block.block_id}--></div>
<script>
    (function(_, $) {
        _.tr({
            'sign_in_to_amazon': '{__("sign_in_to_amazon")|escape:"javascript"}'
        });
    }(Tygh, Tygh.$));
    $(document).ready(function(){
        verifyLoggedIn();
    });
    function verifyLoggedIn() {
        var options = {
            scope: "profile postal_code payments:widget payments:shipping_address",
            popup: true,
            interactive: 'never'
        };
        authRequest = amazon.Login.authorize (options, function(response) {
            if ( response.error ) {
                $.ceNotification('show', {
                    type: 'W',
                    title: _.tr('warning'),
                    message: _.tr('sign_in_to_amazon'),
                });
                fn_show_login_button();
                return false;
            } else {
                $.toggleStatusBox('show', {
                    statusContent: '<span class="ty-ajax-loading-box-with__text-wrapper">' + '{__("amazon_please_wait")}' + '</span>',
                    statusClass: 'ty-ajax-loading-box_text_block',
                });
                amazon.Login.retrieveProfile(function (response) {
                    Tygh.$.ceAjax('request', fn_url("amazon_checkout.check_email"), {
                        method: 'post',
                        result_ids: 'checkout*,cart_status*,cart_items,payment-methods,account_info*',
                        full_render: true,
                        data: {
                            email: response.profile.PrimaryEmail,
                        }
                    });
                });
            }
        });
    }
    function fn_show_login_button() {
        $('.amazon-widgets').addClass('hidden');
        $('.amazon-button-login').removeClass('hidden');
        $('#amazon_place_order_section').addClass('hidden');
    }
</script>


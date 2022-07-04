<div id="sq_ccbox">
    <div class="ty-control-group">
        <label class="ty-control-group__title cm-required">{__("addons.sd_square_payment.card_number")}:</label>
        <div id="sq_card_number"></div>
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title cm-required">{__("addons.sd_square_payment.cvv")}:</label>
        <div id="sq_cvv"></div>
    </div>
    <div class="ty-control-group">
        <label class="ty-control-group__title cm-required">{__("addons.sd_square_payment.expiration_date")}:</label>
        <div id="sq_expiration_date"></div>
    </div>
    {if $addons.sd_square_payment.show_postal_code == "Y"}
        <div class="ty-control-group">
            <label class="ty-control-group__title cm-required">{__("addons.sd_square_payment.postal_code")}:</label>
            <div id="sq_postal_code"></div>
        </div>
    {/if}
    <div class="ty-control-group">
        <label class="ty-control-group__title cm-required" for="cardholder_name">{__("addons.sd_square_payment.cardholder")}:</label>
        <input type="text" name="payment_info[cardholder_name]" id="cardholder_name" class="sq-input">
    </div>
    <input type="hidden" id="card_nonce" name="payment_info[card_nonce]">
</div>
{include file="addons/sd_square_payment/components/square_elements_script.tpl"}

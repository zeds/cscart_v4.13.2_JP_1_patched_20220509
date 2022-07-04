<div class="control-group">
    <label class="control-label" for="elm_square_only_auth">{__('addons.sd_square_payment.only_auth')}</label>
    <div class="controls">
        <input type="hidden" name="payment_data[processor_params][only_auth]" value="N">
        <input type="checkbox" name="payment_data[processor_params][only_auth]" id="elm_square_only_auth" value="Y" {if $processor_params.only_auth == "Y"}checked="checked"{/if}>
    </div>
</div>

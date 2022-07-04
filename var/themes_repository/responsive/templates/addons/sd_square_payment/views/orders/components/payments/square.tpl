<script>
//<![CDATA[
(function(_, $) {
    $(_.doc).on('click', '.square-cm-select-credit-card', function() {
        var self = $(this);
        if (self.val() == 0) {
            $('#square_new_cc').show();
            $('#square_new_cc :input').prop('disabled', false);
        } else {
            $('#square_new_cc').hide();
            $('#square_new_cc :input').prop('disabled', true);
        }
    });
}(Tygh, Tygh.$));
//]]>
</script>

<div class="ty-square-payment-tpl">
    {if $customer_cards}
        <div class="ty-square-payment-list">
            {foreach from=$customer_cards item="card" name="credit_cards"}
                <label>
                    <input id="square_card_{$card.id}" class="radio valign square-cm-select-credit-card" type="radio" name="payment_info[card_id]" value="{$card.id}" {if $smarty.foreach.credit_cards.first}checked="checked"{/if} />
                    <span>{$card.card_brand}&nbsp;<em>....</em>{$card.last_4}</span>
                    <span>{$card.exp_month}/{$card.exp_year}</span>
                </label>
            {/foreach}
            <label>
                <input id="square_card_new" class="radio valign square-cm-select-credit-card" type="radio" name="payment_info[card_id]" value="" />
                <span>{__('addons.sd_square_payment.use_new_credit_card')}</span>
            </label>
        </div>
    {/if}

    <div id="square_new_cc" class="{if $customer_cards}hidden{/if}">
        {include file="addons/sd_square_payment/components/square_elements.tpl" form_name="payments_form_`$tab_id`" button_id="place_order_`$tab_id`"}
        {if $auth.user_id}
            <div class="ty-square-notice">
                <label>{__('addons.sd_square_payment.attention')}</label>
                <span>{__('addons.sd_square_payment.card_will_be_saved')}</span>
            </div>
        {/if}
    </div>

</div>

{if $customer_cards}
    <script class="cm-ajax-force">
    //<![CDATA[
        Tygh.$(document).ready(function() {
            $('#square_new_cc :input').prop('disabled', true);
        });
    //]]>
    </script>
{/if}
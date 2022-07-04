{if $customer_cards}
    <div class="ty-square-cards">
        {foreach from=$customer_cards item=c}
            <div class="ty-square-card__item">
                {$c.card_brand} **** **** **** {$c.last_4} {$c.exp_month}/{$c.exp_year}
                <a href="{"square_cards.delete&card_id=`$c.id`"|fn_url}" class="cm-post cm-confirm">{__("delete")}</a>
            </div>
        {/foreach}
    </div>
{else}
    <p class="ty-no-items">{__("addons.sd_square_payment.no_saved_card")}</p>
{/if}
{*
{include file="common/subheader.tpl" title=__("addons.sd_square_payment.add_new_card")}
<form name="card_form" action="{""|fn_url}" method="post">
    <div class="square-payment-tpl">
        {include file="addons/sd_square_payment/components/square_elements.tpl" form_name="card_form" button_id="save_card_but"}
        {include file="buttons/save.tpl" but_name="dispatch[square_cards.add]" but_meta="ty-btn__secondary" but_id="save_card_but"}
    </div>
</form>
{capture name="mainbox_title"}{__("addons.sd_square_payment.saved_cards")}{/capture}
*}
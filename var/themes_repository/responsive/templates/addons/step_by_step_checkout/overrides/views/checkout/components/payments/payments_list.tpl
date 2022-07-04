{* Modified by tommy from cs-cart.jp 2017 *}

<div class="ty-other-pay clearfix">
    <ul class="ty-payments-list">
        {hook name="checkout:payment_method"}
            {foreach from=$payments item="payment"}
                {* Modified by tommy from cs-cart.jp 2017 BOF *}
                <div class="jp-paym-method">
                {* Modified by tommy from cs-cart.jp 2017 EOF *}
                {if $payment_id == $payment.payment_id}
                    {$instructions = $payment.instructions}
                {/if}

                <li class="ty-payments-list__item">
                    {* Modified by tommy from cs-cart.jp 2017 BOF *}
                    <input id="payment_{$payment.payment_id}" class="ty-payments-list__checkbox cm-select-payment{if $payment|fn_lcjp_check_payment}_submit{/if}" type="radio" name="payment_id" value="{$payment.payment_id}" data-ca-url="{$url}" data-ca-result-ids="{$result_ids}" {if $payment_id == $payment.payment_id}checked="checked"{/if} {if $payment.disabled}disabled{/if} />
                    {* Modified by tommy from cs-cart.jp 2017 EOF *}

                    <div class="ty-payments-list__item-group">
                        <label for="payment_{$payment.payment_id}" class="ty-payments-list__item-title">
                            {if $payment.image}
                                <div class="ty-payments-list__image">
                                {include file="common/image.tpl" obj_id=$payment.payment_id images=$payment.image class="ty-payments-list__image"}
                                </div>
                            {/if}

                            {$payment.payment}
                        </label>
                        <div class="ty-payments-list__description">
                            {$payment.description}
                        </div>
                    </div>
                </li>

                {if $payment_id == $payment.payment_id}
                    {if $payment.template && $payment.template != "cc_outside.tpl"}
                        <div>
                            {include file=$payment.template}
                        </div>
                    {/if}
                {/if}
                {* Modified by tommy from cs-cart.jp 2017 BOF *}
                </div>
                {* Modified by tommy from cs-cart.jp 2017 EOF *}
            {/foreach}
        {/hook}
    </ul>
    <div class="ty-payments-list__instruction">
        {$instructions nofilter}
    </div>
</div>

{* Modified by tommy from cs-cart.jp 2016 *}

{if $completed_steps.step_two}
    {assign var="profile_fields" value="I"|fn_get_profile_fields}
    {assign var="jp_company_country" value=$settings.Company.company_country|fn_get_country_name}

    {if $profile_fields.B}
        <ul id="tygh_billing_adress" class="shipping-adress clearfix">
            {foreach from=$profile_fields.B item="field"}
                {assign var="value" value=$cart.user_data|fn_get_profile_field_value:$field}
                {if $value && $field.class != 'first-name-kana' && $field.class != 'last-name-kana'}
                    {if $field.class != 'billing-country' || $value != $jp_company_country}
                        <li class="{$field.field_name|replace:"_":"-"}">{$value}{if $field.class == 'billing-last-name'} {__("dear")}{/if}</li>
                    {/if}
                {/if}
            {/foreach}
        </ul>

        <hr />
    {/if}

    {if $profile_fields.S}
        <h4>{__("shipping_address")}:</h4>
        <ul id="tygh_shipping_adress" class="shipping-adress clearfix">
            {foreach from=$profile_fields.S item="field"}
                {assign var="value" value=$cart.user_data|fn_get_profile_field_value:$field}
                {if $value && $field.class != 'first-name-kana' && $field.class != 'last-name-kana'}
                    {if $field.class != 'shipping-country' || $value != $jp_company_country}
                        <li class="{$field.field_name|replace:"_":"-"}">{$value}{if $field.class == 'shipping-last-name'} {__("dear")}{/if}</li>
                    {/if}
                {/if}
            {/foreach}
        </ul>
        <hr />
    {/if}

    {if !$cart.shipping_failed && !empty($cart.chosen_shipping) && $cart.shipping_required}
        <h4>{__("shipping_method")}:</h4>
        <ul id="tygh_shipping_method">
            {foreach from=$cart.chosen_shipping key="group_key" item="shipping_id"}
                <li>{$product_groups[$group_key].shippings[$shipping_id].shipping}</li>
            {/foreach}
        </ul>
    {/if}

{/if}
{assign var="block_wrap" value="checkout_order_info_`$block.snapping_id`_wrap" scope="parent"}

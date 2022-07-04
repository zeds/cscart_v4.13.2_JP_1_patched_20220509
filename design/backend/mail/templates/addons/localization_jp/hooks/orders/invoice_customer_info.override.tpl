{* $Id: invoice_customer_info.override.tpl by tommy from cs-cart.jp 2013 *}

		{if $smarty.const.CART_LANGUAGE == 'ja'}
            {if !$profile_fields}
                {assign var="profile_fields" value='I'|fn_get_profile_fields}
            {/if}
            {if $profile_fields}
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding: 32px 0px 24px 0px;">
                    <tr valign="top">
                        {if $profile_fields.C}
                            {assign var="profields_c" value=$profile_fields.C|fn_fields_from_multi_level:"field_name":"field_id"}
                            <td width="33%" style="font-size: 12px; font-family: Arial;">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("customer")}:</h3>
                                <p style="margin: 2px 0px 3px 0px;">{if $profields_c.firstname}{$order_info.firstname}&nbsp;{/if}{if $profields_c.lastname}{$order_info.lastname}{/if} {__("dear")}</p>
                                {if $profields_c.email}<p style="margin: 2px 0px 3px 0px;"><a href="mailto:{$order_info.email|escape:url}">{$order_info.email}</a></p>{/if}
                                {if $profields_c.phone}<p style="margin: 2px 0px 3px 0px;"><span style="text-transform: uppercase;">{__("phone")}:</span>&nbsp;{$order_info.phone}</p>{/if}
                                {if $profields_c.fax && $order_info.fax}<p style="margin: 2px 0px 3px 0px;"><span style="text-transform: uppercase;">{__("fax")}:</span>&nbsp;{$order_info.fax}</p>{/if}
                                {if $profields_c.company && $order_info.company}<p style="margin: 2px 0px 3px 0px;"><span style="text-transform: uppercase;">{__("company")}:</span>&nbsp;{$order_info.company}</p>{/if}
                                {if $profields_c.url && $order_info.url}<p style="margin: 2px 0px 3px 0px;"><span style="text-transform: uppercase;">{__("url")}:</span>&nbsp;{$order_info.url}</p>{/if}
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.C}
                            </td>
                        {/if}
                        {if $profile_fields.B}
                            {assign var="profields_b" value=$profile_fields.B|fn_fields_from_multi_level:"field_name":"field_id"}
                            <td width="34%" style="font-size: 12px; font-family: Arial; {if $profile_fields.S}padding-right: 10px;{/if} {if $profile_fields.C}padding-left: 10px;{/if}">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("bill_to")}:</h3>
                                {if $order_info.b_firstname && $profields_b.b_firstname || $order_info.b_lastname && $profields_b.b_lastname}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_b.b_firstname}{$order_info.b_firstname} {/if}{if $profields_b.b_lastname}{$order_info.b_lastname}{/if} {__("dear")}
                                    </p>
                                {/if}
                                {if $order_info.b_city && $profields_b.b_city || $order_info.b_state_descr && $profields_b.b_state || $order_info.b_zipcode && $profields_b.b_zipcode}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_b.b_zipcode}{$order_info.b_zipcode}{/if} {if $profields_b.b_state}{$order_info.b_state_descr}{/if}{if $profields_b.b_city}{$order_info.b_city}{/if}
                                    </p>
                                {/if}
                                {if $order_info.b_address && $profields_b.b_address || $order_info.b_address_2 && $profields_b.b_address_2}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_b.b_address}{$order_info.b_address} {/if}{if $profields_b.b_address_2}<br />{$order_info.b_address_2}{/if}
                                    </p>
                                {/if}
                                {if $order_info.b_country_descr && $profields_b.b_country && $company_data.company_country_descr != $order_info.b_country_descr}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.b_country_descr}
                                    </p>
                                {/if}
                                {if $order_info.b_phone && $profields_b.b_phone}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_b.b_phone}{$order_info.b_phone} {/if}
                                    </p>
                                {/if}
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.B}
                            </td>
                        {/if}
                        {if $profile_fields.S}
                            {assign var="profields_s" value=$profile_fields.S|fn_fields_from_multi_level:"field_name":"field_id"}
                            <td width="33%" style="font-size: 12px; font-family: Arial;">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("ship_to")}:</h3>
                                {if $order_info.s_firstname && $profields_s.s_firstname || $order_info.s_lastname && $profields_s.s_lastname}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_s.s_firstname}{$order_info.s_firstname} {/if}{if $profields_s.s_lastname}{$order_info.s_lastname}{/if} {__("dear")}
                                    </p>
                                {/if}
                                {if $order_info.s_city && $profields_s.s_city || $order_info.s_state_descr && $profields_s.s_state || $order_info.s_zipcode && $profields_s.s_zipcode}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_s.s_zipcode}{$order_info.s_zipcode}{/if} {if $profields_s.s_state}{$order_info.s_state_descr}{/if}{if $profields_s.s_city}{$order_info.s_city}{/if}
                                    </p>
                                {/if}
                                {if $order_info.s_address && $profields_s.s_address || $order_info.s_address_2 && $profields_s.s_address_2}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_s.s_address}{$order_info.s_address} {/if}{if $profields_s.s_address_2}<br />{$order_info.s_address_2}{/if}
                                    </p>
                                {/if}
                                {if $order_info.s_country_descr && $profields_s.s_country && $company_data.company_country_descr != $order_info.s_country_descr}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.s_country_descr}
                                    </p>
                                {/if}
                                {if $order_info.s_phone && $profields_s.s_phone}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $profields_s.s_phone}{$order_info.s_phone} {/if}
                                    </p>
                                {/if}
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.S}
                            </td>
                        {/if}
                    </tr>
                </table>
            {/if}
		{/if}

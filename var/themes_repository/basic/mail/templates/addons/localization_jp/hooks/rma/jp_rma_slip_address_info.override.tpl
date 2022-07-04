{* $Id: jp_rma_slip_address_info.tpl by tommy from cs-cart.jp 2016 *}

			{if $smarty.const.CART_LANGUAGE == 'ja'}
                <table cellpadding="0" cellspacing="0" border="0" width="100%" style="direction: {$language_direction}; padding: 32px 0px 24px 0px;">
                    <tr valign="top">
                        {if $profile_fields.C}
                            <td width="33%">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("customer")}:</h3>
                                <p style="margin: 2px 0px 3px 0px;">{$order_info.firstname}&nbsp;{$order_info.lastname} {__("dear")}</p>
                                <p style="margin: 2px 0px 3px 0px;"><a href="mailto:{$order_info.email|escape:url}">{$order_info.email}</a></p>
                                <p style="margin: 2px 0px 3px 0px;"><span style="text-transform: uppercase;">{__("phone")}:</span>&nbsp;{$order_info.phone}</p>
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.C}
                            </td>
                        {/if}
                        {if $profile_fields.B}
                            <td width="34%" style="{if $profile_fields.S}padding-right: 10px;{/if} {if $profile_fields.C}padding-left: 10px;{/if}">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("bill_to")}:</h3>
                                {if $order_info.b_city || $order_info.b_state_descr || $order_info.b_zipcode}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $order_info.b_zipcode}〒{$order_info.b_zipcode} {/if}{$order_info.b_state_descr}{$order_info.b_city}
                                    </p>
                                {/if}
                                {if $order_info.b_address || $order_info.b_address_2}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.b_address} {$order_info.b_address_2}
                                    </p>
                                {/if}
                                {if $order_info.b_country_descr && $company_data.company_country_descr != $order_info.b_country_descr}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.b_country_descr}
                                    </p>
                                {/if}
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.B exclude_kana=true}
                                {if $order_info.b_firstname || $order_info.b_lastname}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.b_firstname} {$order_info.b_lastname} {__("dear")}
                                    </p>
                                {/if}
                            </td>
                        {/if}
                        {if $profile_fields.S}
                            <td width="33%">
                                <h3 style="font: bold 17px Tahoma; padding: 0px 0px 3px 1px; margin: 0px;">{__("ship_to")}:</h3>
                                {if $order_info.s_city || $order_info.s_state_descr || $order_info.s_zipcode}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {if $order_info.s_zipcode}〒{$order_info.s_zipcode} {/if}{$order_info.s_state_descr}{$order_info.s_city}
                                    </p>
                                {/if}
                                {if $order_info.s_address || $order_info.s_address_2}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.s_address} {$order_info.s_address_2}
                                    </p>
                                {/if}
                                {if $order_info.s_country_descr && $company_data.company_country_descr != $order_info.s_country_descr}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.s_country_descr}
                                    </p>
                                {/if}
                                {include file="profiles/profiles_extra_fields.tpl" fields=$profile_fields.S exclude_kana =true}
                                {if $order_info.s_firstname || $order_info.s_lastname}
                                    <p style="margin: 2px 0px 3px 0px;">
                                        {$order_info.s_firstname} {$order_info.s_lastname} {__("dear")}
                                    </p>
                                {/if}
                            </td>
                        {/if}
                    </tr>
                </table>
			{/if}

{* $Id: invoice_company_info.tpl by tommy from cs-cart.jp 2013 *}

			{if $smarty.const.CART_LANGUAGE == 'ja'}
                <td style="width: 50%; padding: 14px 0px 0px 2px; font-size: 12px; font-family: Arial;">
                    <h2 style="font: bold 12px Arial; margin: 0px 0px 3px 0px;">{$company_data.company_name}</h2>
                    {$company_data.company_zipcode} {$company_data.company_state_descr}{$company_data.company_city}<br />
                    {$company_data.company_address}<br />
                    {if $order_info.b_country_descr && $company_data.company_country_descr != $order_info.b_country_descr}
                        {$company_data.company_country_descr}
                    {/if}
                    <table cellpadding="0" cellspacing="0" border="0">
                        {if $company_data.company_phone}
                            <tr valign="top">
                                <td style="font-size: 12px; font-family: verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px;    white-space: nowrap;">{__("phone1_label")}:</td>
                                <td width="100%" style="font-size: 12px; font-family: Arial;">{$company_data.company_phone}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_phone_2}
                            <tr valign="top">
                                <td style="font-size: 12px; font-family: verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("phone2_label")}:</td>
                                <td width="100%" style="font-size: 12px; font-family: Arial;">{$company_data.company_phone_2}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_fax}
                            <tr valign="top">
                                <td style="font-size: 12px; font-family: verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("fax")}:</td>
                                <td width="100%" style="font-size: 12px; font-family: Arial;">{$company_data.company_fax}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_website}
                            <tr valign="top">
                                <td style="font-size: 12px; font-family: verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("web_site")}:</td>
                                <td width="100%" style="font-size: 12px; font-family: Arial;">{$company_data.company_website}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_orders_department}
                            <tr valign="top">
                                <td style="font-size: 12px; font-family: verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("email")}:</td>
                                <td width="100%" style="font-size: 12px; font-family: Arial;"><a href="mailto:{$company_data.company_orders_department}">{$company_data.company_orders_department|replace:",":"<br>"|replace:" ":""}</a></td>
                            </tr>
                        {/if}
                    </table>
                </td>
			{/if}

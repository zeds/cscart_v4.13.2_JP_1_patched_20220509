{* $Id: jp_rma_slip_company_info.tpl by tommy from cs-cart.jp 2016 *}

				{if $smarty.const.CART_LANGUAGE == 'ja'}
                    <h2 style="font: bold 12px Arial; margin: 0px 0px 3px 0px;">{$company_data.company_name}</h2>
                    {if $company_data.company_zipcode}〒{$company_data.company_zipcode}{/if} {$company_data.company_state_descr}{$company_data.company_city}<br />
                    {$company_data.company_address}
                    {if $order_info.s_country_descr && $company_data.company_country_descr != $order_info.s_country_descr}
                    <br />
                    {$company_data.company_country_descr}
                    {/if}
                    <table cellpadding="0" cellspacing="0" border="0" style="direction: {$language_direction};">
                        {if $company_data.company_phone}
                            <tr>
                                <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("phone1_label")}:</td>
                                <td width="100%">{$company_data.company_phone}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_phone_2}
                            <tr>
                                <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("phone2_label")}:</td>
                                <td width="100%">{$company_data.company_phone_2}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_fax}
                            <tr>
                                <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("fax")}:</td>
                                <td width="100%">{$company_data.company_fax}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_website}
                            <tr>
                                <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("web_site")}:</td>
                                <td width="100%">{$company_data.company_website}</td>
                            </tr>
                        {/if}
                        {if $company_data.company_orders_department}
                            <tr>
                                <td style="font: 12px verdana, helvetica, arial, sans-serif; text-transform: uppercase; color: #000000; padding-right: 10px; white-space: nowrap;">{__("email")}:</td>
                                <td width="100%">{$company_data.company_orders_department}</td>
                            </tr>
                        {/if}
                    </table>
				{/if}

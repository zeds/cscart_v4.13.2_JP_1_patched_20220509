{* $Id: jp_invoice_hide_tax.override.tpl by ari from cs-cart.jp 2016 *}

            <table width="100%" cellpadding="0" cellspacing="1" style="direction: {$language_direction}; background-color: #dddddd;">
            <tr>
                <th width="70%" style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("product")}</th>
                <th style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("quantity")}</th>
                <th style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("unit_price")}</th>
                {if $order_info.use_discount}
                    <th style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("discount")}</th>
                {/if}
                {*
                {if $order_info.taxes && $settings.General.tax_calculation != "subtotal"}
                    <th style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("tax")}</th>
                {/if}
                *}
                <th style="background-color: #eeeeee; padding: 6px 10px; white-space: nowrap; font-size: 12px; font-family: Arial;">{__("subtotal")}</th>
            </tr>

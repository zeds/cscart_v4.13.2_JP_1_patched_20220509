{* $Id: jp_ps_company_info.tpl by tommy from cs-cart.jp 2013 *}

			{if $smarty.const.CART_LANGUAGE == 'ja'}
                <h2 style="font: bold 12px Arial; margin: 0px 0px 3px 0px;">{$company_placement_info.company_name}</h2>
                {if $company_placement_info.company_zipcode}〒{/if}{$company_placement_info.company_zipcode} {$company_placement_info.company_state_descr}{$company_placement_info.company_city}<br />
                {$company_placement_info.company_address}<br />
                {if $order_info.s_country_descr && $company_placement_info.company_country_descr != $order_info.s_country_descr}{$company_placement_info.company_country_descr}{/if}
			{/if}

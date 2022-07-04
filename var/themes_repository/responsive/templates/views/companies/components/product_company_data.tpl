{* Modified for Mall edition by tommy from cs-cart.jp 2022 BOF *}
{* ストア名は商品名の上に表示する *}
{if "MULTIVENDOR"|fn_allowed_for && ($company_name || $company_id) && $settings.Vendors.display_vendor == "Y"}
    <div class="ty-control-group{if !$capture_options_vs_qty} product-list-field{/if}">
        {hook name="companies:product_company_data"}
            {if fn_allowed_for("MULTIVENDOR:PLUS")}
               {if $runtime.controller == "checkout" || $runtime.controller == "separate_checkout"}
                   <label class="ty-control-group__label">{__("vendor")} : </label>
                   <span class="ty-control-group__item ty-company-name"><a href="{"companies.products?company_id=`$company_id`"|fn_url}">{if $company_name}{$company_name}{else}{$company_id|fn_get_company_name}{/if}</a></span>
               {/if}
            {else}
                <label class="ty-control-group__label">{__("vendor")}:</label>
                <span class="ty-control-group__item ty-company-name"><a href="{"companies.products?company_id=`$company_id`"|fn_url}">{if $company_name}{$company_name}{else}{$company_id|fn_get_company_name}{/if}</a></span>
            {/if}
        {/hook}
    </div>
{/if}
{* Modified for Mall edition by tommy from cs-cart.jp 2022 EOF *}
{* Modified by takahashi from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
{else}
    {__("hello")},<br /><br />
{/if}

{__("text_company_status_disabled_to_pending", ["[company]" => $company_data.company_name])}
<br /><br />

{__("text_company_status_pending", ["[company]" => $company_data.company_name])}

<br /><br />

{if $reason}
{__("reason")}: {$reason}
<br /><br />
{/if}

{include file="common/letter_footer.tpl"}

{* Modified by takahashi from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
{else}
    {__("hello")},
    <br />
    <br />
{/if}
{__("vendor_payouts.new_payout_issued_text", ["[amount]" => $payment.amount, "[issuer]" => $payment.initiator])}.
{__("vendor_payouts.view_details")}: <a href="{$accounting_url}">{$accounting_url}</a>
{if $payment.comments}
    <br />
    <br />
    {__("vendor_payouts.payout_comments")}:
    <br />
    {$payment.comments}
{/if}

{include file="common/letter_footer.tpl"}
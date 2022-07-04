{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {$user_data.firstname}{__("dear")}<br /><br />
{else}
    {__("dear")} {$user_data.firstname},<br /><br />
{/if}

{__("we_would_like_to_inform")}: {if $reason.action == 'A'}{__("reward_points_subj_added_to", [$reason.amount])}{else}{__("reward_points_subj_subtracted_from", [$reason.amount])}{/if}<br />

{if $smarty.const.CART_LANGUAGE == "ja"}
    <br />
{/if}

<b>{__("reason")}:</b><br />
        {$reason.reason}

{if $smarty.const.CART_LANGUAGE == "ja"}
    <br /><br />
{/if}

{include file="common/letter_footer.tpl"}
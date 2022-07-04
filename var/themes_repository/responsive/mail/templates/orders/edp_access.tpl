{* Modified by tommy from cs-cart.jp 2017 *}

{include file="common/letter_header.tpl"}

{if $smarty.const.CART_LANGUAGE == "ja"}
    {$order_info.firstname}{__("dear")}<br /><br />
{else}
    {__("dear")} {$order_info.firstname},<br /><br />
{/if}

{__("edp_access_granted")}<br /><br />

<a href="{$order_files_list_url}"><b>{__("view_avail_files_for_order", ["[order_id]" => $order_info.order_id])}</b></a><br /><br />

{foreach from=$edp_data item="product"}
    {foreach from=$product.files item="file"}
    <a href="{$file.url}">{$file.file_name} ({$file.file_size|number_format:0:'':' '}&nbsp;{__("bytes")})</a><br /><br />
    {/foreach}
{/foreach}

{include file="common/letter_footer.tpl"}

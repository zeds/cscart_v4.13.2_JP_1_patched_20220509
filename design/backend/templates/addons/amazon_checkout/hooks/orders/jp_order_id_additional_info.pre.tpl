{* $Id: jp_order_id_additional_info.pre.tpl by tommy from cs-cart.jp 2016 *}
{* 注文一覧に AmazonリファレンスID を表示 *}
{if $o.lpa_auth_id}
    <small class="muted">{$o.lpa_auth_id}</small>
{/if}

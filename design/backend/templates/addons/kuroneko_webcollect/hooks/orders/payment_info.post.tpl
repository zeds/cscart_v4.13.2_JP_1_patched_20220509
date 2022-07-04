{* payment_info.post.tpl by tommy from cs-cart.jp 2016 *}
{if $wc_status_name}
    <div class="control-group">
        <div class="control-label">{__("jp_kuroneko_webcollect_wc_order_status")}</div>
        <div id="tygh_payment_info" class="controls">{$wc_status_name}</div>
    </div>
{/if}
{if $ab_order_status}
    <div class="control-group">
        <div class="control-label">{__("jp_kuroneko_webcollect_ab_order_status")}</div>
        <div id="tygh_payment_info" class="controls">{$ab_order_status}</div>
    </div>
    {if $ab_warning_info}
        <div class="control-group">
            <div class="control-label">{__("jp_kuroneko_webcollect_ab_warning_info")}</div>
            <div id="tygh_payment_info" class="controls">{$ab_warning_info}</div>
        </div>
    {/if}
{/if}
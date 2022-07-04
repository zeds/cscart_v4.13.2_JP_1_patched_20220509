{if $navigation.static.central.orders.items.ap_sbps_rp_manager.subitems}
    <div class="tabs clear">
        <ul class="nav nav-tabs">
            {foreach from=$navigation.static.central.orders.items.ap_sbps_rp_manager.subitems key=k item=v}
                <li class="{if $v.active}active{/if}">
                    <a href="{$v.href|fn_url}">{__($k)}</a>
                </li>
            {/foreach}
        </ul>
    </div>
{/if}
{if $navigation.static.central.orders.items.ap_sbps_manager.subitems}
    <div class="tabs clear">
        <ul class="nav nav-tabs">
            {foreach from=$navigation.static.central.orders.items.ap_sbps_manager.subitems key=k item=v}
                <li class="{if $v.active}active{/if}">
                    {if $incompleted_view}
                        <a href="{"`$v.href`&status=`$smarty.const.STATUS_INCOMPLETED_ORDER`"|fn_url}">{__($k)}</a>
                    {else}
                        <a href="{$v.href|fn_url}">{__($k)}</a>
                    {/if}
                </li>
            {/foreach}
        </ul>
    </div>
{/if}

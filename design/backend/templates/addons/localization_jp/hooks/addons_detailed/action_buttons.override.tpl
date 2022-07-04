<li>{btn type="list" method="POST" text=__("jp_addon_refresh") href="{$addon.refresh_url}"}</li>
{$line = "`$_addon`.confirmation_deleting"|fn_is_lang_var_exists}
{if $line}
    {$btn_delete_data["data-ca-confirm-text"] = __("`$_addon`.confirmation_deleting")}
{/if}
<li>{btn type="list" class="cm-confirm text-error" method="POST" text=__("uninstall") href="{$addon.delete_url}" data=$btn_delete_data}</li>
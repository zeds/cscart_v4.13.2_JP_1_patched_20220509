{* Modified by takahashi from cs-cart.jp 2019 *}

{if !"ULTIMATE"|fn_allowed_for}
{if $smartlink}
<div id="content_smartlink" class="hidden">
    <div class="control-group">
        <label class="control-label" for="elm_tenant_id">{__("jp_sln_tenant_code")}:</label>
        <div class="controls">
            <input class="input-small" type="tel" name="tenant_id" id="elm_tenant_id" value="{$tenant_id}" maxlength="4"/>
        </div>
    </div>
</div>
{/if}
{/if}

{* $Id: smbc_rb.tpl by tommy from cs-cart.jp 2013 *}

{if $addons.subscription_payment_jp.status == 'A'}
<p>{__("jp_smbc_rb_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_smbc_connections_settings") target="#smbc_rb_connection_settings"}
<div id="smbc_rb_connection_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("test_live_mode")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][mode]" id="mode">
                    <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                    <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
                </select>
            </div>
        </div>
    </fieldset>
</div>
{else}
<p>{__("jp_smbc_rb_activate_subscription_addon")}</p>
{/if}

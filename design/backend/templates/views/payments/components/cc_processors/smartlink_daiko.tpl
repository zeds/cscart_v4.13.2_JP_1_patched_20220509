{* $Id: smartlink_daiko.tpl by tommy from cs-cart.jp 2014 *}

<p>{__('jp_sln_daiko_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_sln_connections_settings') target="#text_sln_cc_connection_settings"}

<div id="text_sln_cc_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_sln_daiko_payment_settings') target="#text_sln_daiko_payment_settings"}

<div id="text_sln_cc_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="paylimit">{__('jp_sln_daiko_paylimit')}:</label>
        <div class="controls">
            {__('jp_sln_daiko_paylimit_prefix')}<input type="text" name="payment_data[processor_params][paylimit]" id="paylimit" value="{$processor_params.paylimit}" class="input-text-short" size="3" />{__('jp_sln_daiko_paylimit_suffix')}
        </div>
    </div>
</div>
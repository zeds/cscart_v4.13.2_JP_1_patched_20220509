{* $Id: smbc_cvs.tpl by tommy from cs-cart.jp 2013 *}

<p>{__("jp_smbc_cvs_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_smbc_connections_settings") target="#smbc_cvs_connection_settings"}
<div id="smbc_cvs_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_smbc_cvs_payment_settings") target="#smbc_cvs_payment_settings"}
<div id="smbc_cvs_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="cnvkind">{__("jp_smbc_cvs_cnvkind")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][cnvkind][0301]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cnvkind][0301]" id="cnvkind_0301" value="true" {if $processor_params.cnvkind['0301'] == "true"} checked="checked"{/if} /> {__("jp_cvs_se")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cnvkind][0302]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cnvkind][0302]" id="cnvkind_0302" value="true" {if $processor_params.cnvkind['0302'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ls")} / {__("jp_cvs_ms")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cnvkind][0303]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cnvkind][0303]" id="cnvkind_0303" value="true" {if $processor_params.cnvkind['0303'] == "true"} checked="checked"{/if} /> {__("jp_cvs_sm")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cnvkind][0304]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cnvkind][0304]" id="cnvkind_0304" value="true" {if $processor_params.cnvkind['0304'] == "true"} checked="checked"{/if} /> {__("jp_cvs_fm")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cnvkind][0305]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cnvkind][0305]" id="cnvkind_0305" value="true" {if $processor_params.cnvkind['0305'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ck")} / {__("jp_cvs_ts")}
            </div>
        </div>
    </fieldset>
</div>

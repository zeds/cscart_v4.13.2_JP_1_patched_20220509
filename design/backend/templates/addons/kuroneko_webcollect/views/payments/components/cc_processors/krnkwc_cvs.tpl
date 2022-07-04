{* $Id: krnkwc_cvs.tpl by tommy from cs-cart.jp 2016 *}

<p>{__("jp_kuroneko_webcollect_cvs_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_kuroneko_webcollect_connections_settings") target="#krnkwc_cvs_connection_settings"}
<div id="krnkwc_cvs_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_kuroneko_webcollect_cvs_payment_settings") target="#krnkwc_cvs_payment_settings"}
<div id="krnkwc_cvs_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="convenience">{__("jp_kuroneko_webcollect_cvs_type")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][cvs][se]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][se]" id="convenience_se" value="true" {if $processor_params.cvs['se'] == "true"} checked="checked"{/if} /> {__("jp_cvs_se")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs][fm]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][fm]" id="convenience_fm" value="true" {if $processor_params.cvs['fm'] == "true"} checked="checked"{/if} /> {__("jp_cvs_fm")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs][ls]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][ls]" id="convenience_ls" value="true" {if $processor_params.cvs['ls'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ls")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs][ck]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][ck]" id="convenience_ck" value="true" {if $processor_params.cvs['ck'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ck")}{__("jp_cvs_ts")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs][ms]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][ms]" id="convenience_ms" value="true" {if $processor_params.cvs['ms'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ms")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs][sm]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs][sm]" id="convenience_sm" value="true" {if $processor_params.cvs['sm'] == "true"} checked="checked"{/if} /> {__("jp_cvs_sm")}
            </div>
        </div>
    </fieldset>
</div>

{include file="common/subheader.tpl" title=__('jp_kuroneko_webcollect_wc_cron_setting') target="#krnkwc_cron_settings"}
<div id="krnkwc_cron_settings" class="in collapse">
    <div class="control-group">{'wc'|fn_krnkwc_get_cron_command}</div>
</div>
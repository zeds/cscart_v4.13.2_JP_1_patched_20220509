{* $Id: kessai_navi.tpl by tommy from cs-cart.jp 2015 *}

<p>{__("jp_knv_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_knv_connections_settings') target="#text_knv_connection_settings"}
<div id="text_knv_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="mode">{__("test_live_mode")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][mode]" id="mode">
                <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="url_production">{__("jp_knv_url_production")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][url_production]" id="url_production" value="{$processor_params.url_production}" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="url_test">{__("jp_knv_url_test")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][url_test]" id="url_test" value="{$processor_params.url_test}" />
        </div>
    </div>
</div>
<hr />
{include file="common/subheader.tpl" title=__('jp_knv_ccreg_settings') target="#text_knv_ccreg_settings"}
<div id="text_knv_ccreg_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="use_dbkey">{__("jp_knv_use_dbkey")}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][use_dbkey]" value="N" />
            <input type="checkbox" name="payment_data[processor_params][use_dbkey]" id="use_dbkey" value="Y" {if $processor_params.use_dbkey == "Y"} checked="checked"{/if} />
            <br />{__("jp_knv_use_dbkey_notice")}
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="url_ccreg_production">{__("jp_knv_url_ccreg_production")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][url_ccreg_production]" id="url_ccreg_production" value="{$processor_params.url_ccreg_production}" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="url_ccreg_test">{__("jp_knv_url_ccreg_test")}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][url_ccreg_test]" id="url_ccreg_test" value="{$processor_params.url_ccreg_test}" />
        </div>
    </div>
</div>
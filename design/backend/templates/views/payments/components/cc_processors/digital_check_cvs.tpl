{* $Id: digital_check_cvs.tpl by tommy from cs-cart.jp 2013 *}

<p>{__('jp_digital_check_cvs_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_digital_check_connections_settings') target="#text_dc_cvs_connection_settings"}

<div id="text_dc_cvs_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="shop_code">{__('jp_digital_check_ip')}:</label>
        <div class="controls">
            <input type="text" name="payment_data[processor_params][ip]" id="ip" value="{$processor_params.ip}" class="input-text" size="10" />
        </div>
    </div>
</div>

{include file="common/subheader.tpl" title=__('jp_digital_check_cvs_payment_settings') target="#text_dc_cvs_payment_settings"}

<div id="text_dc_cvs_payment_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="cnvkind">{__('jp_digital_check_cvs_cnvkind')}:</label>
        <div class="controls">
            <input type="hidden" name="payment_data[processor_params][cnvkind][1]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][cnvkind][1]" id="cnvkind_1" value="true" {if $processor_params.cnvkind.1 == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cvs_ls')} / {__('jp_cvs_sm')} / {__('jp_digital_check_cvs_ms')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][cnvkind][2]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][cnvkind][2]" id="cnvkind_2" value="true" {if $processor_params.cnvkind.2 == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cvs_se')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][cnvkind][3]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][cnvkind][3]" id="cnvkind_3" value="true" {if $processor_params.cnvkind.3 == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cvs_fm')}</span>
            <br />
            <input type="hidden" name="payment_data[processor_params][cnvkind][73]" value="false" />
            <input type="checkbox" name="payment_data[processor_params][cnvkind][73]" id="cnvkind_73" value="true" {if $processor_params.cnvkind.73 == "true"} checked="checked"{/if} /><span class="jp_admin_checkbox_text">{__('jp_cvs_ck')} / {__('jp_cvs_ts')} / {__('jp_cvs_dy')} / {__('jp_cvs_yd')} / {__('jp_digital_check_cvs_3f')}</span>
        </div>
    </div>
</div>
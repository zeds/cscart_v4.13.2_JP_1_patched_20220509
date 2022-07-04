{* $Id: oricopp_sw.tpl by tommy from cs-cart.jp 2014 *}

<p>{__('jp_oppsw_notice')}</p>
<hr />
{include file="common/subheader.tpl" title=__('jp_oppsw_connections_settings') target="#text_oricopp_sw_connection_settings"}

<div id="text_oricopp_sw_connection_settings" class="in collapse">
    <div class="control-group">
        <label class="control-label" for="type">{__("jp_oppsw_settlement_type")}:</label>
        <div class="controls">
            <select name="payment_data[processor_params][type]" id="type">
                <option value="00" {if $processor_params.type == "00"}selected="selected"{/if}>{__("jp_oppsw_all")}</option>
                <option value="01" {if $processor_params.type == "01"}selected="selected"{/if}>{__("jp_oppsw_cc")}</option>
                <option value="02" {if $processor_params.type == "02"}selected="selected"{/if}>{__("jp_oppsw_cvs")}</option>
                <option value="03" {if $processor_params.type == "03"}selected="selected"{/if}>{__("jp_oppsw_em")}</option>
                <option value="04" {if $processor_params.type == "04"}selected="selected"{/if}>{__("jp_oppsw_bnk")}</option>
                <option value="05" {if $processor_params.type == "05"}selected="selected"{/if}>{__("jp_oppsw_sc")}</option>
            </select>
        </div>
    </div>
</div>
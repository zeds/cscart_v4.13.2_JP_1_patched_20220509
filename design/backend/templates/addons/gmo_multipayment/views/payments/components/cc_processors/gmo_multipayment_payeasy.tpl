{* $Id: gmo_multipayment_payeasy.tpl by tommy from cs-cart.jp 2015 *}

<p>{__("jp_gmo_multipayment_payeasy_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_gmo_multipayment_connections_settings") target="#gmomp_cvs_connection_settings"}
<div id="gmomp_cvs_connection_settings" class="in collapse">
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

{include file="common/subheader.tpl" title=__("jp_gmo_multipayment_cvs_payment_settings") target="#gmomp_cvs_payment_settings"}
        <div class="control-group">
            <label class="control-label" for="paymenttermday">{__('jp_gmo_multipayment_cvs_paymenttermday')}:</label>
            <div class="controls">
                {__('jp_gmo_multipayment_cvs_paymenttermday_prefix')}<input type="text" name="payment_data[processor_params][paymenttermday]" id="paymenttermday" value="{$processor_params.paymenttermday}" class="input-text-short" size="3" />{__('jp_gmo_multipayment_cvs_paymenttermday_suffix')}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="receiptsdisp11">{__("jp_gmo_multipayment_receiptsdisp11")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][receiptsdisp11]" id="receiptsdisp11" value="{$processor_params.receiptsdisp11}">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="receiptsdisp12">{__("jp_gmo_multipayment_receiptsdisp12")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][receiptsdisp12]" id="receiptsdisp12" value="{$processor_params.receiptsdisp12}"  size="12">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="mode">{__("jp_gmo_multipayment_receiptsdisp13")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][receiptsdisp13_start]" id="mode">
                    {section name=starttime start=0 loop=25}
                        {if $smarty.section.starttime.index < 10}
                            {assign var="gmomp_start_time" value="0`$smarty.section.starttime.index`"}
                        {else}
                            {assign var="gmomp_start_time" value=$smarty.section.starttime.index}
                        {/if}
                        <option value="{$gmomp_start_time}:00" {if $processor_params.receiptsdisp13_start == "{$gmomp_start_time}:00"}selected="selected"{/if}>{$gmomp_start_time}:00</option>
                        {if $smarty.section.starttime.index < 24}
                        <option value="{$gmomp_start_time}:30" {if $processor_params.receiptsdisp13_start == "{$gmomp_start_time}:30"}selected="selected"{/if}>{$gmomp_start_time}:30</option>
                        {/if}
                    {/section}
                </select>
                -
                <select name="payment_data[processor_params][receiptsdisp13_end]" id="mode">
                    {section name=endtime start=0 loop=25}
                        {if $smarty.section.endtime.index < 10}
                            {assign var="gmomp_end_time" value="0`$smarty.section.endtime.index`"}
                        {else}
                            {assign var="gmomp_end_time" value=$smarty.section.endtime.index}
                        {/if}
                        <option value="{$gmomp_end_time}:00" {if $processor_params.receiptsdisp13_end == "{$gmomp_end_time}:00"}selected="selected"{/if}>{$gmomp_end_time}:00</option>
                        {if $smarty.section.endtime.index < 24}
                            <option value="{$gmomp_end_time}:30" {if $processor_params.receiptsdisp13_end == "{$gmomp_end_time}:30"}selected="selected"{/if}>{$gmomp_end_time}:30</option>
                        {/if}
                    {/section}
                </select>
            </div>
        </div>
    </fieldset>
</div>


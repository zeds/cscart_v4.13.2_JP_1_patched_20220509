{* $Id: gmo_multipayment_cvs.tpl by tommy from cs-cart.jp 2015 *}
{* Modified by takahashi from cs-cart.jp 2017 *}
{* コンビニコードの変更 *}

<p>{__("jp_gmo_multipayment_cvs_notice")}</p>
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
<div id="gmomp_cvs_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="convenience">{__("jp_gmo_multipayment_cvs_convenience")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][convenience][00007]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][00007]" id="convenience_00007" value="true" {if $processor_params.convenience['00007'] == "true"} checked="checked"{/if} /> {__("jp_cvs_se")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10001]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10001]" id="convenience_10001" value="true" {if $processor_params.convenience['10001'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ls")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10002]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10002]" id="convenience_10002" value="true" {if $processor_params.convenience['10002'] == "true"} checked="checked"{/if} /> {__("jp_cvs_fm")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10005]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10005]" id="convenience_10005" value="true" {if $processor_params.convenience['10005'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ms")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][00009]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][00009]" id="convenience_00009" value="true" {if $processor_params.convenience['00009'] == "true"} checked="checked"{/if} /> {__("jp_gmo_multipayment_cvs_3f")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10003]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10003]" id="convenience_10003" value="true" {if $processor_params.convenience['10003'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ts")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10004]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10004]" id="convenience_10004" value="true" {if $processor_params.convenience['10004'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ck")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][00006]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][00006]" id="convenience_00006" value="true" {if $processor_params.convenience['00006'] == "true"} checked="checked"{/if} /> {__("jp_cvs_dy")}
                <br />
                <input type="hidden" name="payment_data[processor_params][convenience][10008]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][convenience][10008]" id="convenience_10008" value="true" {if $processor_params.convenience['10008'] == "true"} checked="checked"{/if} /> {__("jp_cvs_sm")}
            </div>
        </div>
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


<p>{__('sbps_cctkn_notice')}</p>
<p>{__("jp_sbps_notice")}</p>
<hr />
{include file='common/subheader.tpl' title=__('sbps_connections_settings') target="#ap_sbps_cctkn_connection_settings"}
<div id="ap_sbps_cctkn_connection_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__('test_live_mode')}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][mode]" id="mode">
                    <option value="test" {if $processor_params.mode == 'test'}selected="selected"{/if}>{__('sbps_test')}</option>
                    <option value="live" {if $processor_params.mode == 'live'}selected="selected"{/if}>{__('sbps_live')}</option>
                </select>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="merchant_id">{__('sbps_merchant_id')}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][merchant_id]" id="merchant_id" value="{$processor_params.merchant_id}" size="20"/>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="service_id">{__('sbps_service_id')}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][service_id]" id="service_id" value="{$processor_params.service_id}" size="20"/>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="hash_key">{__('sbps_hashkey')}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][hash_key]" id="hash_key" value="{$processor_params.hash_key}" size="45"/>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="hash_key">{__('sbps_encrypt_key')}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][encrypt_key]" id="hash_key" value="{$processor_params.encrypt_key}" size="45"/>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="hash_key">{__('sbps_init_key')}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][init_key]" id="hash_key" value="{$processor_params.init_key}" size="45"/>
            </div>
        </div>
    </fieldset>
</div>

{include file="common/subheader.tpl" title=__('sbps_cc_payment_settings') target="#ap_sbps_cc_payment_settings"}
<div id="ap_sbps_cc_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="use_cvv">{__('sbps_cc_use_cvv')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][use_cvv]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][use_cvv]" id="use_cvv" value="true" {if $processor_params.use_cvv == 'true'} checked="checked"{/if} />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="dealings_type">{__('jp_cc_method')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][dealings_type][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][dealings_type][10]" id="dealings_type_10" value="true" {if $processor_params.dealings_type[10] == 'true'} checked="checked"{/if} /> {__('jp_cc_onetime')}
                <input type="hidden" name="payment_data[processor_params][dealings_type][21]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][dealings_type][21]" id="dealings_type_21" value="true" {if $processor_params.dealings_type[21] == 'true'} checked="checked"{/if} /> {__('sbps_cc_bonus')}
                <input type="hidden" name="payment_data[processor_params][dealings_type][61]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][dealings_type][61]" id="dealings_type_61" value="true" {if $processor_params.dealings_type[61] == 'true'} checked="checked"{/if} /> {__('jp_cc_installment')}
                <input type="hidden" name="payment_data[processor_params][dealings_type][80]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][dealings_type][80]" id="dealings_type_80" value="true" {if $processor_params.dealings_type[80] == 'true'} checked="checked"{/if} /> {__('jp_cc_revo')}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="divide_times">{__('jp_cc_installment_times')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][divide_times][3]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][3]" id="paycount_3" value="true" {if $processor_params.divide_times[3] == 'true'} checked="checked"{/if} /> 3{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][5]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][5]" id="paycount_5" value="true" {if $processor_params.divide_times[5] == 'true'} checked="checked"{/if} /> 5{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][6]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][6]" id="paycount_6" value="true" {if $processor_params.divide_times[6] == 'true'} checked="checked"{/if} /> 6{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][10]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][10]" id="paycount_10" value="true" {if $processor_params.divide_times[10] == 'true'} checked="checked"{/if} /> 10{__('jp_paytimes_unit')}
                <br />
                <input type="hidden" name="payment_data[processor_params][divide_times][12]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][12]" id="paycount_12" value="true" {if $processor_params.divide_times[12] == 'true'} checked="checked"{/if} /> 12{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][15]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][15]" id="paycount_15" value="true" {if $processor_params.divide_times[15] == 'true'} checked="checked"{/if} /> 15{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][18]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][18]" id="paycount_18" value="true" {if $processor_params.divide_times[18] == 'true'} checked="checked"{/if} /> 18{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][20]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][20]" id="paycount_20" value="true" {if $processor_params.divide_times[20] == 'true'} checked="checked"{/if} /> 20{__('jp_paytimes_unit')}
                <input type="hidden" name="payment_data[processor_params][divide_times][24]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][divide_times][24]" id="paycount_24" value="true" {if $processor_params.divide_times[24] == 'true'} checked="checked"{/if} /> 24{__('jp_paytimes_unit')}
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="cust_manage">{__('sbps_cc_cust_manage')}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][cust_manage]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cust_manage]" id="cust_manage" value="true" {if $processor_params.cust_manage == 'true'} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>
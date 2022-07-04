<p>{__('sbps_ccreg_notice')}</p>
<hr />

{include file="common/subheader.tpl" title=__('sbps_cc_payment_settings') target="#ap_sbps_ccreg_payment_settings"}
<div id="ap_sbps_ccreg_payment_settings" class="in collapse">
    <fieldset>
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
    </fieldset>
</div>
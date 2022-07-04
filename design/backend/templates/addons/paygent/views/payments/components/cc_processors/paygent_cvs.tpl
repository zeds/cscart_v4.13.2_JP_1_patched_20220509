{* $Id: paygent_cvs.tpl by tommy from cs-cart.jp 2016 *}

<p>{__("jp_paygent_cvs_notice")}</p>
<hr />
{include file="common/subheader.tpl" title=__("jp_paygent_cvs_payment_settings") target="#pygnt_payment_settings"}
<div id="pygnt_payment_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="convenience">{__("jp_paygent_cvs_available")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C001]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C001]" id="convenience_00C001" value="true" {if $processor_params.cvs_company_id['00C001'] == "true"} checked="checked"{/if} /> {__("jp_cvs_se")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C002]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C002]" id="convenience_00C002" value="true" {if $processor_params.cvs_company_id['00C002'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ls")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C005]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C005]" id="convenience_00C005" value="true" {if $processor_params.cvs_company_id['00C005'] == "true"} checked="checked"{/if} /> {__("jp_cvs_fm")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C004]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C004]" id="convenience_00C004" value="true" {if $processor_params.cvs_company_id['00C004'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ms")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C006]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C006]" id="convenience_00C006" value="true" {if $processor_params.cvs_company_id['00C006'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ts")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C007]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C007]" id="convenience_00C007" value="true" {if $processor_params.cvs_company_id['00C007'] == "true"} checked="checked"{/if} /> {__("jp_cvs_ck")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C014]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C014]" id="convenience_00C014" value="true" {if $processor_params.cvs_company_id['00C014'] == "true"} checked="checked"{/if} /> {__("jp_cvs_dy")}
                <br />
                <input type="hidden" name="payment_data[processor_params][cvs_company_id][00C016]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][cvs_company_id][00C016]" id="convenience_00C016" value="true" {if $processor_params.cvs_company_id['00C016'] == "true"} checked="checked"{/if} /> {__("jp_cvs_sm")}
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="paygent_postpay">{__("jp_paygent_cvs_allow_postpay")}:</label>
            <div class="controls">
                <input type="hidden" name="payment_data[processor_params][paygent_postpay]" value="false" />
                <input type="checkbox" name="payment_data[processor_params][paygent_postpay]" id="paygent_postpay" value="true" {if $processor_params.paygent_postpay == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="payment_limit_date">{__("jp_paygent_cvs_payment_limit_date")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][payment_limit_date]" id="payment_limit_date" value="{$processor_params.payment_limit_date}"  size="3">
            </div>
        </div>
    </fieldset>
</div>

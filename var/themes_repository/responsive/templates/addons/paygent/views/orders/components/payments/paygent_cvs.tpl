{* $Id: paygent_cvs.tpl by tommy from cs-cart.jp 2016 *}

<div class="ty-control-group">
    <label for="jp_paygent_cvs_name" class="ty-control-group__title cm-required">{__('jp_paygent_select_cvs')}</label>
    <select id="jp_paygent_cvs_name" name="payment_info[jp_paygent_cvs_name]" onchange="fn_check_paygent_cvs_code(this.value);">
        {if $payment_method.processor_params.cvs_company_id.00C002 == 'true'}
            <option value="00C002">{__("jp_cvs_ls")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C001 == 'true'}
            <option value="00C001">{__("jp_cvs_se")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C005 == 'true'}
            <option value="00C005">{__("jp_cvs_fm")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C006 == 'true'}
            <option value="00C006">{__("jp_cvs_ts")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C007 == 'true'}
            <option value="00C007">{__("jp_cvs_ck")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C016 == 'true'}
            <option value="00C016">{__("jp_cvs_sm")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C004 == 'true'}
            <option value="00C004">{__("jp_cvs_ms")}</option>
        {/if}
        {if $payment_method.processor_params.cvs_company_id.00C014 == 'true'}
            <option value="00C014">{__("jp_cvs_dy")}</option>
        {/if}
    </select>
</div>
{if $payment_method.processor_params.paygent_postpay == 'true'}
    <div class="ty-control-group hidden" id="display_paygent_cvs_payment_timing">
        <label for="jp_paygent_cvs_payment_timing" class="ty-control-group__title cm-required">{__("jp_paygent_cvs_payment_timing")}:</label>
        <p>
            <input type="radio" name="payment_info[jp_paygent_cvs_payment_timing]" id="prepaid" value="1" checked="checked" class="radio" />{__("jp_paygent_cvs_prepaid")}
            &nbsp;&nbsp;
            <input type="radio" name="payment_info[jp_paygent_cvs_payment_timing]" id="postpaid" value="3" class="radio" />{__("jp_paygent_cvs_postpaid")}
        </p>
    </div>
{/if}

<script>
    //<![CDATA[
    {literal}
    function fn_check_paygent_cvs_code(cvs_code)
    {
        if (cvs_code == '00C001') {
            $('#display_paygent_cvs_payment_timing').switchAvailability(false);
        } else {
            $('#display_paygent_cvs_payment_timing').switchAvailability(true);
        }
    }
    {/literal}

    fn_check_paygent_cvs_code($('#jp_paygent_cvs_name').val());
    //]]>
</script>
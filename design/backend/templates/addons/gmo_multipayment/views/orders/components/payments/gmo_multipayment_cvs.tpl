{* $Id: gmo_multipayment_cvs.tpl by tommy from cs-cart.jp 2015 *}

<div class="control-group">
    <label for="jp_gmomp_cvs_name" class="ty-control-group__title cm-required">{__('jp_gmo_multipayment_cvs_select_cvs')}</label>
    <div class="controls">
        <select id="jp_gmomp_cvs_name" name="payment_info[convenience]">
            {if $payment_method.processor_params.convenience['00007'] == 'true'}
                <option value="00007">{__("jp_cvs_se")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10001'] == 'true'}
                <option value="10001">{__("jp_cvs_ls")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10002'] == 'true'}
                <option value="10002">{__("jp_cvs_fm")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10005'] == 'true'}
                <option value="10005">{__("jp_cvs_ms")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['00009'] == 'true'}
                <option value="00009">{__("jp_gmo_multipayment_cvs_3f")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10003'] == 'true'}
                <option value="10003">{__("jp_cvs_ts")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10004'] == 'true'}
                <option value="10004">{__("jp_cvs_ck")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['00006'] == 'true'}
                <option value="00006">{__("jp_cvs_dy")}</option>
            {/if}
            {if $payment_method.processor_params.convenience['10008'] == 'true'}
                <option value="10008">{__("jp_cvs_sm")}</option>
            {/if}
        </select>
    </div>
</div>

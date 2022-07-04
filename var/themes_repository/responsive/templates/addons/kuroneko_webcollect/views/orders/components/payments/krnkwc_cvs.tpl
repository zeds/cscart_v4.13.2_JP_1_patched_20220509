{* $Id: krnkwc_cvs.tpl by tommy from cs-cart.jp 2016 *}

<div class="ty-control-group">
    <label for="jp_krnkwc_cvs_name" class="ty-control-group__title cm-required">{__('jp_kuroneko_webcollect_cvs_select_cvs')}</label>
    <select id="jp_krnkwc_cvs_name" name="payment_info[cvs]">
        {if $payment_method.processor_params.cvs['se'] == 'true'}
            <option value="se">{__("jp_cvs_se")}</option>
        {/if}
        {if $payment_method.processor_params.cvs['fm'] == 'true'}
            <option value="fm">{__("jp_cvs_fm")}</option>
        {/if}
        {if $payment_method.processor_params.cvs['ls'] == 'true'}
            <option value="ls">{__("jp_cvs_ls")}</option>
        {/if}
        {if $payment_method.processor_params.cvs['ck'] == 'true'}
            <option value="ck">{__("jp_cvs_ck")}{__("jp_cvs_ts")}</option>
        {/if}
        {if $payment_method.processor_params.cvs['ms'] == 'true'}
            <option value="ms">{__("jp_cvs_ms")}</option>
        {/if}
        {if $payment_method.processor_params.cvs['sm'] == 'true'}
            <option value="sm">{__("jp_cvs_sm")}</option>
        {/if}
    </select>
</div>

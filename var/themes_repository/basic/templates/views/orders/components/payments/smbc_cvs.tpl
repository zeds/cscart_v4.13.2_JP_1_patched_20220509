{* $Id: smbc_cvs.tpl by tommy from cs-cart.jp 2013 *}

<div class="control-group">
    <label for="jp_smbc_cvs_name" class="cm-required">{__("jp_smbc_cvs_select_cvs")}</label>
    <select id="jp_smbc_cvs_name" name="payment_info[jp_smbc_cvs_cnvkind]">
        {if $payment_method.processor_params.cnvkind['0301'] == 'true'}
            <option value="0301">{__("jp_cvs_se")}</option>
        {/if}
        {if $payment_method.processor_params.cnvkind['0302'] == 'true'}
            <option value="0302">{__("jp_cvs_ls")} / {__("jp_cvs_ms")}</option>
        {/if}
        {if $payment_method.processor_params.cnvkind['0303'] == 'true'}
            <option value="0303">{__("jp_cvs_sm")}</option>
        {/if}
        {if $payment_method.processor_params.cnvkind['0304'] == 'true'}
            <option value="0304">{__("jp_cvs_fm")}</option>
        {/if}
        {if $payment_method.processor_params.cnvkind['0305'] == 'true'}
            <option value="0305">{__("jp_cvs_ck")} / {__("jp_cvs_ts")}</option>
        {/if}
    </select>
</div>

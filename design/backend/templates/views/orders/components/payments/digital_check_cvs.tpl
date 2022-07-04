{* $Id: digital_check_cvs.tpl by tommy from cs-cart.jp 2014 *}

<div class="clearfix">
    <div class="control-group">
        <label class="control-label cm-required">{__('jp_digital_check_cvs_select_cvs')}:</label>
        <div class="controls">
            <select id="jp_digital_check_cvs_name" name="payment_info[jp_digital_check_cvs_cnvkind]">
                {if $payment_method.processor_params.cnvkind.1 == 'true'}
                    <option value="1">{__('jp_cvs_ls')} / {__('jp_cvs_sm')} / {__('jp_digital_check_cvs_ms')}</option>
                {/if}
                {if $payment_method.processor_params.cnvkind.2 == 'true'}
                    <option value="2">{__('jp_cvs_se')}</option>
                {/if}
                {if $payment_method.processor_params.cnvkind.3 == 'true'}
                    <option value="3">{__('jp_cvs_fm')}</option>
                {/if}
                {if $payment_method.processor_params.cnvkind.73 == 'true'}
                    <option value="73">{__('jp_cvs_ck')} / {__('jp_cvs_ts')} / {__('jp_cvs_dy')} / {__('jp_cvs_yd')} / {__('jp_digital_check_cvs_3f')}</option>
                {/if}
            </select>
        </div>
    </div>
</div>

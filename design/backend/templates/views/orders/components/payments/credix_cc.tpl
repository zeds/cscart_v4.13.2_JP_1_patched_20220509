{* $Id: credix_cc.tpl by tommy from cs-cart.jp 2014 *}

<div class="clearfix">
    {if ($payment_method.processor_params.quick_charge == 'true' && $auth.user_id && $auth.user_id > 0)}
    <div class="credit-card">
        <div class="control-group">
            <label for="jp_credix_use_quick_charge" class="cm-required">{__("jp_credix_use_quick_charge")}</label>
            <p>
                <input type="radio" name="payment_info[jp_credix_use_quick_charge]" id="quick_charge_yes" value="Y" checked="checked" class="radio" /> {__("yes")}
                &nbsp;&nbsp;
                <input type="radio" name="payment_info[jp_credix_use_quick_charge]" id="quick_charge_no" value="N" class="radio" /> {__("no")}
            </p>
        </div>
    </div>
    {/if}
</div>

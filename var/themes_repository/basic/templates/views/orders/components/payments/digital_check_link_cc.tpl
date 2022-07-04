{* $Id: digital_check_link_cc.tpl by tommy from cs-cart.jp 2015 *}

<div class="clearfix">
    {if $payment_method.processor_params.use_uid == 'true' && $auth.user_id && $auth.user_id > 0}
    <div class="credit-card">
        <div class="control-group">
            <label for="use_uid" class="cm-required">{__("jp_digital_check_register_card_info")}</label>
            <p>
                <input type="radio" name="payment_info[use_uid]" id="use_uid_yes" value="true" checked="checked" class="radio" />{__("yes")}
                &nbsp;&nbsp;
                <input type="radio" name="payment_info[use_uid]" id="use_uid_no" value="false" class="radio" />{__("no")}
            </p>
        </div>
    </div>
    {/if}
</div>

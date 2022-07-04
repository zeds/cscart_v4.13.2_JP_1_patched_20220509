{* $Id: edit_profile_fields.pre.tpl by takahashi from cs-cart.jp 2018 *}
{if $auth.user_id && $auth.user_id > 0
    && $auth.user_id|fn_kuroneko_kakebarai_is_kakebarai_user
    && fn_kuroneko_kakebarai_is_payment_registered('A')
    && fn_krnkkb_check_use_Usable() == __("jp_kuroneko_kakebarai_use_usable_ok")
}
    <div class="checkout__block">
        <p class="ty-error-text">
            {__("jp_kuroneko_kakebarai_bill_to_notice")}
        </p>
    </div>
{/if}

{* $Id: detailed_content.post.tpl by takahashi from cs-cart.jp 2018 *}

{if $addons.subscription_payment_jp.status == 'A'}
{include file="common/subheader.tpl" title=__("jp_sonypayment_carrier_rb_payment") target="#acc_jp_sonypayment_carrier_rb_payment"}

<div id="acc_jp_sonypayment_carrier_rb_payment" class="in collapse">

    {include file="common/subheader2.tpl" title=__("jp_sonypayment_carrier_docomo")}

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_first_payment_day">{__("jp_sonypayment_carrier_rb_first_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_first_payment_day_01]" id="sonypayment_carrier_rb_first_payment_day">
                <option {if $sonypayment_carrier_rb_product_01.first_payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_registered_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_01.first_payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')} ({__('jp_sonypayment_carrier_rb_payment_ifpast_nextmonth')})</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_01.first_payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_payment_day">{__("jp_sonypayment_carrier_rb_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_payment_day_01]" id="sonypayment_carrier_rb_payment_day">
                <option {if $sonypayment_carrier_rb_product_01.payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_carrier_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_01.payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')}</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_01.payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

    {include file="common/subheader2.tpl" title=__("jp_sonypayment_carrier_au")}

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_first_payment_day">{__("jp_sonypayment_carrier_rb_first_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_first_payment_day_02]" id="sonypayment_carrier_rb_first_payment_day">
                <option {if $sonypayment_carrier_rb_product_02.first_payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_registered_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_02.first_payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')} ({__('jp_sonypayment_carrier_rb_payment_ifpast_nextmonth')})</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_02.first_payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_payment_day">{__("jp_sonypayment_carrier_rb_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_payment_day_02]" id="sonypayment_carrier_rb_payment_day">
                <option {if $sonypayment_carrier_rb_product_02.payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_carrier_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_02.payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')}</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_02.payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

    {include file="common/subheader2.tpl" title=__("jp_sonypayment_carrier_softbank")}

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_first_payment_day">{__("jp_sonypayment_carrier_rb_first_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_first_payment_day_03]" id="sonypayment_carrier_rb_first_payment_day">
                <option {if $sonypayment_carrier_rb_product_03.first_payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_registered_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_03.first_payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')} ({__('jp_sonypayment_carrier_rb_payment_ifpast_nextmonth')})</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_03.first_payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="sonypayment_carrier_rb_payment_day">{__("jp_sonypayment_carrier_rb_payment_day")}:</label>
        <div class="controls">
            <select name="product_data[sonypayment_carrier_rb_payment_day_03]" id="sonypayment_carrier_rb_payment_day">
                <option {if $sonypayment_carrier_rb_product_03.payment_day == 0}selected="selected"{/if} value="00">{__('jp_sonypayment_carrier_rb_day_none')} ({__('jp_sonypayment_carrier_rb_payment_carrier_day')})</option>
                {for $day=1 to 28}
                    <option {if $sonypayment_carrier_rb_product_03.payment_day == $day}selected="selected"{/if} value="{$day}">{$day}{__('jp_sonypayment_carrier_rb_day_day')}</option>
                {/for}
                <option {if $sonypayment_carrier_rb_product_03.payment_day == 99}selected="selected"{/if} value="99">{__('jp_sonypayment_carrier_rb_day_eom')}</option>
            </select>
        </div>
    </div>

</div>
{/if}

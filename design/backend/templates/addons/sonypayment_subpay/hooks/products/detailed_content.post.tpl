{* $Id: detailed_content.post.tpl by takahashi from cs-cart.jp 2019 *}

{if $addons.subscription_payment_jp.status == 'A'}
{include file="common/subheader.tpl" title=__("jp_sonys_payment") target="#acc_jp_sonys_payment"}

<div id="acc_jp_sonys_payment" class="in collapse">

    <div class="control-group">
        <label class="control-label" for="sonys_second_price">{__("jp_sonys_second_price")}:</label>
        <div class="controls">
            <input type="tel" name="product_data[sonys_second_price]" id="sonys_second_price" value="{$sonys_product.second_price}">
        </div>
    </div>

    {assign var="sonys_deliver_day_w" value=$sonys_product.deliver_day_w|unserialize}

    <div class="control-group">
        <label class="control-label" for="sonys_deliver_freq_w">{__("jp_sonys_deliver_send_day")} - {__("jp_sonys_deliver_freq_w")}:</label>
        <div class="controls id="sonys_deliver_freq_w">
            {for $var=1 to 7}
                <label class="checkbox inline" for="sonys_deliver_send_day_{$var}">
                    <input type="checkbox" name="product_data[sonys_deliver_send_day_w][]" id="sonys_deliver_send_day_{$var}"  value="{$var}" {if $var|in_array:$sonys_deliver_day_w}checked{/if}/>
                    {__("jp_sonys_deliver_send_day_`$var`")}</label>
            {/for}
        </div>
    </div>

    {assign var="sonys_deliver_day_bw" value=$sonys_product.deliver_day_bw|unserialize}

    <div class="control-group">
        <label class="control-label" for="sonys_deliver_freq_bw">{__("jp_sonys_deliver_send_day")} - {__("jp_sonys_deliver_freq_bw")}:</label>
        <div class="controls id="sonys_deliver_freq_bw">
            {for $var=1 to 7}
                <label class="checkbox inline" for="sonys_deliver_send_day_{$var}">
                    <input type="checkbox" name="product_data[sonys_deliver_send_day_bw][]" id="sonys_deliver_send_day_{$var}"  value="{$var}" {if $var|in_array:$sonys_deliver_day_bw}checked{/if}/>
                    {__("jp_sonys_deliver_send_day_`$var`")}</label>
            {/for}
        </div>
    </div>

    {assign var="sonys_deliver_day_m" value=$sonys_product.deliver_day_m|unserialize}

    <div class="control-group">
        <label class="control-label" for="sonys_deliver_freq_m">{__("jp_sonys_deliver_send_day")} - {__("jp_sonys_deliver_freq_m")}:</label>
        <div class="controls" id="sonys_deliver_freq_m">
            <label class="checkbox inline" for="sonys_deliver_send_m_day_1">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_1"  value="1" {if "1"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_1")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_5">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_5"  value="5" {if "5"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_5")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_10">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_10"  value="10" {if "10"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_10")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_15">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_15"  value="15" {if "15"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_15")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_25">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_25"  value="25" {if "25"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_25")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_end">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_m][]" id="sonys_deliver_send_m_day_end"  value="end" {if "end"|in_array:$sonys_deliver_day_m}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_end")}</label>
        </div>
    </div>

    {assign var="sonys_deliver_day_bm" value=$sonys_product.deliver_day_bm|unserialize}

    <div class="control-group">
        <label class="control-label" for="sonys_deliver_freq_bm">{__("jp_sonys_deliver_send_day")} - {__("jp_sonys_deliver_freq_bm")}:</label>
        <div class="controls" id="sonys_deliver_freq_bm">
            <label class="checkbox inline" for="sonys_deliver_send_m_day_1">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_1"  value="1" {if "1"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_1")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_5">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_5"  value="5" {if "5"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_5")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_10">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_10"  value="10" {if "10"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_10")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_15">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_15"  value="15" {if "15"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_15")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_25">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_25"  value="25" {if "25"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_25")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_m_day_end">
                <input type="checkbox" name="product_data[sonys_deliver_send_day_bm][]" id="sonys_deliver_send_m_day_end"  value="end" {if "end"|in_array:$sonys_deliver_day_bm}checked{/if}/>
                {__("jp_sonys_deliver_send_m_day_end")}</label>
        </div>
    </div>

    {assign var="sonys_deliver_time" value=$sonys_product.deliver_time|unserialize}

    <div class="control-group">
        <label class="control-label" for="sonys_deliver_send">{__("jp_sonys_deliver_send_time")}:</label>
        <div class="controls">
            <label class="checkbox inline" for="sonys_deliver_send_time_am">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_am"  value="am" {if "am"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_am")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_pm">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_pm"  value="pm" {if "pm"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_pm")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_8_10">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_8_10"  value="8_10" {if "8_10"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_8_10")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_10_12">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_10_12"  value="10_12" {if "10_12"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_10_12")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_12_14">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_12_14"  value="12_14" {if "12_14"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_12_14")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_14_16">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_14_16"  value="14_16" {if "14_16"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_14_16")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_16_18">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_16_18"  value="16_18" {if "16_18"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_16_18")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_18_20">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_18_20"  value="18_20" {if "18_20"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_18_20")}</label>
            <label class="checkbox inline" for="sonys_deliver_send_time_20_22">
                <input type="checkbox" name="product_data[sonys_deliver_send_time][]" id="sonys_deliver_send_time_20_22"  value="20_22" {if "20_22"|in_array:$sonys_deliver_time}checked{/if}/>
                {__("jp_sonys_deliver_send_time_20_22")}</label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_date_jp_sonys_start_date_holder">{__("jp_sonys_start_date")}:</label>
        <div class="controls">
            {include file="common/calendar.tpl" date_id="elm_date_jp_sonys_start_date_holder" date_name="product_data[sonys_start_date]" date_val=$sonys_product.start_date|default:$smarty.const.TIME start_year=$settings.Company.company_start_year}
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="elm_date_jp_sonys_end_date_holder">{__("jp_sonys_end_date")}:</label>
        <div class="controls">
            {include file="common/calendar.tpl" date_id="elm_date_jp_sonys_end_date_holder" date_name="product_data[sonys_end_date]" date_val=$sonys_product.end_date start_year=$settings.Company.company_start_year}
        </div>
    </div>

</div>
{/if}

{* Modified by tommy from cs-cart.jp 2016 *}

{if $settings.Appearance.calendar_date_format == "month_first"}
    {assign var="date_format" value="%m/%d/%Y"}
{else}
    {assign var="date_format" value="%d/%m/%Y"}
{/if}
{if $smarty.const.CART_LANGUAGE == "ja"}
    {assign var="date_format" value="%Y/%m/%d"}
{/if}
<div class="calendar-block">
    <input type="text" id="{$date_id}" name="{$date_name}" class="input-text-medium{if $date_meta} {$date_meta}{/if} cm-calendar" value="{if $date_val}{$date_val|date_format:"`$date_format`"}{/if}" {$extra} size="10" />&nbsp;<a class="cm-external-focus calendar-link" data-ca-external-focus-id="{$date_id}"><i class="icon-calendar calendar-but valign" title="{__("calendar")}"></i></a>
    <script>
    //<![CDATA[
    (function(_, $) {$ldelim}
        $.ceEvent('on', 'ce.commoninit', function(context) {
            $('#{$date_id}').datepicker({
                changeMonth: true,
                duration: 'fast',
                changeYear: true,
                numberOfMonths: 1,
                selectOtherMonths: true,
                showOtherMonths: true,
                firstDay: {if $settings.Appearance.calendar_week_format == "sunday_first"}0{else}1{/if},
                dayNamesMin: ['{__("weekday_abr_0")}', '{__("weekday_abr_1")}', '{__("weekday_abr_2")}', '{__("weekday_abr_3")}', '{__("weekday_abr_4")}', '{__("weekday_abr_5")}', '{__("weekday_abr_6")}'],
                monthNamesShort: ['{__("month_name_abr_1")}', '{__("month_name_abr_2")}', '{__("month_name_abr_3")}', '{__("month_name_abr_4")}', '{__("month_name_abr_5")}', '{__("month_name_abr_6")}', '{__("month_name_abr_7")}', '{__("month_name_abr_8")}', '{__("month_name_abr_9")}', '{__("month_name_abr_10")}', '{__("month_name_abr_11")}', '{__("month_name_abr_12")}'],
                yearRange: '{if $start_year}{$start_year}{else}c-100{/if}:c+10',
                dateFormat: '{if $smarty.const.CART_LANGUAGE == 'ja'}yy/mm/dd{else}{if $settings.Appearance.calendar_date_format == "month_first"}mm/dd/yy{else}dd/mm/yy{/if}{/if}'
            });
        });
    {$rdelim}(Tygh, Tygh.$));
    //]]>
    </script>
</div>
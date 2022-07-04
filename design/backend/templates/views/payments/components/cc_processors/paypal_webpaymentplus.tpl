{* $Id: paypao_webpaymentplus.tpl by tommy from cs-cart.jp 2013 *}

{include file="common/subheader.tpl" title=__("pwpp_connection_settings") target="#pwpp_connection_settings"}
<div id="pwpp_connection_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="mode">{__("test_live_mode")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][mode]" id="mode">
                    <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
                    <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
                </select>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="accont">{__("account")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][account]" id="account" value="{$processor_params.account}" class="input-text" />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="currency">{__("currency")}:</label>
            <div class="controls">
                <select name="payment_data[processor_params][currency]" id="currency">
                    <option value="JPY" {if $processor_params.currency == "JPY"}selected="selected"{/if}>{__("currency_code_jpy")}</option>
                    <!--
                    <option value="CAD" {if $processor_params.currency == "CAD"}selected="selected"{/if}>{__("currency_code_cad")}</option>
                    <option value="EUR" {if $processor_params.currency == "EUR"}selected="selected"{/if}>{__("currency_code_eur")}</option>
                    <option value="GBP" {if $processor_params.currency == "GBP"}selected="selected"{/if}>{__("currency_code_gbp")}</option>
                    <option value="USD" {if $processor_params.currency == "USD"}selected="selected"{/if}>{__("currency_code_usd")}</option>
                    <option value="AUD" {if $processor_params.currency == "AUD"}selected="selected"{/if}>{__("currency_code_aud")}</option>
                    <option value="NZD" {if $processor_params.currency == "NZD"}selected="selected"{/if}>{__("currency_code_nzd")}</option>
                    <option value="CHF" {if $processor_params.currency == "CHF"}selected="selected"{/if}>{__("currency_code_chf")}</option>
                    <option value="HKD" {if $processor_params.currency == "HKD"}selected="selected"{/if}>{__("currency_code_hkd")}</option>
                    <option value="SGD" {if $processor_params.currency == "SGD"}selected="selected"{/if}>{__("currency_code_sgd")}</option>
                    <option value="SEK" {if $processor_params.currency == "SEK"}selected="selected"{/if}>{__("currency_code_sek")}</option>
                    <option value="DKK" {if $processor_params.currency == "DKK"}selected="selected"{/if}>{__("currency_code_dkk")}</option>
                    <option value="PLN" {if $processor_params.currency == "PLN"}selected="selected"{/if}>{__("currency_code_pln")}</option>
                    <option value="NOK" {if $processor_params.currency == "NOK"}selected="selected"{/if}>{__("currency_code_nok")}</option>
                    <option value="HUF" {if $processor_params.currency == "HUF"}selected="selected"{/if}>{__("currency_code_huf")}</option>
                    <option value="CZK" {if $processor_params.currency == "CZK"}selected="selected"{/if}>{__("currency_code_czk")}</option>
                    <option value="ILS" {if $processor_params.currency == "ILS"}selected="selected"{/if}>{__("currency_code_ils")}</option>
                    <option value="MXN" {if $processor_params.currency == "MXN"}selected="selected"{/if}>{__("currency_code_mxn")}</option>
                    <option value="BRL" {if $processor_params.currency == "BRL"}selected="selected"{/if}>{__("currency_code_brl")}</option>
                    <option value="MYR" {if $processor_params.currency == "MYR"}selected="selected"{/if}>{__("currency_code_myr")}</option>
                    <option value="PHP" {if $processor_params.currency == "PHP"}selected="selected"{/if}>{__("currency_code_php")}</option>
                    <option value="TWD" {if $processor_params.currency == "TWD"}selected="selected"{/if}>{__("currency_code_twd")}</option>
                    <option value="THB" {if $processor_params.currency == "THB"}selected="selected"{/if}>{__("currency_code_thb")}</option>
                    <option value="TRY" {if $processor_params.currency == "TRY"}selected="selected"{/if}>{__("currency_code_try")}</option>
                -->
                </select>
            </div>
        </div>
    </fieldset>
</div>
<p><a href="https://www.paypal.com/jp/cgi-bin/webscr?cmd=_registration-run
" target="_blank">{__("pwpp_registration_link")}</a><br /><br /></p>

<p>{__("pwpp_pay_settings_desc")}</p>

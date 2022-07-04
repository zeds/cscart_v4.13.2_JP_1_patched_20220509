{include file="common/subheader.tpl" title=__("gmo_option_settings") target="#gmo_option_settings"}
<p>{__("gmo_payment_description")}</p>
<div id="gmo_option_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="gmo_certification_id">{__("gmo_certification_id")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][gmo_certification_id]" id="gmo_certification_id" value="{$processor_params.gmo_certification_id}">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gmo_connection_pass">{__("gmo_connection_pass")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][gmo_connection_pass]" id="gmo_connection_pass" value="{$processor_params.gmo_connection_pass}">
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="gmo_merchant_code">{__("gmo_merchant_code")}:</label>
            <div class="controls">
                <input type="text" name="payment_data[processor_params][gmo_merchant_code]" id="gmo_merchant_code" value="{$processor_params.gmo_merchant_code}">
            </div>
        </div>
        <div class="control-group">
    		<label class="control-label" for="cvv2">{__("gmo_include")}:</label>
    		<div class="controls">
        		<input type="hidden" name="payment_data[processor_params][gmo_include]" value="N">
        		<input type="checkbox" name="payment_data[processor_params][gmo_include]" id="cvv2" value="Y" {if $processor_params.gmo_include == "Y"}checked="checked"{/if}>
   		 	</div>
		</div>
        <div class="control-group">
            <label class="control-label" for="test">{__("gmo_test")}:</label>
            <div class="controls">
               <select name="payment_data[processor_params][test]" id="test">
                  <option value="N" {if $processor_params.test == "N"}selected="selected"{/if}>{__("live")}</option>
                  <option value="Y" {if $processor_params.test == "Y"}selected="selected"{/if}>{__("test")}</option>
               </select>
            </div>
        </div>
    </fieldset>
</div>
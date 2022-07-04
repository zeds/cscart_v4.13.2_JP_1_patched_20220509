<p>{__("atone_payment_description")}</p>
{include file="common/subheader.tpl" title=__("atone_option_settings") target="#atone_option_settings"}
<div id="atone_option_settings" class="in collapse">
    <fieldset>
        <div class="control-group">
            <label class="control-label" for="opreg">{__("atone_option_regularly")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][opreg]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][opreg]" id="opreg" value="true" disabled="disabled" />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="opupdates">{__("atone_option_updates")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][opupdates]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][opupdates]" id="opupdates" value="true" {if $processor_params.opupdates == "true"} checked="checked"{/if} />
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="oppro">{__("atone_option_provisional")}:</label>
            <div class="controls">
	            <input type="hidden" name="payment_data[processor_params][oppro]" value="false" />
	            <input type="checkbox" name="payment_data[processor_params][oppro]" id="oppro" value="true" {if $processor_params.oppro == "true"} checked="checked"{/if} />
            </div>
        </div>
    </fieldset>
</div>
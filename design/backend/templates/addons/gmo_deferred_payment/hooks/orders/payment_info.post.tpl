{assign var="result" value=$result}
{if $result}
   <div class="control-group">
      <div class="control-label">{__("gmo_processing_result")}</div>
      <div id="tygh_payment_info" class="controls">{if $result.result == "OK"}OK{else}{__("gmo_processing_result_ng")}{/if}</div>
   </div>
   {if $result.result == "OK"}
     <div class="control-group">
        <div class="control-label">{__("gmo_automatic_examination")}</div>
        <div id="tygh_payment_info" class="controls">{$result.transactionResult.autoAuthorResult}</div>
     </div>
     {if $result.transactionResult.maulAuthorResult}
       <div class="control-group">
          <div class="control-label">{__("gmo_maulauthor_result")}</div>
          <div id="tygh_payment_info" class="controls">{$result.transactionResult.maulAuthorResult}</div>
       </div>
     {/if}
     {if $result.transactionResult.reasons}
       <div class="control-group">
          <div class="control-label">{__("gmo_maulauthor_result_reasons")}</div>
          {foreach from=$result.transactionResult.reasons item="r_reason"}
          <div id="tygh_payment_info" class="controls">{$r_reason}</div>
          {/foreach}
       </div>
     {/if}
   {/if}
   {if $result.errors}
       <div class="control-group">
          <div class="control-label">{__("gmo_result_errors")}</div>
          {foreach from=$result.errors item="error"}
          <div id="tygh_payment_info" class="controls">{$error.errorMessage}</div>
          {/foreach}
       </div>
   {/if}
{/if}
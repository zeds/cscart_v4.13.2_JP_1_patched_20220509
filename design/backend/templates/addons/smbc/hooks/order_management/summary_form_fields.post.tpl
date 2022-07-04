{* $Id: summary_form_fields.post.tpl by tommy from cs-cart.jp 2012 *}

{if $payment_method.processor_id == '9040' || $payment_method.processor_id == '9044'}
<script>
//<![CDATA[
$(document).ready(function(){$ldelim}
{literal}
$(".buttons-container .submit-button-big .button-left-margin").hide();
{/literal}
{$rdelim});
//]]>
</script>
{/if}
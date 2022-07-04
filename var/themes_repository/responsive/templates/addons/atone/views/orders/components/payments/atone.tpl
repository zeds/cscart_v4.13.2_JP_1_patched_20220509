{script src="js/addons/atone/atone.js"}

{assign var="ap_pub_key" value=$pub_key}
{assign var="atone_datas" value=$datas}
{assign var="pre_token_key" value=$pre_token}
{assign var="a_mode" value=$atone_mode}
{assign var="op1" value=$opreg}
{assign var="op2" value=$opupdates}
{assign var="op3" value=$oppro}

{if $a_mode == "N"}
<script src="https://ct-auth.a-to-ne.jp/v1/atone.js"></script>
{else}
<script src="https://auth.atone.be/v1/atone.js"></script>
{/if}

<script type="text/javascript">

    var data = {$atone_datas nofilter};
    var pre = "{$pre_token_key|escape:javascript}";
    var pub_key = "{$ap_pub_key|escape:javascript}";
    
    var text_transaction_cancelled = "{__('text_transaction_cancelled')|escape:'javascript'}";
    var atone_error_alert = "{__('atone_error_alert')|escape:'javascript'}";
    var atone_order_status = "{__('atone_order_status')|escape:'javascript'}";
    var agreement_alert = "{__('checkout_terms_n_conditions_alert')|escape:'javascript'}";
    var warning = "{__('warning')|escape:'javascript'}";
    
    {literal}
    data["shop_transaction_no"] = "shop-tran-no-" + (new Date().getTime()).toString();
    {/literal}
    
    {if $op1 || $op2 || $op3}data["transaction_options"] = [];{/if}
    {if $op1 == 1}data["transaction_options"].push(1);{/if}
    {if $op2 == 2}data["transaction_options"].push(2);{/if}
    {if $op3 == 3}data["transaction_options"].push(3);{/if}
    
    {literal}
    
    function atone_modal(button,tap) {
    	button.addEventListener(tap, function(e) {
        	count = atone_varidate();
    		if(count != 0){
        		$('#litecheckout_payments_form').submit();
	    			return false;
    			}
           
				{/literal}
    			{if $config.tweaks.anti_csrf}
    				atonestart_cross(text_transaction_cancelled,atone_error_alert,atone_order_status);
    			{else}
    				atonestart(text_transaction_cancelled,atone_error_alert,atone_order_status);
    			{/if}
    			{literal}
           
    	}, false)
    }
    
    (function (_, $) {
    
    	if($('#atone-button').length){
	    	var button = document.getElementById("atone-button");
    		var tap = window.ontouchstart === null ? "touchstart" : "click";
    		atone_modal(button,tap);
    	}
	    
    	$.ceEvent('on', 'ce.ajaxdone', function(){
    		if($('#atone-button').length){
    			var button = document.getElementById("atone-button");
    			var tap = window.ontouchstart === null ? "touchstart" : "click";
    			atone_modal(button,tap);
        	}
		});
	
	})(Tygh, Tygh.$);
	
	{/literal}
</script>
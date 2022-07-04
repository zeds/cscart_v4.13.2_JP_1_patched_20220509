function atonestart_cross(text_transaction_cancelled,atone_error_alert,atone_order_status) {
	(function(_, $) {
	  $.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout&security_hash=' + _.security_hash),
              data: {transaction_no: data["shop_transaction_no"]}
      });

      Atone.config({
        pre_token: pre,
        pub_key: pub_key,
        terminal_id: '4000000400',
        payment: data,

        authenticated: function(authentication_token, user_no) {
        	$.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout&security_hash=' + _.security_hash),
              data: {pre_token: authentication_token}
      		});

          Atone.merge({"pre_token": authentication_token});
        },

        cancelled: function() {
			var title = _.tr('notice');
			var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-warning"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + text_transaction_cancelled + '</div>';
			document.getElementById('atone-alert').innerHTML = notification;
        },

        failed: function(response) {
			var title = _.tr('error');
			var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-error"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_error_alert + '</div>';
			document.getElementById('atone-alert').innerHTML = notification;
        },

        succeeded: function(response) {
          $.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout&security_hash=' + _.security_hash),
              data: {transaction_id: response["id"]},
              success: function(data) {
					var title = _.tr('notice');
					var notification = '<div style="margin-right: 50px;" class="cm-notification-content notification-content cm-auto-hide alert-success"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_order_status + '</div>';
					document.getElementById('atone-alert').innerHTML = notification;
					document.getElementById("place_order").click();  
              },
              error:function() {
					var title = _.tr('error');
					var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-error"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_error_alert + '</div>';
					document.getElementById('atone-alert').innerHTML = notification;
              }
          });
  
        },
      }, Atone.start);
    }(Tygh, Tygh.$));
};

function atonestart(text_transaction_cancelled,atone_error_alert,atone_order_status) {
	(function(_, $) {
	  $.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout'),
              data: {transaction_no: data["shop_transaction_no"]}
      });
            
      Atone.config({
        pre_token: pre,
        pub_key: pub_key,
        terminal_id: '4000000400',
        payment: data,

        authenticated: function(authentication_token, user_no) {
        	$.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout'),
              data: {pre_token: authentication_token}
      		});

          Atone.merge({"pre_token": authentication_token});
        },

        cancelled: function() {
			var title = _.tr('notice');
			var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-warning"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + text_transaction_cancelled + '</div>';
			document.getElementById('atone-alert').innerHTML = notification;
        },

        failed: function(response) {
			var title = _.tr('error');
			var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-error"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_error_alert + '</div>';
			document.getElementById('atone-alert').innerHTML = notification;
        },

        succeeded: function(response) {
          $.ajax({
              type: 'POST',
              url : fn_url('checkout.checkout'),
              data: {transaction_id: response["id"]},
              success: function(data) {
					var title = _.tr('notice');
					var notification = '<div style="margin-right: 50px;" class="cm-notification-content notification-content cm-auto-hide alert-success"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_order_status + '</div>';
					document.getElementById('atone-alert').innerHTML = notification;
					document.getElementById("place_order").click();  
              },
              error:function() {
					var title = _.tr('error');
					var notification = '<div style="margin-right: 50px;" class="cm-auto-hide cm-notification-content notification-content alert alert-error"><button type="button" class="close cm-notification-close " data-dismiss="alert">×</button><strong>' + title + '</strong>' + atone_error_alert + '</div>';
					document.getElementById('atone-alert').innerHTML = notification;
              }
          });
  
        },
      }, Atone.start);
     }(Tygh, Tygh.$));
};

function atone_varidate() {

	let count = 0;
    $('#litecheckout_payments_form > .litecheckout__group').find('input,select,textarea').each(function () {

		if($('#sw_billing_address_suffix_no').prop('checked')){

        	if ($(this).closest('.litecheckout__field').children('label.cm-required').length && !$(this).hasClass('cm-skip-avail-switch')) {
			
            	if ($(this).attr('type') === 'checkbox' && !$(this).prop('checked')) {
                	count++;

            	} else if ($(this).attr('type') === 'radio') {
                	let names = $(this).attr('name');
                	if(!$('input:radio[name="'+names+'"]:checked').val()){
                    	count++;
                	}

            	} else if ($(this).val() === '') {
                	count++;
            	}
        	}       	
        
        }else{
        
        	if ($(this).closest('.litecheckout__field').children('label.cm-required').length && !$(this).hasClass('cm-skip-avail-switch') && !$(this).parents('#billing_address').length) {
			
            	if ($(this).attr('type') === 'checkbox' && !$(this).prop('checked')) {
                	count++;

            	} else if ($(this).attr('type') === 'radio') {
                	let names = $(this).attr('name');
                	if(!$('input:radio[name="'+names+'"]:checked').val()){
                    	count++;
                	}

            	} else if ($(this).val() === '') {
                	count++;
            	}
        	}
        
        }
    });
    
    if($('.cm-check-agreement').length){
    	if(!$(".cm-agreement").prop('checked')) {
    		count++;
    	}
    }

    return count;
    
};
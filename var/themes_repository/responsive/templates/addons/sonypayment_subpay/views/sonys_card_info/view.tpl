{* $Id: view.tpl by takahashi from cs-cart.jp 2019 *}

{if $sonys_registered_card}

<table cellpadding="0" cellspacing="0" width="100%" border="0">
<tr valign="top">
	<td>
		{if $sonys_registered_card.card_number}
		<div class="sonys_card_info">
			<label>{__("card_number")} : {$sonys_registered_card.card_number}</label>
		</div>
		{/if}
		{if $sonys_registered_card.card_exp}
        <div class="sonys_card_info">
			<label>{__("expiry_date")} : {$sonys_registered_card.card_exp}</label>
		</div>
		{/if}
	</td>
</tr>
</table>
<div class="buttons-container">
	{include file="buttons/button.tpl" but_text=__("jp_sonys_delete_card_info") but_href="sonys_card_info.delete" but_meta="cm-confirm"}
</div>
	<br/>
	<label class="ty-control-group__title">{__("jp_sonys_update_card_info")}</label>
	{assign var="btn_txt" value=__("update")}
{else}
	<p class="no-items">{__("jp_sonys_no_card_info")}</p>
	<br/>
	<label class="ty-control-group__title">{__("jp_sonys_register_card_info")}</label>
	{assign var="btn_txt" value=__("create")}
{/if}

{capture name="mainbox_title"}{__("jp_sonys_registered_card")}{/capture}

{script src="js/lib/creditcardvalidator_jp/jquery.numeric.min.js"}
{script src="js/lib/creditcardvalidator_jp/jquery.creditCardValidator.js"}

{assign var=process_data value=fn_sonys_get_processor_data()}

<form id='jp_user_info_form' action="{"sonys_card_info.update"|fn_url}" method="post" name="user_info_form" class="form-horizontal form-edit  cm-disable-empty-files" enctype="multipart/form-data">

<div class="clearfix">
	<div class="ty-credit-card">
		<div class="ty-credit-card__control-group ty-control-group">
			<label for="credit_card_number_{$id_suffix}" class="ty-control-group__title cm-required cm-cc-number cc-number_{$id_suffix} cm-cc-number-check-length-jp cc-numeric">{__("card_number")}</label>
			<input size="35" type="tel" id="credit_card_number_{$id_suffix}" data-name="payment_info[card_number]" value="" class="ty-credit-card__input cm-focus cm-autocomplete-off cc-numeric cc-henkan" />
			<ul class="ty-cc-icons cm-cc-icons cc-icons_{$id_suffix}">
				<li class="ty-cc-icons__item cc-default cm-cc-default"><span class="ty-cc-icons__icon default">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-visa"><span class="ty-cc-icons__icon visa">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-visa_electron"><span class="ty-cc-icons__icon visa-electron">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-mastercard"><span class="ty-cc-icons__icon mastercard">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-maestro"><span class="ty-cc-icons__icon maestro">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-amex"><span class="ty-cc-icons__icon american-express">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-discover"><span class="ty-cc-icons__icon discover">&nbsp;</span></li>
				<li class="ty-cc-icons__item cm-cc-jcb"><span class="ty-cc-icons__icon jcb">&nbsp;</span></li>
			</ul>
		</div>

		<div class="ty-credit-card__control-group ty-control-group">
			<label for="credit_card_month_{$id_suffix}" class="cm-required ty-control-group__title cm-cc-date cc-date_{$id_suffix} cm-cc-exp-month cm-cc-exp-month-jp">{__("valid_thru")}</label>
			<label for="credit_card_year_{$id_suffix}" class="cm-cc-date cm-cc-exp-year cc-year_{$id_suffix} cm-cc-exp-year-jp hidden"></label>
			<input type="tel" id="credit_card_month_{$id_suffix}" data-name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;&nbsp;/&nbsp;&nbsp;
			<input type="tel" id="credit_card_year_{$id_suffix}"  data-name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="ty-credit-card__input-short cc-numeric cc-henkan cm-autocomplete-off" />&nbsp;
		</div>

		<div id="token_area"></div>
		{if $process_data.processor_params.use_cvv == 'true'}
			<div class="ty-credit-card__control-group ty-control-group">
				<label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-integer cm-autocomplete-off">{__("jp_sonys_security_code")}</label>
				<input type="tel" id="credit_card_cvv2_{$id_suffix}" data-name="payment_info[cvv2]" value="" size="4" maxlength="4" class="cm-cc-cvv2 ty-credit-card__cvv-field-input cm-autocomplete-off cc-numeric cc-henkan" />

				<div class="ty-cvv2-about">
					<span class="ty-cvv2-about__title">{__("jp_sonys_cc_what_is_security_code")}</span>
					<div class="ty-cvv2-about__note">

						<div class="ty-cvv2-about__info mb30 clearfix">
							<div class="ty-cvv2-about__image">
								<img src="{$images_dir}/visa_cvv.png" alt="" />
							</div>
							<div class="ty-cvv2-about__description">
								<h5 class="ty-cvv2-about__description-title">{__("visa_card_discover")}</h5>
								<p>{__("credit_card_info")}</p>
							</div>
						</div>
						<div class="ty-cvv2-about__info clearfix">
							<div class="ty-cvv2-about__image">
								<img src="{$images_dir}/express_cvv.png" alt="" />
							</div>
							<div class="ty-cvv2-about__description">
								<h5 class="ty-cvv2-about__description-title">{__("american_express")}</h5>
								<p>{__("american_express_info")}</p>
							</div>
						</div>

					</div>
				</div>
			</div>
		{/if}

	</div>
</div>

<div class="buttons-container cm-confirm">
	{include file="buttons/button.tpl" but_name="dispatch[sonys_card_info.update]" but_text=$btn_txt}
</div>

</form>

<script>
	(function(_, $) {
		var ccFormId = '{$id_suffix}';
		var ccBrand = '';

		$(function () {
			//数値だけ受付
			$(".cc-numeric").numeric({
				negative: false
			});
		});


		//全角＞半角変換
		$('.cc-henkan').change(function(){
			var icons           = $('.cc-icons_' + ccFormId + ' li');
			var ccNumber        = $(".cc-number_" + ccFormId);
			var ccNumberInput   = $("#" + ccNumber.attr("for"));

			var text  = $(this).val();
			var hen = text.replace(/[Ａ-Ｚａ-ｚ０-９]/g,function(s){
				return String.fromCharCode(s.charCodeAt(0)-0xFEE0);
			});

			hen = hen.replace(/[^0-9]/g, "");
			$(this).val(hen);

			ccNumberInput.validateCreditCard(function(result) {
				if (result.card_type) {
					icons.removeClass('active');
					icons.filter(' .cm-cc-' + result.card_type.name).addClass('active');
				}
			});
		});


		$.ceEvent('on', 'ce.commoninit', function() {

			var icons           = $('.cc-icons_' + ccFormId + ' li');
			var ccNumber        = $(".cc-number_" + ccFormId);
			var ccNumberInput   = $("#" + ccNumber.attr("for"));
			var ccMax = 0;//カードブランド別最大数
			var ccCv2           = $(".cc-cvv2_" + ccFormId);

			//カード入力が表示されている時だけ
			if ($(ccNumberInput).is(':visible')) {
				ccNumberInput.validateCreditCard(function(result) {
					icons.removeClass('active');
					//カードブランド
					if (result.card_type) {
						var userInput = ccNumberInput.val();
						var userInputLenght = userInput.length;
						ccBrand = result.card_type.name;
						icons.filter(' .cm-cc-' + result.card_type.name).addClass('active');

						if(result.card_type.name == 'visa') {
							ccMax = result.card_type.valid_length[3];//最大数
						}else{
							ccMax = result.card_type.valid_length[0];//最大数
						}

						if(userInputLenght >= ccMax){
							userInput = userInput.substring(0, ccMax);//許容文字数にする
							ccNumberInput.val(userInput);	//フォーム文に許容文字数を設定
						}

						if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
							ccCv2.removeClass("cm-required");
						} else {
							ccCv2.addClass("cm-required");
						}
					}
				});
			}

		});


		//カード有効期限チェック YY（YYの方だけで監視する）
		$.ceFormValidator('registerValidator', {
			class_name: 'cm-cc-exp-year-jp',
			message: _.tr('error_validator_cc_exp_jp'),
			func: function(id) {
				var input = $('#' + id);
				var flag = false;//RETURN

				var yy_val = input.val();
				var mm_val = $('#credit_card_month_{$id_suffix}').val();

				if(yy_val.length == 2 && mm_val.length == 2){
					flag = check_exp_date(yy_val, mm_val);
				}
				return flag;
			}
		});



		//カード長さチェック
		$.ceFormValidator('registerValidator', {
			class_name: 'cm-cc-number-check-length-jp',
			message: _.tr('error_validator_cc_check_length_jp'),
			func: function(id) {
				var input = $('#' + id);
				var flag = false;//RETURN
				var valid_length = 16;
				$(input).validateCreditCard(function(result) {
					if (result.card_type) {
						//桁数
						if(result.card_type.name == 'amex'){
							valid_length = 15;
						}else if(result.card_type.name == 'diners_club_international') {
							valid_length = 14;
						}

						if(input.val().length == valid_length){
							flag = true;
						}else{
							flag = false;
						}
					}
				});
				//カードイメージの調整
				if(!flag){
					$('.ty-cc-icons').attr('style', 'bottom: 45px');
				}else{
					$('.ty-cc-icons').attr('style', 'bottom: 23px');
				}

				//テストモードの場合は常にTRUE
				{if $payment_method.processor_params.mode == "test"}
				if(input.val().length > 0) {
					$('.ty-cc-icons').attr('style', 'bottom: 23px');
				}
				flag = true;
				{/if}

				return flag;
			}
		});
	})(Tygh, Tygh.$);

	var checkoutForm = $('#jp_user_info_form');
	checkoutForm.off('submit', submitHandler);
	checkoutForm.on('submit', submitHandler);

	// チェックアウトフォームのsubmitイベントハンドラ
	function submitHandler(event) {
		event.preventDefault();

		// フォームのValidationチェック
		var isFormVal = checkoutForm.ceFormValidator('check');

		cardNoVal = document.getElementById('credit_card_number_{$id_suffix}').value.replace(/\s+/g, "");
		cardYearVaal = document.getElementById('credit_card_year_{$id_suffix}').value;
		cardMonthVal = document.getElementById('credit_card_month_{$id_suffix}').value;

		var scCdObj = document.getElementById('credit_card_cvv2_{$id_suffix}');

		// セキュリティコードが表示されている場合
		if(scCdObj) {
			scCdVal = scCdObj.value;
		}

		// フォームのValidationがOKの場合
		if(isFormVal){
			SpsvApi.spsvCreateToken(cardNoVal, cardYearVaal, cardMonthVal, scCdVal, '', '', '', '', '');
		}
	}

	function setToken(token, card) {
		document.getElementById('credit_card_number_{$id_suffix}').value = '';
		document.getElementById('credit_card_year_{$id_suffix}').value = '';
		document.getElementById('credit_card_month_{$id_suffix}').value = '';

		var scCdObj = document.getElementById('credit_card_cvv2_{$id_suffix}');
		// セキュリティコードが表示されている場合
		if(scCdObj) {
			scCdObj.value = '';
		}

		// トークン用のhiddenタグを生成
		document.getElementById('token_area').innerHTML += "<input type='hidden' value='" + token + "' id='token' name=payment_info[token][] />";

		checkoutForm.get(0).submit();
	}

	//現在YYMM
	function get_now_yymm() {
		var now = new Date();
		var y = now.getFullYear();
		var m = now.getMonth() + 1;
		var yy = y.toString().slice(-2);
		var mm = ("0" + m).slice(-2);

		return yy + mm;
	}

	//有効期限が今月より未来か
	function check_exp_date(yy, mm) {
		var yymm_val = yy + mm;
		var now_yymm = get_now_yymm();
		//var now_mm = get_now_yymm("mm");
		if(mm < 13 && yymm_val >= now_yymm){
			return true;
		}else{
			return false;
		}
		return true;
	}
</script>

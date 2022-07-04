{* $Id: view.tpl by takahashi from cs-cart.jp 2018 *}

{script src="js/addons/kuroneko_kakebarai/jquery.numeric.min.js"}
<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>

{if $buyer.buyerName}

	<div class="control-group">
		<label for="buyer_id" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_cid")}</label>
		<div class="controls" id="buyer_id">
			<p>{$buyer.buyerId}</p>
		</div>
	</div>

	<div class="control-group">
		<label for="buyer_name" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_buyer_name")}</label>
		<div class="controls" id="buyer_name">
			<p>{$buyer.buyerName}</p>
		</div>
	</div>

	<div class="control-group">
		<label for="judge_status" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_judge_status")}</label>
		<div class="controls" id="judge_status">
			<p>{$buyer.judgeStatus}</p>
		</div>
	</div>

	{capture name="mainbox_title"}{__("jp_kuroneko_kakebarai_user_info_ref")}{/capture}
{else}

	<form id='form' action="{""|fn_url}" method="post" name="user_info_form" class="form-horizontal form-edit  cm-disable-empty-files" enctype="multipart/form-data">

		{$_country = $settings.General.default_country}

		{** 取引先情報エリア **}

		<div>
			{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_buyer_info")}

			<div class="ty-control-group">
				<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_cid")}</label>
				<p>{$buyer.cId}</p>
			</div>

			<div class="ty-control-group">
				<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_hjkjkbn")}</label>
				<label class="radio inline" for="hjkjkbn_1"><input type="radio" name="buyer[hjkjKbn]" id="hjkjkbn_1" {if $buyer.hjkjKbn == "1"}checked="checked"{/if} value="1" onclick="fn_check_hjkjkbn(this.checked, '1');"/>{__("jp_kuroneko_kakebarai_hjkjkbn_1")}</label>
				<label class="radio inline" for="hjkjkbn_2"><input type="radio" name="buyer[hjkjKbn]" id="hjkjkbn_2" {if $buyer.hjkjKbn == "2"}checked="checked"{/if} value="2" onclick="fn_check_hjkjkbn(this.checked, '2');"/>{__("jp_kuroneko_kakebarai_hjkjkbn_2")}</label>
			</div>

			<div id="group_hjkjkbn_1" class="{if $buyer.hjkjKbn != "1"}hidden{/if}">
				<div class="ty-control-group">
					<label for="houjinkaku" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_houjinkaku")}</label>
					<select id="houjinkaku" name="buyer[houjinKaku]">
						{for $item=1 to 20}
							<option value="{$item}" {if $buyer.houjinKaku==$item}selected{/if}>{__("jp_kuroneko_kakebarai_houjinkaku_`$item`")}</option>
						{/for}
					</select>
				</div>

				<div class="ty-control-group">
					<label for="houjinzengo" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_houjinzengo")}</label>
					<select id="houjinzengo" name="buyer[houjinZengo]">
						{for $item=0 to 1}
							<option value="{$item}" {if $buyer.houjinZengo==$item}selected{/if}>{__("jp_kuroneko_kakebarai_houjinzengo_`$item`")}</option>
						{/for}
					</select>
				</div>
			</div>

			<div class="ty-control-group">
				<label for="smei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_smei")}</label>
				<input type="text" id="smei" name="buyer[sMei]" size="32" maxlength="30" value="{$buyer.sMei}" class="ty-input-text cm-focus" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_smei")}&nbsp;&nbsp;<span class="ty-error-text">{__("jp_kuroneko_kakebarai_sample_smei_warning")}</span></span>
			</div>

			<div class="ty-control-group">
				<label for="shitenmei" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_shitenmei")}</label>
				<input type="text" id="shitenmei" name="buyer[shitenMei]" size="32" maxlength="30" value="{$buyer.shitenMei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_shitenmei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="smeikana" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_smeikana")}</label>
				<input type="text" id="smeikana" name="buyer[sMeikana]" size="64" maxlength="60" value="{$buyer.sMeikana}" class="ty-input-texts" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_smeikana")}&nbsp;&nbsp;<span class="ty-error-text">{__("jp_kuroneko_kakebarai_sample_smei_warning")}</span></span>
			</div>

			<div class="ty-control-group">
				<label for="shitenmeikana" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_shitenmeikana")}</label>
				<input type="text" id="shitenmeikana" name="buyer[shitenMeikana]" size="64" maxlength="60" value="{$buyer.shitenMeikana}" class="ty-input-texts" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_shitenmeikana")}</span>
			</div>

			<div class="ty-control-group">
				<label for="ybnno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_ybnno")}</label>
				<input type="tel" id="ybnno" name="buyer[ybnNo]" size="10" maxlength="7" value="{$buyer.ybnNo}" class="ty-input-text cc-numeric" onKeyUp="AjaxZip3.zip2addr(this,'','buyer[Adress_state]','buyer[Adress_city]','','buyer[Adress_address]');"/>
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_ybnno")}</span>
			</div>

			<div class="ty-control-group">
				<label for="adress_state" class="ty-control-group__title cm-required">{__("state")}</label>
				<select id="adress_state" name="buyer[Adress_state]">
					<option value="">- {__("select_state")} -</option>
					{if $states && $states.$_country}
						{foreach from=$states.$_country item=state}
							<option {if $buyer.Adress_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
						{/foreach}
					{/if}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="adress_city" class="ty-control-group__title cm-required">{__("city")}</label>
				<input type="text" id="adress_city" name="buyer[Adress_city]" size="54" maxlength="50" value="{$buyer.Adress_city}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_city")}</span>
			</div>

			<div class="ty-control-group">
				<label for="adress_address" class="ty-control-group__title cm-required">{__("address")}</label>
				<input type="text" id="adress_address" name="buyer[Adress_address]" size="54" maxlength="50" value="{$buyer.Adress_address}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_address")}</span>
			</div>

			<div class="ty-control-group">
				<label for="adress_address2" class="ty-control-group__title">{__("address_2")}</label>
				<input type="text" id="adress_address2" name="buyer[Adress_address_2]" size="54" maxlength="50" value="{$buyer.Adress_address_2}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_address2")}</span>
			</div>

			<div class="ty-control-group">
				<label for="telno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_telno")}</label>
				<input type="tel" id="telno" name="buyer[telNo]" size="15" maxlength="13" value="{$buyer.telNo}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_telno")}</span>
			</div>

			<div class="ty-control-group">
				<label for="keitaino" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_keitaino")}</label>
				<input type="tel" id="keitaino" name="buyer[keitaiNo]" size="15" maxlength="13" value="{$buyer.keitaiNo}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_keitaino")}</span>
			</div>

			<div class="ty-control-group">
				<label for="gyscod1" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_gyscod1")}</label>
				<select id="gyscod1" name="buyer[gyscod1]" onchange="fn_set_gyscod2(this.value)">
					<option value="">- {__("please_select_one")} -</option>
					{for $item=7 to 92}
						{assign var="no" value=substr('0'|cat:$item, -2)}
						{if __("jp_kuroneko_kakebarai_gyscod1_`$no`") != "_jp_kuroneko_kakebarai_gyscod1_`$no`"}
							<option value="{$no}" {if $buyer.gyscod1==$no}selected{/if}>{__("jp_kuroneko_kakebarai_gyscod1_`$no`")}</option>
						{/if}
					{/for}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="gyscod2" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_gyscod2")}</label>
				<select id="gyscod2" name="buyer[gyscod2]">
					<option value="">- {__("please_select_one")} -</option>

					{for $item=782 to 9299}
						{assign var="no" value=substr('0'|cat:$item, -4)}
						{if $buyer.gyscod1 == $no|substr:0:2 && __("jp_kuroneko_kakebarai_gyscod2_`$no`") != "_jp_kuroneko_kakebarai_gyscod2_`$no`"}
							<option value="{$no}" {if $buyer.gyscod2==$no}selected{/if}>{__("jp_kuroneko_kakebarai_gyscod2_`$no`")}</option>
						{/if}
					{/for}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="setsuritungt" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_setsuritungt")}</label>
				<input type="tel" id="setsuritungt" name="buyer[setsurituNgt]" size="8" maxlength="6" value="{$buyer.setsurituNgt}" class="ty-input-text cc-numeric" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_setsuritungt")}</span>
			</div>

			<div id="group_shk" class="{if $buyer.hjkjKbn != "1"}hidden{/if}">
				<div class="ty-control-group">
					<label for="shk" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_shk")}</label>
					<input type="tel" id="shk" name="buyer[shk]" size="6" maxlength="5" value="{$buyer.shk}" class="ty-input-text cc-numeric" />
					&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_shk")}</span>
				</div>
			</div>

			<div class="ty-control-group">
				<label for="nsyo" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_nsyo")}</label>
				<input type="tel" id="nsyo" name="buyer[nsyo]" size="6" maxlength="5" value="{$buyer.nsyo}" class="ty-input-text cc-numeric" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_nsyo")}</span>
			</div>

			<div class="ty-control-group">
				<label for="kmssyainsu" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_kmssyainsu")}</label>
				<input type="tel" id="kmssyainsu" name="buyer[kmssyainsu]" size="6" maxlength="5" value="{$buyer.kmssyainsu}" class="ty-input-text cc-numeric" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_kmssyainsu")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daikjmeisei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_daikjmeisei")}</label>
				<input type="text" id="daikjmeisei" name="buyer[daikjmeiSei]" size="16" maxlength="14" value="{$buyer.daikjmeiSei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_daikjmeisei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daikjmeimei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_daikjmeimei")}</label>
				<input type="text" id="daikjmeimei" name="buyer[daikjmeiMei]" size="16" maxlength="14" value="{$buyer.daikjmeiMei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_daikjmeimei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daiknameisei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_daiknameisei")}</label>
				<input type="text" id="daiknameisei" name="buyer[daiknameiSei]" size="30" maxlength="29" value="{$buyer.daiknameiSei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_daiknameisei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daiknameimei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_daiknameimei")}</label>
				<input type="text" id="daiknameimei" name="buyer[daiknameiMei]" size="30" maxlength="29" value="{$buyer.daiknameiMei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_daiknameimei")}</span>
			</div>
		</div>

		{** 代表者情報エリア **}

		<div id="jp_kuroneko_kakebarai_daiinfo" class="{if $buyer.hjkjKbn != "2"}hidden{/if}">
			{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_daiinfo")}

			<div class="ty-control-group">
				<label for="daiybnno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_daiybnno")}</label>
				<input type="tel" id="daiybnno" name="buyer[daiYbnno]" size="10" maxlength="7" value="{$buyer.daiYbnno}" class="ty-input-text cc-numeric" onKeyUp="AjaxZip3.zip2addr(this,'','buyer[daiAddress_state]','buyer[daiAddress_city]','','buyer[daiAddress_address]');"/>
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_ybnno")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daiaddress_state" class="ty-control-group__title cm-required">{__("state")}</label>
				<select id="daiaddress_state" name="buyer[daiAddress_state]">
					<option value="">- {__("select_state")} -</option>
					{if $states && $states.$_country}
						{foreach from=$states.$_country item=state}
							<option {if $buyer.daiAddress_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
						{/foreach}
					{/if}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="daiaddress_city" class="ty-control-group__title cm-required">{__("city")}</label>
				<input type="text" id="daiaddress_city" name="buyer[daiAddress_city]" size="54" maxlength="50" value="{$buyer.daiAddress_city}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_city")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daiaddress_address" class="ty-control-group__title cm-required">{__("address")}</label>
				<input type="text" id="daiaddress_address" name="buyer[daiAddress_address]" size="54" maxlength="50" value="{$buyer.daiAddress_address}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_address")}</span>
			</div>

			<div class="ty-control-group">
				<label for="daiaddress_address2" class="ty-control-group__title">{__("address_2")}</label>
				<input type="text" id="daiaddress_address2" name="buyer[daiAddress_address_2]" size="54" maxlength="50" value="{$buyer.daiAddress_address_2}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_address2")}</span>
			</div>
		</div>

		<div class="ty-control-group">
			<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_szumu")}</label>
			<label class="radio inline" for="szumu_0"><input type="radio" name="buyer[szUmu]" id="szumu_0" {if $buyer.szUmu == "0"}checked="checked"{/if} value="0" onclick="fn_check_szumu(this.checked, '0');"/>{__("jp_kuroneko_kakebarai_szumu_0")}</label>
			<label class="radio inline" for="szumu_1"><input type="radio" name="buyer[szUmu]" id="szumu_1" {if $buyer.szUmu == "1"}checked="checked"{/if} value="1" onclick="fn_check_szumu(this.checked, '1');"/>{__("jp_kuroneko_kakebarai_szumu_1")}</label>
		</div>

		{** 運営会社情報エリア **}

		<div id="jp_kuroneko_kakebarai_szinfo" class="{if $buyer.szumu != "1"}hidden{/if}">
			{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_szinfo")}

			<div class="ty-control-group">
				<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_szhjkjkbn")}</label>
				<label class="radio inline" for="szhjkjkbn_1"><input type="radio" name="buyer[szHjkjKbn]" id="szhjkjkbn_1" {if $buyer.szHjkjKbn == "1"}checked="checked"{/if} value="1" onclick="fn_check_szhjkjkbn(this.checked, '1');"/>{__("jp_kuroneko_kakebarai_hjkjkbn_1")}</label>
				<label class="radio inline" for="szhjkjkbn_2"><input type="radio" name="buyer[szHjkjKbn]" id="szhjkjkbn_2" {if $buyer.szHjkjKbn == "2"}checked="checked"{/if} value="2" onclick="fn_check_szhjkjkbn(this.checked, '2');"/>{__("jp_kuroneko_kakebarai_hjkjkbn_2")}</label>
			</div>

			<div id="group_szhjkjkbn_1" class="{if $buyer.szHjkjKbn != "1"}hidden{/if}">
				<div class="ty-control-group">
					<label for="szhoujinkaku" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szhoujinkaku")}</label>
					<select id="szhoujinkaku" name="buyer[szHoujinKaku]">
						{for $item=1 to 20}
							<option value="{$item}" {if $buyer.szHoujinKaku==$item}selected{/if}>{__("jp_kuroneko_kakebarai_houjinkaku_`$item`")}</option>
						{/for}
					</select>
				</div>

				<div class="ty-control-group">
					<label for="szhoujinzengo" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szhoujinzengo")}</label>
					<select id="szhoujinzengo" name="buyer[szHoujinZengo]">
						{for $item=0 to 1}
							<option value="{$item}" {if $buyer.szHoujinZengo==$item}selected{/if}>{__("jp_kuroneko_kakebarai_houjinzengo_`$item`")}</option>
						{/for}
					</select>
				</div>
			</div>

			<div class="ty-control-group">
				<label for="szhonknjmei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szhonknjmei")}</label>
				<input type="text" id="szhonknjmei" name="buyer[szHonknjmei]" size="32" maxlength="30" value="{$buyer.szHonknjmei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szhonknjmei")}&nbsp;&nbsp;<span class="ty-error-text">{__("jp_kuroneko_kakebarai_sample_smei_warning")}</span></span>
			</div>

			<div class="ty-control-group">
				<label for="szhonknamei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szhonknamei")}</label>
				<input type="text" id="szhonknamei" name="buyer[szHonknamei]" size="64" maxlength="60" value="{$buyer.szHonknamei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szhonknamei")}&nbsp;&nbsp;<span class="ty-error-text">{__("jp_kuroneko_kakebarai_sample_smei_warning")}</span></span>
			</div>

			<div class="ty-control-group">
				<label for="szybnno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szybnno")}</label>
				<input type="tel" id="szybnno" name="buyer[szYbnno]" size="10" maxlength="7" value="{$buyer.szYbnno}" class="ty-input-text cc-numeric" onKeyUp="AjaxZip3.zip2addr(this,'','buyer[szAddress_state]','buyer[szAddress_city]','','buyer[szAddress_address]');"/>
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szybnno")}&nbsp;&nbsp;<span class="ty-error-text">{__("jp_kuroneko_kakebarai_sample_szybnno_warning")}</span></span>
			</div>

			<div class="ty-control-group">
				<label for="szaddress_state" class="ty-control-group__title cm-required">{__("state")}</label>
				<select id="szaddress_state" name="buyer[szAddress_state]">
					<option value="">- {__("select_state")} -</option>
					{if $states && $states.$_country}
						{foreach from=$states.$_country item=state}
							<option {if $buyer.szAddress_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
						{/foreach}
					{/if}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="szaddress_city" class="ty-control-group__title cm-required">{__("city")}</label>
				<input type="text" id="szaddress_city" name="buyer[szAddress_city]" size="54" maxlength="50" value="{$buyer.szAddress_city}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szaddress_city")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szaddress_address" class="ty-control-group__title cm-required">{__("address")}</label>
				<input type="text" id="szaddress_address" name="buyer[szAddress_address]" size="54" maxlength="50" value="{$buyer.szAddress_address}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szaddress_address")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szaddress_address2" class="ty-control-group__title">{__("address_2")}</label>
				<input type="text" id="szaddress_address2" name="buyer[szAddress_address_2]" size="54" maxlength="50" value="{$buyer.szAddress_address_2}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szaddress_address2")}</span>
			</div>

			<div class="ty-control-group">
				<label for="sztelno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_sztelno")}</label>
				<input type="tel" id="sztelno" name="buyer[szTelno]" size="15" maxlength="13" value="{$buyer.szTelno}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_telno")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szdaikjmei_sei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szdaikjmei_sei")}</label>
				<input type="text" id="szdaikjmei_sei" name="buyer[szDaikjmei_sei]" size="16" maxlength="14" value="{$buyer.szDaikjmei_sei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szdaikjmei_sei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szdaikjmei_mei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szdaikjmei_mei")}</label>
				<input type="text" id="szdaikjmei_mei" name="buyer[szDaikjmei_mei]" size="16" maxlength="14" value="{$buyer.szDaikjmei_mei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szdaikjmei_mei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szdaiknamei_sei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szdaiknamei_sei")}</label>
				<input type="text" id="szdaiknamei_sei" name="buyer[szDaiknamei_sei]" size="30" maxlength="29" value="{$buyer.szDaiknamei_sei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szdaiknamei_sei")}</span>
			</div>

			<div class="ty-control-group">
				<label for="szdaiknamei_mei" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_szdaiknamei_mei")}</label>
				<input type="text" id="szdaiknamei_mei" name="buyer[szDaiknamei_mei]" size="30" maxlength="29" value="{$buyer.szDaiknamei_mei}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_szdaiknamei_mei")}</span>
			</div>
		</div>

		<div class="ty-control-group">
			<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_sqssfkbn")}</label>
			<label class="radio inline" for="sqssfkbn_1"><input type="radio" name="buyer[sqssfKbn]" id="sqssfkbn_1" {if $buyer.sqssfKbn == "1"}checked="checked"{/if} value="1" onclick="fn_check_sqssfkbn(this.checked, '1');"/>{__("jp_kuroneko_kakebarai_sqssfkbn_1")}</label>
			<label class="radio inline" for="sqssfkbn_2"><input type="radio" name="buyer[sqssfKbn]" id="sqssfkbn_2" {if $buyer.sqssfKbn == "2"}checked="checked"{/if} value="2" onclick="fn_check_sqssfkbn(this.checked, '2');"/>{__("jp_kuroneko_kakebarai_sqssfkbn_2")}</label>
			<label class="radio inline" for="sqssfkbn_9"><input type="radio" name="buyer[sqssfKbn]" id="sqssfkbn_9" {if $buyer.sqssfKbn == "9"}checked="checked"{/if} value="9" onclick="fn_check_sqssfkbn(this.checked, '9');"/>{__("jp_kuroneko_kakebarai_sqssfkbn_9")}</label>
		</div>

		{** 請求書送付先情報エリア **}

		<div id="jp_kuroneko_kakebarai_sqssfinfo" class="{if $buyer.sqssfKbn != "9"}hidden{/if}">
			{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_sqssfinfo")}

			<div class="ty-control-group">
				<label for="sqybnno" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_sqybnno")}</label>
				<input type="tel" id="sqybnno" name="buyer[sqYbnno]" size="10" maxlength="7" value="{$buyer.sqYbnno}" class="ty-input-text cc-numeric" onKeyUp="AjaxZip3.zip2addr(this,'','buyer[sqAddress_state]','buyer[sqAddress_city]','','buyer[sqAddress_address]');"/>
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sqybnno")}</span>
			</div>

			<div class="ty-control-group">
				<label for="sqaddress_state" class="ty-control-group__title cm-required">{__("state")}</label>
				<select id="sqaddress_state" name="buyer[sqAddress_state]">
					<option value="">- {__("select_state")} -</option>
					{if $states && $states.$_country}
						{foreach from=$states.$_country item=state}
							<option {if $buyer.sqAddress_state == $state.code}selected="selected"{/if} value="{$state.code}">{$state.state}</option>
						{/foreach}
					{/if}
				</select>
			</div>

			<div class="ty-control-group">
				<label for="sqaddress_city" class="ty-control-group__title cm-required">{__("city")}</label>
				<input type="text" id="sqaddress_city" name="buyer[sqAddress_city]" size="54" maxlength="50" value="{$buyer.sqAddress_city}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sqaddress_city")}</span>
			</div>

			<div class="ty-control-group">
				<label for="sqaddress_address" class="ty-control-group__title cm-required">{__("address")}</label>
				<input type="text" id="sqaddress_address" name="buyer[sqAddress_address]" size="54" maxlength="50" value="{$buyer.sqAddress_address}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sqaddress_address")}</span>
			</div>

			<div class="ty-control-group">
				<label for="sqaddress_address2" class="ty-control-group__title">{__("address_2")}</label>
				<input type="text" id="sqaddress_address2" name="buyer[sqAddress_address_2]" size="54" maxlength="50" value="{$buyer.sqAddress_address_2}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sqaddress_address2")}</span>
			</div>

			<div class="ty-control-group">
				<label for="sofuknjnam" class="ty-control-group__title cm-required">{__("jp_kuroneko_kakebarai_sofuknjnam")}</label>
				<input type="text" id="sofuknjnam" name="buyer[sofuKnjnam]" size="32" maxlength="30" value="{$buyer.sofuKnjnam}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sofuknjnam")}</span>
			</div>

			<div class="ty-control-group">
				<label for="syz" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_syz")}</label>
				<input type="text" id="syz" name="buyer[syz]" size="12" maxlength="10" value="{$buyer.syz}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_syz")}</span>
			</div>

			<div class="ty-control-group">
				<label for="kmstelno" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_kmstelno")}</label>
				<input type="tel" id="kmstelno" name="buyer[kmsTelno]" size="15" maxlength="13" value="{$buyer.kmsTelno}" class="ty-input-text" />
				&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_telno")}</span>
			</div>
		</div>

		<div class="ty-control-group">
			<label for="sofutntnam" class="ty-control-group__title">{__("jp_kuroneko_kakebarai_sofutntnam")}</label>
			<input type="text" id="sofutntnam" name="buyer[sofuTntnam]" size="27" maxlength="25" value="{$buyer.sofuTntnam}" class="ty-input-text" />
			&nbsp;&nbsp;<span>{__("jp_kuroneko_kakebarai_sample_sofutntnam")}</span>
		</div>

		{** 基本情報エリア **}

		{include file="common/subheader.tpl" title=__("jp_kuroneko_kakebarai_basicinfo")}

		<div class="ty-control-group">
			<label class="ty-control-group__title">{__("jp_kuroneko_kakebarai_shrhohkbn")}</label>
			<label class="radio inline" for="shrhohkbn_2"><input type="radio" name="buyer[shrhohKbn]" id="shrhohkbn_2" {if $buyer.shrhohKbn == "2"}checked="checked"{/if} value="2"/>{__("jp_kuroneko_kakebarai_shrhohkbn_2")}</label>
			<label class="radio inline" for="shrhohkbn_8"><input type="radio" name="buyer[shrhohKbn]" id="shrhohkbn_8" {if $buyer.shrhohKbn == "8"}checked="checked"{/if} value="8"/>{__("jp_kuroneko_kakebarai_shrhohkbn_8")}</label>
			<label class="radio inline" for="shrhohkbn_9"><input type="radio" name="buyer[shrhohKbn]" id="shrhohkbn_9" {if $buyer.shrhohKbn == "9"}checked="checked"{/if} value="9"/>{__("jp_kuroneko_kakebarai_shrhohkbn_9")}</label>
		</div>

		<div class="buttons-container cm-confirm">
			{include file="buttons/button.tpl" but_name="dispatch[krnkkb_user_info.add]" but_text=__("jp_kuroneko_kakebarai_user_info_reg")}
		</div>

	</form>

	{capture name="mainbox_title"}{__("jp_kuroneko_kakebarai_user_info_reg")}{/capture}

	<script>
        (function ($) {
            $(document).ready(function () {
                // ラジオボタンの設定
                {if $buyer.hjkjKbn == "1"}
				$('#hjkjkbn_1').click();
				{else}
                $('#hjkjkbn_2').click();
                {/if}

				{if $buyer.szUmu == "1"}
                $('#szumu_1').click();
				{else}
                $('#szumu_0').click();
                {/if}

				{if $buyer.szHjkjKbn == "1"}
                $('#szhjkjkbn_1').click();
				{else}
                $('#szhjkjkbn_2').click();
                {/if}

				{if $buyer.sqssfKbn == "9"}
                $('#sqssfkbn_9').click();
				{elseif $buyer.sqssfKbn == "2"}
                $('#sqssfkbn_2').click();
				{else}
                $('#sqssfkbn_1').click();
                {/if}

				{if $buyer.shrhohKbn == "9"}
                $('#shrhohkbn_9').click();
				{elseif $buyer.shrhohKbn == "8"}
                $('#shrhohkbn_8').click();
				{else}
                $('#shrhohkbn_2').click();
                {/if}
            });
        })(jQuery);

        $(function () {
            //数値だけ受付
            $(".cc-numeric").numeric({
                negative: false
            });
        });

        function fn_check_hjkjkbn(ischecked, value)
        {
            if(ischecked) {
                if (value == '2') {
                    (function ($) {
                        $(document).ready(function () {
                            $('#group_hjkjkbn_1').switchAvailability(true);
                            $('#jp_kuroneko_kakebarai_daiinfo').switchAvailability(false);
							$('#group_shk').switchAvailability(true);
                        });
                    })(jQuery);
                } else {
                    (function ($) {
                        $(document).ready(function () {
                            $('#group_hjkjkbn_1').switchAvailability(false);
                            $('#jp_kuroneko_kakebarai_daiinfo').switchAvailability(true);
							$('#group_shk').switchAvailability(false);
                        });
                    })(jQuery);
                }
            }
        }

        function fn_check_szumu(ischecked, value)
        {
            if(ischecked) {
                if (value == '0') {
                    (function ($) {
                        $(document).ready(function () {
                            $('#jp_kuroneko_kakebarai_szinfo').switchAvailability(true);
                        });
                    })(jQuery);
                } else {
                    (function ($) {
                        $(document).ready(function () {
                            $('#jp_kuroneko_kakebarai_szinfo').switchAvailability(false);
                        });
                    })(jQuery);
                }
            }
        }

        function fn_check_szhjkjkbn(ischecked, value)
        {
            if(ischecked) {
                if (value == '2') {
                    (function ($) {
                        $(document).ready(function () {
                            $('#group_szhjkjkbn_1').switchAvailability(true);
                        });
                    })(jQuery);
                } else {
                    (function ($) {
                        $(document).ready(function () {
                            $('#group_szhjkjkbn_1').switchAvailability(false);
                        });
                    })(jQuery);
                }
            }
        }

        function fn_check_sqssfkbn(ischecked, value)
        {
            if(ischecked) {
                if (value != '9') {
                    (function ($) {
                        $(document).ready(function () {
                            $('#jp_kuroneko_kakebarai_sqssfinfo').switchAvailability(true);
                        });
                    })(jQuery);
                } else {
                    (function ($) {
                        $(document).ready(function () {
                            $('#jp_kuroneko_kakebarai_sqssfinfo').switchAvailability(false);
                        });
                    })(jQuery);
                }
            }
        }

        function fn_set_gyscod2(value)
        {
            (function ($) {
                $(document).ready(function () {

                    $('#gyscod2 option').remove();

                    switch(value){
                        case '07':
                            $('#gyscod2').append('<option value="0782">{__("jp_kuroneko_kakebarai_gyscod2_0782")}</option>');
                            $('#gyscod2').append('<option value="0799">{__("jp_kuroneko_kakebarai_gyscod2_0799")}</option>');
                            break;

                        case '15':
                            $('#gyscod2').append('<option value="1591">{__("jp_kuroneko_kakebarai_gyscod2_1591")}</option>');
                            $('#gyscod2').append('<option value="1599">{__("jp_kuroneko_kakebarai_gyscod2_1599")}</option>');

                            break;

                        case '50':
                            $('#gyscod2').append('<option value="5011">{__("jp_kuroneko_kakebarai_gyscod2_5011")}</option>');
                            $('#gyscod2').append('<option value="5099">{__("jp_kuroneko_kakebarai_gyscod2_5099")}</option>');

                            break;

                        case '57':
                            $('#gyscod2').append('<option value="5799">{__("jp_kuroneko_kakebarai_gyscod2_5799")}</option>');

                            break;

                        case '58':
                            $('#gyscod2').append('<option value="5831">{__("jp_kuroneko_kakebarai_gyscod2_5831")}</option>');
                            $('#gyscod2').append('<option value="5863">{__("jp_kuroneko_kakebarai_gyscod2_5863")}</option>');
                            $('#gyscod2').append('<option value="5899">{__("jp_kuroneko_kakebarai_gyscod2_5899")}</option>');

                            break;

                        case '59':
                            $('#gyscod2').append('<option value="5931">{__("jp_kuroneko_kakebarai_gyscod2_5931")}</option>');
                            $('#gyscod2').append('<option value="5999">{__("jp_kuroneko_kakebarai_gyscod2_5999")}</option>');

                            break;

                        case '60':
                            $('#gyscod2').append('<option value="6031">{__("jp_kuroneko_kakebarai_gyscod2_6031")}</option>');
                            $('#gyscod2').append('<option value="6032">{__("jp_kuroneko_kakebarai_gyscod2_6032")}</option>');
                            $('#gyscod2').append('<option value="6033">{__("jp_kuroneko_kakebarai_gyscod2_6033")}</option>');
                            $('#gyscod2').append('<option value="6064">{__("jp_kuroneko_kakebarai_gyscod2_6064")}</option>');
                            $('#gyscod2').append('<option value="6071">{__("jp_kuroneko_kakebarai_gyscod2_6071")}</option>');
                            $('#gyscod2').append('<option value="6093">{__("jp_kuroneko_kakebarai_gyscod2_6093")}</option>');
                            $('#gyscod2').append('<option value="6096">{__("jp_kuroneko_kakebarai_gyscod2_6096")}</option>');
                            $('#gyscod2').append('<option value="6099">{__("jp_kuroneko_kakebarai_gyscod2_6099")}</option>');

                            break;

                        case '76':
                            $('#gyscod2').append('<option value="7611">{__("jp_kuroneko_kakebarai_gyscod2_7611")}</option>');
                            $('#gyscod2').append('<option value="7629">{__("jp_kuroneko_kakebarai_gyscod2_7629")}</option>');
                            $('#gyscod2').append('<option value="7631">{__("jp_kuroneko_kakebarai_gyscod2_7631")}</option>');
                            $('#gyscod2').append('<option value="7641">{__("jp_kuroneko_kakebarai_gyscod2_7641")}</option>');
                            $('#gyscod2').append('<option value="7651">{__("jp_kuroneko_kakebarai_gyscod2_7651")}</option>');
                            $('#gyscod2').append('<option value="7661">{__("jp_kuroneko_kakebarai_gyscod2_7661")}</option>');
                            $('#gyscod2').append('<option value="7671">{__("jp_kuroneko_kakebarai_gyscod2_7671")}</option>');
                            $('#gyscod2').append('<option value="7699">{__("jp_kuroneko_kakebarai_gyscod2_7699")}</option>');

                            break;

                        case '83':
                            $('#gyscod2').append('<option value="8311">{__("jp_kuroneko_kakebarai_gyscod2_8311")}</option>');
                            $('#gyscod2').append('<option value="8321">{__("jp_kuroneko_kakebarai_gyscod2_8321")}</option>');
                            $('#gyscod2').append('<option value="8331">{__("jp_kuroneko_kakebarai_gyscod2_8331")}</option>');
                            $('#gyscod2').append('<option value="8361">{__("jp_kuroneko_kakebarai_gyscod2_8361")}</option>');
                            $('#gyscod2').append('<option value="8399">{__("jp_kuroneko_kakebarai_gyscod2_8399")}</option>');

                            break;

                        case '85':
                            $('#gyscod2').append('<option value="8549">{__("jp_kuroneko_kakebarai_gyscod2_8549")}</option>');
                            $('#gyscod2').append('<option value="8599">{__("jp_kuroneko_kakebarai_gyscod2_8599")}</option>');

                            break;

                        case '92':
                            $('#gyscod2').append('<option value="9221">{__("jp_kuroneko_kakebarai_gyscod2_9221")}</option>');
                            $('#gyscod2').append('<option value="9299">{__("jp_kuroneko_kakebarai_gyscod2_9299")}</option>');

                            break;
                    }

                });
            })(jQuery);
        }
	</script>
{/if}



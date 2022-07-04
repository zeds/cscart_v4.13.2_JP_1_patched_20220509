{* $Id: manage.tpl by takahashi from cs-cart.jp 2020 *}

{capture name="mainbox"}

	<form action="{""|fn_url}" method="post" target="_self" name="localization_jp_form" class="form-horizontal form-edit">

		{assign var="page_title" value=__("localization_jp")}

		{include file="common/subheader.tpl" title=__("jp_elm_prod_search") target="#jp_elm_prod_search"}
		<div id="jp_elm_prod_search" class="in collapse">
			<fieldset>
				<div class="control-group">
					<label for="jp_prod_search_mode" class="control-label">{__("jp_prod_search_mode")}:<i class="cm-tooltip icon-question-sign" title="{__("jp_prod_search_mode.tooltip")}"></i></label>
					<div class="controls">
						<select name="lcjp[jp_prod_search_mode]" id="jp_prod_search_mode">
							<option value="jp_prod_srch_all" {if $lcjp.jp_prod_search_mode == "jp_prod_srch_all"}selected="selected"{/if}>{__("jp_prod_srch_all")}</option>
							<option value="jp_prod_srch_any" {if ($lcjp.jp_prod_search_mode == "" && $addons.localization_jp.jp_prod_search_mode == "jp_prod_srch_any") || $lcjp.jp_prod_search_mode == "jp_prod_srch_any"}selected="selected"{/if}>{__("jp_prod_srch_any")}</option>
						</select>
					</div>
				</div>

			</fieldset>
		</div>

		{include file="common/subheader.tpl" title=__("jp_elm_checkout") target="#jp_elm_checkout"}
		<div id="jp_elm_checkout" class="in collapse">
			<fieldset>
				<div class="control-group">
					<label class="control-label" for="jp_checkout_fullname_mode">{__("jp_checkout_fullname_mode")}:<i class="cm-tooltip icon-question-sign" title="{__("jp_checkout_fullname_mode.tooltip")}"></i></label>
					<div class="controls">
						<select name="lcjp[jp_checkout_fullname_mode]" id="jp_checkout_fullname_mode">
							<option value="jp_checkout_fullname_yes" {if $lcjp.jp_checkout_fullname_mode == "jp_checkout_fullname_yes"}selected="selected"{/if}>{__("jp_checkout_fullname_yes")}</option>
							<option value="jp_checkout_fullname_no" {if $lcjp.jp_checkout_fullname_mode == "jp_checkout_fullname_no"}selected="selected"{/if}>{__("jp_checkout_fullname_no")}</option>
						</select>
					</div>
				</div>
			</fieldset>
		</div>
	</form>
{/capture}

{capture name="buttons"}
	{include file="buttons/save.tpl" but_meta="cm-product-save-buttons" but_role="submit-link" but_name="dispatch[localization_jp.manage]" but_target_form="localization_jp_form" save=$id}
{/capture}

{include file="common/mainbox.tpl" title=$page_title sidebar=$smarty.capture.sidebar content=$smarty.capture.mainbox buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons content_id="manage_orders"}

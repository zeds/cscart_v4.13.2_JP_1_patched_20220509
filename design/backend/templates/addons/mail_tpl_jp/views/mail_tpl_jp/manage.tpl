{* $Id: manage.tpl by tommy from cs-cart.jp 2013 *}

{script src="js/picker.js"}

{** news section **}

{capture name="mainbox"}

<form action="{$index_script}" method="post" name="mtpl_form" class="cm-form-highlight">
<input type="hidden" name="fake" value="1" />

{include file="common/pagination.tpl" save_current_page=true}
<table class="table">
    <thead>
    <tr>
	<th>{__("template")}</th>
	<th>{__("mtpl_trigger")}</th>
	<th>{__("status")}</th>
    <th class="cm-non-cb">&nbsp;</th>
    </tr>
    </thead>
{foreach from=$mail_templates item=mail_template}
<tr {cycle values="class=\"table-row\", "} valign="top" >
	<td><a href="{"mail_tpl_jp.update&tpl_id=`$mail_template.tpl_id`"|fn_url}">{$mail_template.tpl_name}</a></td>

	<td>{$mail_template.tpl_trigger}</td>
	<td>
		{include file="common/select_popup.tpl" id=$mail_template.tpl_code status=$mail_template.status hidden="" object_id_name="tpl_code" table="jp_mtpl"}
	</td>
	<td class="nowrap">
		{include file="common/table_tools_list.tpl" prefix=$mail_template.tpl_id href="{"mail_tpl_jp.update&tpl_id=`$mail_template.tpl_id`"|fn_url}"}
	</td>
</tr>

{foreachelse}
<tr class="no-items">
	<td colspan="6"><p>{__("no_items")}</p></td>
</tr>
{/foreach}
</table>

{include file="common/pagination.tpl"}
</form>

{/capture}
{include file="common/mainbox.tpl" title=__("mtpl_mail_tpl") content=$smarty.capture.mainbox tools=$smarty.capture.tools select_languages=true}

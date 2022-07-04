{* $Id: update.tpl by tommy from cs-cart.jp 2013 *}

{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="mail_templates_form" class="form-horizontal form-edit">

    <input type="hidden" name="fake" value="1" />
    <input type="hidden" name="tpl_id" value="{$mail_template.tpl_id}" />
    <input type="hidden" name="dispatch" value="" />

    <div id="update_mail_tpl_form_{$mail_template.tpl_id}">

    {notes}
        {if $tpl_vars}
        <h5>{__("mtpl_tpl_vars")}</h5>
        <hr />
        <ul>
        {foreach from=$tpl_vars item=p_descr key=p}
            <li><strong>{ldelim}%{$p}%{rdelim}</strong> : {__($p_descr.desc)}</li>
        {/foreach}
        </ul>
        <hr />
        <br />
        {/if}
        <h5>{__("mtpl_tpl_common_vars")}</h5>
        <hr />
        <ul>
        {foreach from=$tpl_common_vars item=p_descr key=p}
            <li><strong>{ldelim}%{$p}%{rdelim}</strong> : {__($p_descr.desc)}</li>
        {/foreach}
        </ul>
    {/notes}

    {if $mail_template.tpl_code != 'mtpl_email_footer'}
    <div class="control-group">
        <label for="elm_mail_template_subject" class="control-label cm-required">{__("subject")}:</label>
        <div class="controls">
            <input type="text" name="mail_template_data[subject]" id="elm_mail_template_subject" value="{$mail_template.subject}" class="input-large" />
        </div>
    </div>
    {/if}
    <div class="control-group">
        <label for="elm_mail_template_body" class="control-label cm-required">{__("body")}:</label>
        <div class="controls">
            <textarea name="mail_template_data[body_txt]" id="elm_mail_template_body" cols="35" rows="20" class="input-large">{$mail_template.body_txt}</textarea>
        </div>
    </div>
    {if $mail_template.tpl_code != 'mtpl_email_footer'}
    <div class="control-group">
        <label for="elm_use_footer" class="control-label">{__("mtpl_use_footer")}:</label>
        <div class="controls">
            <input type="checkbox" id="elm_use_footer" name="mail_template_data[use_footer]" value="Y" class="checkbox cm-item" {if $mail_template.use_footer == 'Y'}checked="checked"{/if} />
        </div>
    </div>
    {/if}
    {include file="common/select_status.tpl" input_name="mail_template_data[status]" id="mail_template_data" obj=$mail_template}
</div>

</form>
{/capture}

{capture name="add_button"}
    {$smarty.capture.add_button}
    {include file="buttons/save_cancel.tpl" but_role="submit-link" but_target_form="mail_templates_form" but_name="dispatch[mail_tpl_jp.update]" save=$mail_template.tpl_id}
{/capture}

{capture name="buttons"}
    {$smarty.capture.add_button nofilter}
{/capture}

{capture name="mainbox_title"}
    {__("editing")} : {$mail_template.tpl_name}
{/capture}

{include file="common/mainbox.tpl" title=$smarty.capture.mainbox_title content=$smarty.capture.mainbox select_languages=true buttons=$smarty.capture.buttons}

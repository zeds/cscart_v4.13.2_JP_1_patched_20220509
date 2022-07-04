{* Modified by takahashi from cs-cart.jp 2018 *}
<div class="well">
    {__("backend_google_auth.settings.create_new_application")}
    {* マニュアルURLの変更 *}
    <a href="{$config.resources.docs_url}topic/google-backend-signin" target="_blank">
        {__("backend_google_auth.settings.learn_more_about")}
    </a>
</div>

{include file="common/widget_copy.tpl"
    widget_copy_text=__("backend_google_auth.settings.authorized_redirect_uris")
    widget_copy_code_text="backend_google_auth.callback?hauth.done={$smarty.const.BACKEND_GOOGLE_AUTH_PROVIDER}"|fn_url:"A"
}

<p>
    {__("backend_google_auth.settings.provide_your_credentials")}:
</p>

{assign var="r_op" value=$r_option}
{assign var="o_id" value=$order_id}
{assign var="current_redirect_url" value=$config.current_url|escape:url}

{if $r_op}
	{if $r_op == "3"}
		<a class="cm-confirm registration" href="{"order_management.registration?order_id=`$o_id`&amp;redirect_url=`$current_redirect_url`"|fn_url}">{__("atone_register")}</a>
	{else}
		<p>{__("already_atone_sales")}</p>
	{/if}
{/if}
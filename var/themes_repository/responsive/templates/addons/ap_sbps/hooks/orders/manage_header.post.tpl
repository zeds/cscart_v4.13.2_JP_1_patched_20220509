{if $addons.subscription_payment_jp && $addons.subscription_payment_jp.status == 'A'}
    <th width="15%"><a class="cm-ajax" href="{"`$c_url`&sort_by=rb_status&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__('subscription_payment')}<br />{__('sbps_rb_status')}</a>{if $search.sort_by == 'rb_status'}{$sort_sign nofilter}{/if}</th>
{/if}
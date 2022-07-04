{* $Id: jp_place_order_button.override.tpl by tommy from cs-cart.jp 2014 *}

{if $payment_method.processor == 'ペイパルウェブペイメントプラス（クレジットカード）' }
<link rel="stylesheet" href="js/lib/prettyphoto/css/prettyPhoto.css" media="screen" charset="utf-8" />
<script src="js/lib/prettyphoto/js/jquery.prettyPhoto.js" charset="utf-8"></script>
<script charset="utf-8">
//<![CDATA[
{literal}
  $(document).ready(function(){
	$("a[rel^='prettyPhoto']").prettyPhoto({modal: true, social_tools: false});
  });
 {/literal}
 //]]>
</script>
{assign var="pwpp_destination" value ="pwpp_form.view?iframe=true&width=585&height=420"|fn_url:'C':'current'}
<a href="{$pwpp_destination}" rel="prettyPhoto[iframes]" title="{__("pwpp_pay_by_pwpp")}">{include file="buttons/place_order.tpl" but_text=__("submit_my_order") but_name="dispatch[checkout.place_order]" but_role="big" but_id="place_order_`$tab_id`"}</a>
{/if}

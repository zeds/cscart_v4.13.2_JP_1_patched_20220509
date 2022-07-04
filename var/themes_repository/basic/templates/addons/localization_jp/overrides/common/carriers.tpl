{* Modified by tommy from cs-cart.jp 2016 *}

{$carrier_info = ""}

{hook name="carriers:list"}

{if $carrier == "usps"}
    {$url = "https://tools.usps.com/go/TrackConfirmAction_input?strOrigTrackNum=`$tracking_number`"}
    {$carrier_name = __("carrier_usps")}
{elseif $carrier == "ups"}
    {$url = "http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=`$tracking_number`"}
    {$carrier_name = __("carrier_ups")}
{elseif $carrier == "fedex"}
    {$url = "http://fedex.com/Tracking?action=track&amp;tracknumbers=`$tracking_number`"}
    {$carrier_name = __("carrier_fedex")}
{elseif $carrier == "aup"}
    {$url = "http://auspost.com.au/track/track.html?id=`$tracking_number`"}
    {$carrier_name = __("carrier_aup")}
{elseif $carrier == "can"}
    {$url = "http://www.canadapost.com/cpotools/apps/track/personal/findByTrackNumber?trackingNumber=`$tracking_number`"}
    {$carrier_name = __("carrier_can")}
{elseif $carrier == "dhl" || $shipping.carrier == "ARB"}
    {$url = "http://www.dhl-usa.com/en/express/tracking.shtml?ShipmentNumber=`$tracking_number`"}
    {$carrier_name = __("carrier_dhl")}
{elseif $carrier == "swisspost"}
    {$url = "http://www.post.ch/swisspost-tracking?formattedParcelCodes=`$tracking_number`"}
    {$carrier_name = __("carrier_swisspost")}
{elseif $carrier == "temando"}
    {$url = "https://temando.com/education-centre/support/track-your-item?token=`$tracking_number`"}
    {$carrier_name = __("carrier_temando")}
{elseif $carrier == "yamato"}
    {$url = "http://jizen.kuronekoyamato.co.jp/jizen/servlet/crjz.b.NQ0010?id=`$tracking_number`"}
    {$carrier_name = __("carrier_yamato")}
{elseif $carrier == "sagawa"}
    {$url = "http://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=`$tracking_number`"}
    {$carrier_name = __("carrier_sagawa")}
{elseif $carrier == "jpost"}
    {$url = "http://tracking.post.japanpost.jp/service/singleSearch.do?org.apache.struts.taglib.html.TOKEN=&amp;searchKind=S002&amp;locale=ja&amp;SVID=&amp;reqCodeNo1=`$tracking_number`"}
    {$carrier_name = __("carrier_jpost")}
{elseif $carrier == "jpems"}
    {$url = "http://tracking.post.japanpost.jp/service/singleSearch.do?searchKind=S004&amp;locale=en&amp;reqCodeNo1=`$tracking_number`"}
    {$carrier_name = __("carrier_jpems")}
{else}
    {$url = ""}
    {$carrier_name = $carrier}
{/if}

{/hook}

{hook name="carriers:capture"}
    {capture name="carrier_name"}
        {$carrier_name}
    {/capture}

    {capture name="carrier_url"}
        {$url nofilter}
    {/capture}

    {capture name="carrier_info"}
        {$carrier_info nofilter}
    {/capture}
{/hook}
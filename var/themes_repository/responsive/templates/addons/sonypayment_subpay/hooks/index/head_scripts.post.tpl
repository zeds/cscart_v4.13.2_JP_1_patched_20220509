{* $Id: head_scripts.post.tpl by takahashi from cs-cart.jp 2019 *}
{assign var=process_data value=fn_sonys_get_processor_data()}

{if isset($process_data.processor_params.token_ninsyo_code) }
    {if $process_data.processor_params.token_ninsyo_code && (($runtime.controller == "checkout" && $runtime.mode == "checkout") || ($runtime.controller == "sonys_card_info" && $runtime.mode == "view")) }
        {if $process_data.processor_params.mode == "test"}
            <script src="https://www.test.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$process_data.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
        {elseif $process_data.processor_params.mode == "live"}
            <script src="https://www.e-scott.jp/euser/stn/CdGetJavaScript.do?k_TokenNinsyoCode={$process_data.processor_params.token_ninsyo_code}" callBackFunc="setToken" class="spsvToken" ></script>
        {/if}
    {/if}
{/if}

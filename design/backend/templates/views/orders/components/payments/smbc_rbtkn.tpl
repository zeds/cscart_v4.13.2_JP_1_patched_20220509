{* $Id: smbc_rbtkn.tpl by tommy from cs-cart.jp 2014 *}

{script src="js/lib/maskedinput/jquery.maskedinput.min.js"}
{script src="js/lib/creditcardvalidator/jquery.creditCardValidator.js"}

<div class="clearfix">
    <div class="credit-card">
        <div class="control-group">
            <label for="cc_number{$id_suffix}" class="control-label cm-required">{__("card_number")}</label>
            <div class="controls">
                <input id="cc_number{$id_suffix}" size="35" type="text" name="payment_info[card_number]" value="" class="input-big cm-autocomplete-off" />
            </div>
            <ul class="cc-icons-wrap cc-icons unstyled" id="cc_icons{$id_suffix}">
                <li class="cc-icon cm-cc-default"><span class="default">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa"><span class="visa">&nbsp;</span></li>
                <li class="cc-icon cm-cc-visa_electron"><span class="visa-electron">&nbsp;</span></li>
                <li class="cc-icon cm-cc-mastercard"><span class="mastercard">&nbsp;</span></li>
                <li class="cc-icon cm-cc-maestro"><span class="maestro">&nbsp;</span></li>
                <li class="cc-icon cm-cc-amex"><span class="american-express">&nbsp;</span></li>
                <li class="cc-icon cm-cc-discover"><span class="discover">&nbsp;</span></li>
            </ul>
        </div>

        <div class="control-group">
            <label for="cc_exp_month{$id_suffix}" class="control-label cm-required">{__("valid_thru")}</label>
            <div class="controls clear">
                <div class="cm-field-container nowrap">
                    <input type="text" id="cc_exp_month{$id_suffix}" name="payment_info[expiry_month]" value="" size="2" maxlength="2" class="input-small" />&nbsp;/&nbsp;<input type="text" id="cc_exp_year{$id_suffix}" name="payment_info[expiry_year]" value="" size="2" maxlength="2" class="input-small" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function(_, $) {
        $(document).ready(function() {
            var icons = $('#cc_icons{$id_suffix} li');

            $("#cc_number{$id_suffix}").mask("9999 9999 9999 9?999", {
                placeholder: ' '
            });

            $("#cc_cvv2{$id_suffix}").mask("999?9", {
                placeholder: ''
            });

            $("#cc_exp_month{$id_suffix},#cc_exp_year{$id_suffix}").mask("99", {
                placeholder: ''
            });

            $('#cc_number{$id_suffix}').validateCreditCard(function(result) {
                icons.removeClass('active');
                if (result.card_type) {
                    icons.filter('.cm-cc-' + result.card_type.name).addClass('active');

                    if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1) {
                        $('label[for=cc_cvv2{$id_suffix}]').removeClass('cm-required');
                    } else {
                        $('label[for=cc_cvv2{$id_suffix}]').addClass('cm-required');
                    }
                }
            });
        });
    })(Tygh, Tygh.$);
</script>
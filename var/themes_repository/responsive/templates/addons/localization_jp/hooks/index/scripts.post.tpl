<script>
    (function(_, $) {
        //言語変数を定義
        _.tr({
            'error_validator_cc_exp_jp': '{__("error_validator_cc_exp_jp")|escape:"javascript"}',
            'error_validator_cc_check_length_jp': '{__("error_validator_cc_check_length_jp")|escape:"javascript"}'
        });

        // Modified by takahashi from cs-cart.jp 2018
        // 支払方法のラジオボタンで cm-select-payment_submitクラスにするとAjaxではなくSubmitする
        $(_.doc).on('click', '.cm-select-payment_submit', function() {

            // 新チェックアウト方式に対応
            if(document.getElementById('litecheckout_payments_form')) {
                var jelm = $(this);
                document.litecheckout_payments_form.payment_id[0].value = jelm.val();
                document.litecheckout_payments_form.payment_id[1].value = jelm.val();

                $('#litecheckout_payments_form').append('<input type="hidden" name="switch_payment_method" value="Y" />');
            }

            this.form.method = "POST";
            this.form.action = "{"checkout.checkout"|fn_url}";
            this.form.submit();
        });
    }(Tygh, Tygh.$));
</script>

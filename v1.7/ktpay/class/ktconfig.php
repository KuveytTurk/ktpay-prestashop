<?php

class KTPayConfig{

    public static function get_rates_array($arrayString){

        if($arrayString==null && !$arrayString)
            return array();
        
        $arrayString = str_replace("[","",$arrayString);
        $arrayString = str_replace("]","",$arrayString);

        return explode(",",$arrayString);
    }

    public static function init_rates($installment_array)
    {
        $installments = array();
        for ($i = 1; $i <= count($installment_array); $i++) {
            $installments[$i]['rate'] = 0;
            $installments[$i]['active'] = 0;
            $installments[$i]['count'] = $installment_array[$i-1];
        }

        return $installments;
    }

    public static function create_rates_update_form($rates, $installment_array)
    {
        $installment_options_form = '<form action="" method="post">';

        if(!is_array($rates))
            $rates = array_values(json_decode($rates, true));

        $installment_options_form .= '<table class="table" style="text-align: center;">'
            . '<thead><tr>';
        foreach ($installment_array as $i) {
            $installment_options_form .= '<th>' . $i . ' Taksit</th><input type="hidden" name="ktpay_rates[' . $i . '][count]" value="'.$i.'"/>';
        }

        $installment_options_form .= '</tr></thead><tbody><tr>';

        for ($i = 1; $i <= count($installment_array); $i++) {
            if (!isset($rates[$i]['active']))
                $rates[$i]['active'] = 0;

            $installment_options_form .= '<td>'
                . ' <input type="checkbox"  name="ktpay_rates[' . $i . '][active]" '
                . ' value="1" ' . ((int) $rates[$i]['active'] == 1 ? 'checked="checked"' : '') . ' /> </td>';
        }

        $installment_options_form .= '</tr>';
        $installment_options_form .= '</tbody></table>';
        $installment_options_form .='<h1>
            <input id="checkInstallmentDefinition" type="submit" value="Taksit Tanımını Kontrol Et" name="checkInstallmentDefinition" class="w3-btn w3-teal w3-round-large w3-small" /> 
            <input id="saveInstallmentSettings" type="submit" value="Kaydet" name="saveInstallmentSettings" class="w3-btn w3-teal w3-round-large w3-small" />         
            </h1>';
        $installment_options_form .='</form>';

        return $installment_options_form;
    }

    public static function calculate_price_with_installments($price, $rates, $installment_array)
    {
        $installment = array();
        for ($i = 1; $i <= count($installment_array); $i++) {
            $installment[$i] = array(
                'count' => $rates[$i]['count'],
                'active' => isset($rates[$i]['active']) ? $rates[$i]['active'] : 0,
                'total' => number_format((((100 + (isset($rates[$i]['rate']) ? $rates[$i]['rate'] : 0)) * $price) / 100), 2, '.', ''),
                'monthly' => number_format((((100 + (isset($rates[$i]['rate']) ? $rates[$i]['rate'] : 0)) * $price) / 100) / $i, 2, '.', ''),
            );
        }
        return $installment;
    }

    public static function calculate_total_price($price, $rates, $installment)
    {
        return number_format((((100 + (isset($rates[$installment]['rate']) ? $rates[$installment]['rate'] : 0)) * $price) / 100), 2, '.', '');
    }

    public static function get_currency_code($currency)
    {
        switch ($currency) {
            case 'TRY':
                $code = "0949";
                break;
            case 'USD':
                $code = "0840";
                break;
            case 'EUR':
                $code = "0978";
                break;
            default:
                $code = "0949";
                break;
        }
        return $code;
    }

    public static function replaceSpace($veri) {
        $veri = str_replace("/s+/", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace(" ", "", $veri);
        $veri = str_replace("/s/g", "", $veri);
        $veri = str_replace("/s+/g", "", $veri);
        $veri = trim($veri);
        return $veri;
    }

    public static function get_hash_data($hashValue, $password)
    {
        //$hashPassword = base64_encode(hash('sha1', mb_convert_encoding($password, "UTF-8",mb_detect_encoding($password)), true));
        $hashPassword = "poCqMathhevCYY1LVNbWCQWbC5I=";
        $hashValue .= $hashPassword;
        $hashbytes=mb_convert_encoding($hashValue, "UTF-8",mb_detect_encoding($hashValue));
        $inputbytes = hash_hmac('sha512', $hashbytes, mb_convert_encoding($hashPassword, "UTF-8",mb_detect_encoding($hashPassword)), true);
        return base64_encode($inputbytes);
    }
}
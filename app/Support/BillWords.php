<?php

namespace App\Support;

/**
 * The amount-in-words functions the CakePHP member bill view (SocietyBills/print_member_bills.ctp) defines at its foot,
 * carried over as they are: English (Indian lakh / crore words), Marathi, and the two helpers they use.
 * Only the mistyped bareword in the Marathi word list (a constant on PHP 7.4, an error on PHP 8) is written as the text it stood for.
 */
class BillWords
{
    public static function firstLetterZero($number){
    $splitedNo = str_split($number);
    if($splitedNo[0] == 0){
        unset($splitedNo[0]);
    }
    $removedArray = array_values($splitedNo);
    $str = implode($removedArray);    
    return $str;
}

    public static function marathi($num)
{
        $num = str_replace('-','',$num);

        $ones = array(
        1 => " एक ",
        2 => " दोन ",
        3 => " तीन ",
        4 => " चार ",
        5 => " पाच ",
        6 => " सहा ",
        7 => " सात ",
        8 => " आठ ",
        9 => " नऊ ",
        10=> " दहा ",
        11=> " अकरा ",
        12=> " बारा ",
        13=> " तेरा ",
        14=> " चौदा ",
        15 => " पंधरा ",
        16 => " सोळा ",
        17 => " सतरा ",
        18 => " अठरा ",
        19 => " एकोणीस ",
        );
        $tens = array(
        1 => " दहा ",
        2 => " वीस ",
        3 => " तीस ",
        4 => " चाळीस ",
        5 => " पन्नास ",
        6 => " साठ ",
        7 => " सत्तर ",
        8 => " ऐंशी ",
        9 => " नव्वद ",
        );
        $hundreds = array(
        " शंभर ",
        " हजार ",
        " दक्षलक्ष ",
        " अब्ज ",
        " अमेरिकेत ",
        " चतुर्भुज ", "चतुर्भुज"
        ); //limit t quadrillion
        $num = number_format($num,2,".",",");
        $num_arr = explode(".",$num);
        $wholenum = $num_arr[0];
        $decnum = $num_arr[1];
        $whole_arr = array_reverse(explode(",",$wholenum));
        krsort($whole_arr);
        $rettxt = "";
        foreach($whole_arr as $key => $i){
        if($i < 20){
        $rettxt .= $ones[$i];
        }elseif($i < 100){
        $rettxt .= $tens[substr($i,0,1)];
        $rettxt .= " ".$ones[substr($i,1,1)];
        }else{
        $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0];
        $rettxt .= " ".$tens[substr($i,1,1)];
        $rettxt .= " ".$ones[substr($i,2,1)];
        }
        if($key > 0){
        $rettxt .= " ".$hundreds[$key]." ";
        }
        }
        if($decnum > 0){
        $rettxt .= " and ";
        if($decnum < 20){
        $rettxt .= $ones[$decnum];
        }elseif($decnum < 100){
        $rettxt .= $tens[substr($decnum,0,1)];
        $rettxt .= " ".$ones[substr($decnum,1,1)];
        }
        }
        return $rettxt;
}

    public static function english($number, $amountType = 'rupees') {
        $arr_ones = array("", "One", "Two", "Three", "Four", "Five", "Six","Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen","Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eightteen","Nineteen");
        $arr_tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", "Seventy", "Eigthy", "Ninety");



        $arab = floor($number / 1000000000);  /* arab (giga) */

        $number -= $arab * 1000000000;

        $crores = floor($number / 10000000);  /* crore (giga) */

        $number -= $crores * 10000000;

        $lakhs = floor($number / 100000);  /* lakhs (giga) */

        $number -= $lakhs * 100000;

        $thousands = floor($number / 1000);  /* Thousands (kilo) */

        $number -= $thousands * 1000;

        $hundreds = floor($number / 100);   /* Hundreds (hecto) */

        $number -= $hundreds * 100;

        $tens = floor($number / 10);    /* Tens (deca) */

        $ones = $number % 10;      /* Ones */

        $res = "";
        
        

        if ($arab) {

            $res .= self::convertToWords($arab);

            $res .= ($arab > 10) ? " Arabs " : " Arab ";

        }


        if ($crores) {

            $res .= self::convertToWords($crores);

            $res .= ($crores > 10) ? " Crores " : " Crore ";

        }

        if ($lakhs) {
            
            $res .= self::convertToWords($lakhs);

            $res .= ($lakhs > 10) ? " Lakhs " : " Lakh ";

        }

        if ($thousands) {
            $res .=  self::convertToWords($thousands) . " Thousand ";

        }



        if ($hundreds) {

            $res .= self::convertToWords($hundreds) . " Hundred ";

        }
        


        if ($tens || $ones) {

            if (!empty($res)) {

                $res .= " and ";

            }

            if ($tens < 2) {

                $res .= $arr_ones[$tens * 10 + $ones];

            } else {

                $res .= $arr_tens[$tens];
                // echo $res;
                if ($ones) {
                    $res .= " " .$arr_ones[$ones];

                }

            }

        }



        if (empty($res)) {

            $res = "zero";

        }

        return $res;

    }

    public static function convertToWords($num)
        {

                $num = str_replace('-','',$num);

                $ones = array(
                1 => "one",
                2 => "two",
                3 => "three",
                4 => "four",
                5 => "five",
                6 => "six",
                7 => "seven",
                8 => "eight",
                9 => "nine",
                10 => "ten",
                11 => "eleven",
                12 => "twelve",
                13 => "thirteen",
                14 => "fourteen",
                15 => "fifteen",
                16 => "sixteen",
                17 => "seventeen",
                18 => "eighteen",
                19 => "nineteen"
                );
                $tens = array(
                1 => "ten",
                2 => "twenty",
                3 => "thirty",
                4 => "forty",
                5 => "fifty",
                6 => "sixty",
                7 => "seventy",
                8 => "eighty",
                9 => "ninety"
                );
                $hundreds = array(
                "hundred",
                "thousand",
                "million",
                "billion",
                "trillion",
                "quadrillion"
                ); //limit t quadrillion
                $num = number_format($num,2,".",",");
                $num_arr = explode(".",$num);
                $wholenum = $num_arr[0];
                $decnum = $num_arr[1];
                $whole_arr = array_reverse(explode(",",$wholenum));
                krsort($whole_arr);
                $rettxt = "";
                foreach($whole_arr as $key => $i){
                    if($i < 20){
                    $rettxt .= $ones[$i];
                    }elseif($i < 100){
                    $i = self::firstLetterZero($i);
                    $rettxt .= $tens[substr($i,0,1)];
                    $rettxt .= " ".$ones[substr($i,1,1)];
                    }else{
                    $i = self::firstLetterZero($i);
                    $rettxt .= $ones[substr($i,0,1)]." ".$hundreds[0];
                    $rettxt .= " ".$tens[substr($i,1,1)];
                    $rettxt .= " ".$ones[substr($i,2,1)];
                    }
                    if($key > 0){
                    $rettxt .= " ".$hundreds[$key]." ";
                    }
                }
                if($decnum > 0){
                $rettxt .= " and ";
                if($decnum < 20){
                $rettxt .= $ones[$decnum];
                }elseif($decnum < 100){
                $rettxt .= $tens[substr($decnum,0,1)];
                $rettxt .= " ".$ones[substr($decnum,1,1)];
                }
                $rettxt .= " Paisa ";
                }
                return $rettxt;
        }    

    /** SocietyBillComponent::numberTowords - the words the e-mail / PDF bill prints (no "Paisa" part) */
    public static function numberTowords($num)
    {
        $num = str_replace('-', '', $num);
        $ones = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven',
            12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen'];
        $tens = [1 => 'ten', 2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty', 6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'];
        $hundreds = ['hundred', 'thousand', 'million', 'billion', 'trillion', 'quadrillion'];
        $num = number_format($num, 2, '.', ',');
        $num_arr = explode('.', $num);
        $wholenum = $num_arr[0];
        $decnum = $num_arr[1];
        $whole_arr = array_reverse(explode(',', $wholenum));
        krsort($whole_arr);
        $rettxt = '';
        foreach ($whole_arr as $key => $i) {
            if ($i < 20) {
                $rettxt .= $ones[$i] ?? '';
            } elseif ($i < 100) {
                $rettxt .= $tens[substr($i, 0, 1)] ?? '';
                $rettxt .= ' ' . ($ones[substr($i, 1, 1)] ?? '');
            } else {
                $rettxt .= ($ones[substr($i, 0, 1)] ?? '') . ' ' . $hundreds[0];
                $rettxt .= ' ' . ($tens[substr($i, 1, 1)] ?? '');
                $rettxt .= ' ' . ($ones[substr($i, 2, 1)] ?? '');
            }
            if ($key > 0) {
                $rettxt .= ' ' . $hundreds[$key] . ' ';
            }
        }
        if ($decnum > 0) {
            $rettxt .= ' and ';
            if ($decnum < 20) {
                $rettxt .= $ones[$decnum] ?? '';
            } elseif ($decnum < 100) {
                $rettxt .= $tens[substr($decnum, 0, 1)] ?? '';
                $rettxt .= ' ' . ($ones[substr($decnum, 1, 1)] ?? '');
            }
        }

        return $rettxt;
    }
}

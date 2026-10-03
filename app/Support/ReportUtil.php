<?php

namespace App\Support;

/**
 * The bits of CakePHP's UtilComponent the account reports lean on (mysqlToDate, getAmountInRupees,
 * CreditDebitAmountCheck ...), ported one to one so the printed text is the same.
 */
class ReportUtil
{
    private const ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eightteen', 'Nineteen'];
    private const TENS = ['', '', 'Twenty', 'Thirty', 'Fourty', 'Fifty', 'Sixty', 'Seventy', 'Eigthy', 'Ninety'];

    /** UtilComponent::getFormatDate: today when the date is empty, the epoch when it does not parse. */
    public static function formatDate($date = '', string $format = 'Y-m-d'): string
    {
        if ($date !== '' && $date !== null) {
            $ts = strtotime((string) $date);

            return date($format, $ts === false ? 0 : $ts);
        }

        return date($format);
    }

    /** UtilComponent::mysqlToDate: 'd/m/Y' style (default separator '-'), '' when the date does not parse. */
    public static function mysqlToDate($date, string $separator = ''): string
    {
        $ts = strtotime((string) $date);
        if ($ts === false || $date === null || $date === '') {
            return '';
        }

        return $separator !== '' ? date('d' . $separator . 'm' . $separator . 'Y', $ts) : date('d-m-Y', $ts);
    }

    /** UtilComponent::getAmountInRupees (works on the printed text of the amount, exactly like CakePHP). */
    public static function amountInRupees($amount): string
    {
        $value = str_replace(',', '', (string) $amount);
        $split = explode('.', $value);
        $rs = 'Rupees ' . self::convertToWords($split[0]);
        $ps = '';
        if (count($split) == 2) {
            $ps = ($split[1] > 0) ? ' and ' . self::convertToWords($split[1]) . ' Paise' : '';
        }

        return $rs . $ps . ' Only';
    }

    /** UtilComponent::convertToWords */
    public static function convertToWords($number): string
    {
        $number = is_numeric($number) ? $number + 0 : 0;
        $arab = floor($number / 1000000000);
        $number -= $arab * 1000000000;
        $crores = floor($number / 10000000);
        $number -= $crores * 10000000;
        $lakhs = floor($number / 100000);
        $number -= $lakhs * 100000;
        $thousands = floor($number / 1000);
        $number -= $thousands * 1000;
        $hundreds = floor($number / 100);
        $number -= $hundreds * 100;
        $tens = (int) floor($number / 10);
        $ones = (int) fmod($number, 10);

        $res = '';
        if ($arab) {
            $res .= self::convertToWords($arab) . ($arab > 10 ? ' Arabs ' : ' Arab ');
        }
        if ($crores) {
            $res .= self::convertToWords($crores) . ($crores > 10 ? ' Crores ' : ' Crore ');
        }
        if ($lakhs) {
            $res .= self::convertToWords($lakhs) . ($lakhs > 10 ? ' Lakhs' : ' Lakh');
        }
        if ($thousands) {
            $res .= (empty($res) ? '' : ' ') . self::convertToWords($thousands) . ' Thousand';
        }
        if ($hundreds) {
            $res .= (empty($res) ? '' : ' ') . self::convertToWords($hundreds) . ' Hundred';
        }
        if ($tens || $ones) {
            if (!empty($res)) {
                $res .= ' and ';
            }
            if ($tens < 2) {
                $res .= self::ONES[$tens * 10 + $ones];
            } else {
                $res .= self::TENS[$tens];
                if ($ones) {
                    $res .= ' ' . self::ONES[$ones];
                }
            }
        }
        if (empty($res)) {
            $res = 'zero';
        }

        return $res;
    }

    /**
     * UtilComponent::NumberFormat2Decimal, as it behaves on the PHP 7.4 CakePHP runs on: "$amt != ''" is
     * false for a numeric 0 (0 == '' there), so a zero total prints nothing; text amounts such as "0.00" pass.
     *
     * @return string|false
     */
    public static function decimal2($amt = '')
    {
        if ($amt === '' || $amt === null || $amt === false) {
            return false;
        }
        if ((is_int($amt) || is_float($amt)) && $amt == 0) {
            return false;
        }

        return number_format((float) $amt, 2, '.', '');
    }

    /** UtilComponent::CreditDebitAmountCheck($x) applied to decimal2($amt): the "Total" cells of the register style reports. */
    public static function decimal2CreditDebit($amt)
    {
        return self::creditDebit(self::decimal2($amt));
    }
    /** UtilComponent::CreditDebitAmountCheck: "12.00 Dr" / "12.00 Cr", the bare value when 0, false when ''. */
    public static function creditDebit($value)
    {
        if ($value === '' || $value === null) {
            return false;
        }
        if ($value > 0) {
            return number_format((float) abs($value), 2, '.', '') . ' <span class="notranslate">Dr</span>';
        }
        if ($value < 0) {
            return number_format((float) abs($value), 2, '.', '') . ' <span class="notranslate">Cr</span>';
        }

        return $value;
    }

    /** UtilComponent::societyBillingFrequency */
    public static function billingFrequency($type): array
    {
        $all = [
            1 => ['4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December', '1' => 'January', '2' => 'February', '3' => 'March'],
            2 => ['4' => 'Apr-May', '6' => 'Jun-Jul', '8' => 'Aug-Sep', '10' => 'Oct-Nov', '12' => 'Dec-Jan', '2' => 'Feb-Mar'],
            3 => ['4' => 'Apr-May-Jun', '7' => 'Jul-Aug-Sep', '10' => 'Oct-Nov-Dec', '1' => 'Jan-Feb-Mar'],
            4 => [],
            5 => ['4' => 'Apr-Sep', '10' => 'Oct-Mar'],
            6 => ['4' => 'April-March'],
        ];

        return $all[$type] ?? [];
    }

    /** Heading text that may hold markup in the data: strip it, same as the other report views. */
    public static function plain($text): string
    {
        return strip_tags((string) $text);
    }
}

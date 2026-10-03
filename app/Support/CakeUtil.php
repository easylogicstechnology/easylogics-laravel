<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Stand-ins for the CakePHP objects the account-report views call ($utilObj = UtilComponent,
 * $societyObj = SocietyBillComponent, $this->Session), so the heavier report views can be carried
 * over line by line. Every method behaves like the CakePHP one.
 */
class CakeUtil
{
    public function getFormatDate($date = '', $format = 'Y-m-d')
    {
        return ReportUtil::formatDate($date, $format);
    }

    public function mysqlToDate($date, $separator = '')
    {
        return ReportUtil::mysqlToDate($date, $separator);
    }

    public function NumberFormat2Decimal($amt = '')
    {
        return ReportUtil::decimal2($amt);
    }

    public function CreditDebitAmountCheck($amtValue = '')
    {
        return ReportUtil::creditDebit($amtValue);
    }

    public function getAmountInRupees($amount)
    {
        return ReportUtil::amountInRupees($amount);
    }

    public function convertToWords($number, $amountType = 'rupees')
    {
        return ReportUtil::convertToWords($number);
    }

    public function getMonthWordFormat($monthNum = '')
    {
        if ($monthNum) {
            return date('F', mktime(0, 0, 0, (int) $monthNum, 10));
        }

        return null;
    }

    /** SocietyBillComponent::numberTowords */
    public function numberTowords($num)
    {
        return BillWords::numberTowords($num);
    }

    /** SocietyBillComponent::monthWordFormatByBillingFrequency */
    public function monthWordFormatByBillingFrequency($monthNo = null)
    {
        static $lists = [];
        $society = (int) Auth::id();
        if (!isset($lists[$society])) {
            $row = DB::table('society_parameters')->where('society_id', $society)->orderBy('id')->first(['billing_frequency_id']);
            $lists[$society] = $row ? ReportUtil::billingFrequency($row->billing_frequency_id) : [];
        }

        return $lists[$society][$monthNo] ?? false;
    }
}

<?php

namespace Studiobit\Project\Helper;

class Text
{
    /**
     * @param string $phone
     * @return string
     */
    public static function normalizePhone($phone)
    {
        $phone = preg_replace('/[^\d]/', '', $phone);

        $cleanPhone = (string)\NormalizePhone($phone, 1);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '7' . $cleanPhone;
        }

        return $cleanPhone;
    }

    /**
     * @param $value
     * @return string
     */
    public static function priceFormat($value)
    {
        $decimals = 0;
        if ($value - floor($value) >= 0.01) {
            $decimals = 2;
        }

        return number_format($value, $decimals, ',', ' ');
    }
}

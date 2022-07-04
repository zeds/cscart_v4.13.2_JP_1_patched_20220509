<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\Enum;

/**
 * SquareStatuses contains possible values for statuses of square transaction
 *
 * @package Tygh\Enum
 */
class SquareStatuses
{
    const CAPTURE = 'C';
    const AUTH = 'A';
    const VOID = 'V';
    const REFUND = 'R';
    
    public static function getDescription($status)
    {
        $descriptions = array(
            self::CAPTURE => __('addons.sd_square_payment.captured'),
            self::AUTH => __('addons.sd_square_payment.authorized'),
            self::VOID => __('addons.sd_square_payment.voided'),
            self::REFUND => __('addons.sd_square_payment.refunded'),
        );
        return !empty($descriptions[$status]) ? $descriptions[$status] : '';
    }

    public static function getActions($status)
    {
        $actions = array(
            self::CAPTURE => array(
                self::REFUND => __('addons.sd_square_payment.refund')
            ),
            self::AUTH => array(
                self::CAPTURE => __('addons.sd_square_payment.capture'),
                self::VOID => __('addons.sd_square_payment.void'),
            ),
            self::VOID => array(),
            self::REFUND => array(),
        );
        return !empty($actions[$status]) ? $actions[$status] : '';
    }
}

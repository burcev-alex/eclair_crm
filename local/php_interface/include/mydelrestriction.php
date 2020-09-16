<?php
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\Internals\Entity;

class MyDeliveryRestriction extends Restrictions\Base
{
    public static function getClassTitle()
    {
        return 'moon';
    }

    public static function getClassDescription()
    {
        return 'moon';
    }

    public static function check($moonday, array $restrictionParams, $deliveryId = 0)
    {
        if ($moonday < $restrictionParams['MIN_MOONDAY']
            || $moonday > $restrictionParams['MAX_MOONDAY'])
            return false;

        return true;
    }
    protected static function extractParams(Entity $shipment)
    {
        $json = file_get_contents('http://moon-today.com/api/index.php?get=moonday');
        $res = json_decode($json, true);
        return !empty($res['moonday']) ? intval($res['moonday']) : 0;
    }
    public static function getParamsStructure($entityId = 0)
    {
        return array(
            "MIN_MOONDAY" => array(
                'TYPE' => 'NUMBER',
                'DEFAULT' => "1",
                'LABEL' => 'moon day'
            ),
            "MAX_MOONDAY" => array(
                'TYPE' => 'NUMBER',
                'DEFAULT' => "30",
                'LABEL' => 'moon day max'
            )
        );
    }
}
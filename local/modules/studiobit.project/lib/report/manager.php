<?php

namespace Studiobit\Project\Report;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Project;

Main\Loader::includeModule('crm');
Main\Loader::includeModule('report');

class Manager extends \CCrmReportManager
{
    public static $OWNER_INFOS_EX = null;

    private static function createOwnerInfo($ID, $className, $title)
    {
        return array(
            'ID' => $ID,
            'HELPER_CLASS' => $className,
            'TITLE' => $title
        );
    }

    public static function getOwnerInfos()
    {
        if(self::$OWNER_INFOS_EX)
        {
            return self::$OWNER_INFOS_EX;
        }
        self::$OWNER_INFOS_EX = array();

        self::$OWNER_INFOS_EX = parent::getOwnerInfos();

        self::$OWNER_INFOS_EX[] = self::createOwnerInfo(
            Contact::getOwnerId(),
            '\Studiobit\Project\Report\Contact',
            'Контакт'
        );

        return self::$OWNER_INFOS_EX;
    }

    public static function getOwnerInfo($ownerID)
    {
        $ownerID = strval($ownerID);
        if($ownerID === '')
        {
            return null;
        }

        $infos = self::getOwnerInfos();
        foreach($infos as $info)
        {
            if($info['ID'] === $ownerID)
            {
                return $info;
            }
        }
        return null;
    }
    public static function getOwnerHelperClassName($ownerID)
    {
        $info = self::getOwnerInfo($ownerID);
        return $info ? $info['HELPER_CLASS'] : '';
    }
}
?>
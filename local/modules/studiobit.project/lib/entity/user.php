<?php
namespace Studiobit\Project\Entity;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Base\Tools;
use Studiobit\Project as Project;


class UserTable extends Main\UserTable
{
    static $cache = [];

    public static function getData($id){
        if(!isset(self::$cache[$id])) {
            $rs = self::getList([
                'filter' => ['ID' => $id],
                'select' => [
                    'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LAST_NAME', 'EMAIL'
                ]
            ]);

            self::$cache[$id] = $rs->fetch();
        }

        return self::$cache[$id];
    }

    public static function getFullName($id){
        return \CUser::FormatName(\CSite::GetDefaultNameFormat(), self::getData($id));
    }

    public static function getUrl($id){
        return '/company/personal/user/'.$id.'/';
    }

    public static function isAdmin($id){
        foreach (self::getUserGroupIds($id) as $groupId)
        {
            if ($groupId == 1)
            {
                return true;
            }
        }

        return false;
    }

    public static function getDepartment($id){
        $return = [];
        if(Main\Loader::includeModule('iblock')){
            $iblockId = intval(\COption::GetOptionInt('intranet', 'iblock_structure'));

            $rsSection = \CIBlockSection::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    'UF_HEAD'   => $id
                ],
                false,
                ['ID']
            );

            while($arSection = $rsSection->Fetch()) {
                $return[$arSection['ID']] = $arSection['ID'];
            }

            $rs = self::getList([
                'filter' => ['ID' => $id],
                'select' => ['ID', 'UF_DEPARTMENT']
            ]);

            if($ar = $rs->fetch()){
                if(is_array($ar['UF_DEPARTMENT'])) {
                    $return = array_merge($return, $ar['UF_DEPARTMENT']);
                }
            }
        }

        return array_values(array_unique($return));
    }
}
?>
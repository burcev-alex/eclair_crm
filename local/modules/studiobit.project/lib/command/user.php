<?php

namespace Studiobit\Project\Command;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\UserUtils;
use Studiobit\Base\Tools;

/**
 * команды для пользователей
 */

class User
{
	public static function checkEmptyPhone()
    {
        \Bitrix\Main\Loader::includeModule('im');
        
        $rsUser = UserTable::getList([
            'filter' => ['WORK_PHONE' => false],
            'select' => ['ID', 'EMAIL', 'NAME']
        ]);
        
        while($arUser = $rsUser->fetch())
        {
            $message = "<b>Внимание!</b> У вас не заполнено поле \"Рабочий телефон\" в профиле пользователя.";

            Tools::addNoteUser(
                1,
                $arUser['ID'],
                $message,
                IM_NOTIFY_SYSTEM
            );
            
            \CEvent::Send('STUDIOBIT_USER_EMPTY_PHONE', SITE_ID, $arUser);
        }
    }

    public static function updateUF()
    {
        //обновляем поля Командир и Руководитель для всех пользователей
        $structure = self::GetStructure();

        $users = [];
        p($structure);

        foreach($structure['DATA'] as $sectionId => $arData){
            foreach($arData['EMPLOYEES'] as $userId){
                if(!isset($users[$userId]))
                    $users[$userId] = [];

                $users[$userId]['UF_HEAD'] = $arData['UF_HEAD'];
                $users[$userId]['UF_COMMANDER'] = $arData['UF_HEAD'];

                if($userId == $arData['UF_HEAD'] || $users[$userId]['UF_HEAD'] == 0){
                    while($users[$userId]['UF_HEAD'] == 0) {
                        $parent = $structure['DATA'][$arData['IBLOCK_SECTION_ID']];
                        if (!empty($parent)) {
                            $users[$userId]['UF_HEAD'] = $parent['UF_HEAD'];
                        }
                        else{
                            break;
                        }
                    }
                }
            }
        }

        $obUser = new \CUser();
        foreach($users as $userId => $fields){
            $obUser->Update($userId, $fields);
        }
    }

    //аналог CIntranetUtils::_GetDeparmentsTree, но без кеша и возвращает всех пользователей, в том числе уволенных
    private static function GetStructure()
    {
        global $DB;

        Loader::includeModule('iblock');

        $return = [
            'TREE' => [],
            'DATA' => [],
        ];

        $ibDept = \COption::GetOptionInt('intranet', 'iblock_structure', false);
        if ($ibDept <= 0)
            return $return;

        $dbRes = \CIBlockSection::GetList(
            array("LEFT_MARGIN"=>"ASC"),
            array('IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'),
            false,
            array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD', 'SECTION_PAGE_URL', 'DEPTH_LEVEL',)
        );

        while ($arRes = $dbRes->Fetch())
        {
            if (!$arRes['IBLOCK_SECTION_ID'])
                $arRes['IBLOCK_SECTION_ID'] = 0;

            if (!$return['TREE'][$arRes['IBLOCK_SECTION_ID']])
                $return['TREE'][$arRes['IBLOCK_SECTION_ID']] = array();

            $return['TREE'][$arRes['IBLOCK_SECTION_ID']][] = $arRes['ID'];
            $return['DATA'][$arRes['ID']] = array(
                'ID' => $arRes['ID'],
                'NAME' => $arRes['NAME'],
                'IBLOCK_SECTION_ID' => $arRes['IBLOCK_SECTION_ID'],
                'UF_HEAD' => $arRes['UF_HEAD'],
                'SECTION_PAGE_URL' => $arRes['SECTION_PAGE_URL'],
                'DEPTH_LEVEL' => $arRes['DEPTH_LEVEL'],
                'EMPLOYEES' => array()
            );
        }

        $dbRes = $DB->Query("
            SELECT BUF.VALUE_ID AS ID, BUF.VALUE_INT AS UF_DEPARTMENT
                FROM b_utm_user BUF
                    LEFT JOIN b_user_field UF ON BUF.FIELD_ID = UF.ID
                    LEFT JOIN b_user U ON BUF.VALUE_ID = U.ID
                WHERE ( UF.FIELD_NAME = 'UF_DEPARTMENT' )
                    AND ( BUF.VALUE_INT IS NOT NULL AND BUF.VALUE_INT <> 0 )
        ");

        while ($arRes = $dbRes->Fetch())
        {
            $dpt = $arRes['UF_DEPARTMENT'];
            if (is_array($return['DATA'][$dpt]))
                $return['DATA'][$dpt]['EMPLOYEES'][] = $arRes['ID'];
        }

        return $return;
    }
}
?>
<?php

namespace Studiobit\Project\Command;

use Bitrix\Main\Loader;
use Studiobit\Base\Tools;

/**
 * команды для дел
 */

class Activity
{
	public static function importantOverdue($id, $userId)
    {
        if($id && Loader::includeModule('crm') && Loader::includeModule('im')) {
            $rsActivity = \CCrmActivity::GetList(
                [],
                ['ID' => $id],
                false,
                false,
                ['ID', 'SUBJECT', 'DEADLINE', 'COMPLETED']
            );

            if ($arActivity = $rsActivity->fetch()) {
                if ($arActivity['COMPLETED'] == 'N') {
                    $name = $arActivity['SUBJECT'];

                    $arBindings = \CCrmActivity::GetBindings($id);

                    foreach($arBindings as $arBinding){
                        $url = $entity = $title = '';
                        if($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Deal){
                            $rsEntity = \CCrmDeal::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['TITLE', 'ID']);

                            if($arEntity = $rsEntity->Fetch()) {
                                $entity = 'сделке';
                                $title = $arEntity['TITLE'];
                                $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_deal_show');
                                $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['deal_id' => $arEntity['ID']]);
                            }
                        }
                        elseif($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Contact){
                            $rsEntity = \CCrmContact::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['FULL_NAME', 'ID']);

                            if($arEntity = $rsEntity->Fetch()) {
                                $entity = 'контакте';
                                $title = $arEntity['FULL_NAME'];
                                $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');
                                $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arEntity['ID']]);
                            }
                        }
                        elseif($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Company){
                            $rsEntity = \CCrmCompany::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['TITLE', 'ID']);

                            if($arEntity = $rsEntity->Fetch()) {
                                $entity = 'компании';
                                $title = $arEntity['TITLE'];
                                $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_company_show');
                                $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arEntity['ID']]);
                            }
                        }

                        if(!empty($url))
                        {
                            $message = "Дело \"$name\" в $entity <a href=\"$url\">$title</a> просрочено на 3 часа.";

                            Tools::addNoteUser(
                                1,
                                $userId,
                                $message,
                                IM_NOTIFY_SYSTEM
                            );
                        }
                    }
                }
                else
                {
                    $timestamp = \MakeTimeStamp($arActivity['DEADLINE']) + 3600 * 3;

                    //создаем команду
                    $command = \Studiobit\Base\CommandManager::add();
                    $command->module('studiobit.project')
                        ->func('\Studiobit\Project\Command\Activity::importantOverdue(' . $id . ', ' . $userId . ')')
                        ->date(\ConvertTimeStamp($timestamp, 'FULL'))->save();
                }
            }
        }
    }

    public static function remindOverdueSms($id, $userId)
    {
        if($id && Loader::includeModule('crm') && Loader::includeModule('im')) {
            $rsActivity = \CCrmActivity::GetList(
                [],
                ['ID' => $id],
                false,
                false,
                ['ID', 'SUBJECT', 'DEADLINE', 'COMPLETED']
            );

            if ($arActivity = $rsActivity->fetch()) {
                $name = $arActivity['SUBJECT'];

                $arBindings = \CCrmActivity::GetBindings($id);

                foreach($arBindings as $arBinding){
                    $url = $entity = $title = '';
                    if($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Deal){
                        $rsEntity = \CCrmDeal::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['TITLE', 'ID']);

                        if($arEntity = $rsEntity->Fetch()) {
                            $entity = 'сделке';
                            $title = $arEntity['TITLE'];
                            $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_deal_show');
                            $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['deal_id' => $arEntity['ID']]);
                        }
                    }
                    elseif($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Contact){
                        $rsEntity = \CCrmContact::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['FULL_NAME', 'ID']);

                        if($arEntity = $rsEntity->Fetch()) {
                            $entity = 'контакте';
                            $title = $arEntity['FULL_NAME'];
                            $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');
                            $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arEntity['ID']]);
                        }
                    }
                    elseif($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Company){
                        $rsEntity = \CCrmCompany::GetListEx([], ['ID' => $arBinding['OWNER_ID']], false, false, ['TITLE', 'ID']);

                        if($arEntity = $rsEntity->Fetch()) {
                            $entity = 'компании';
                            $title = $arEntity['TITLE'];
                            $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_company_show');
                            $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arEntity['ID']]);
                        }
                    }

                    if(!empty($url))
                    {
                        $message = "Напоминание о деле \"$name\" в $entity \"$title\". Крайний срок: ".$arActivity['DEADLINE'];
                        //@TODO: заменить на смс
                        Tools::addNoteUser(
                            1,
                            $userId,
                            $message,
                            IM_NOTIFY_SYSTEM
                        );
                    }
                }
            }
        }
    }
}
?>
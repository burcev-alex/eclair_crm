<?php

namespace Studiobit\Project\Custom\Activities;

use Bitrix\Main\Error;
use Studiobit\Project;
use Studiobit\Base\Tools;

class Call extends \Bitrix\Crm\Activity\Provider\Call
{
    protected static function canUpdate($activity){
        $userId = $GLOBALS['USER']->GetID();

        if($userId)
        {
            $authorId = $activity['RESPONSIBLE_ID'];

            //редактировать свои дела может автор дела
            if ($userId == $authorId) {
                return true;
            }

            //редактировать любые дела может админ
            if ($GLOBALS['USER']->IsAdmin()) {
                return true;
            }

            //редактировать любые дела может КЦ
            $users = Tools::getUsersByDepartment(Project\CALL_CENTER_DEPARTMENT, true, true);

            if (in_array($userId, $users)) {
                return true;
            }

            //редактировать дела менеджера может вышестоящий начальник
            $arHeads = Tools::getAllUserManagers($authorId);

            if (in_array($userId, $arHeads)) {
                return true;
            }
        }

        return false;
    }

    public static function checkFields($action, &$fields, $id, $params = null)
    {
        $result = parent::checkFields($action, $fields, $id, $params);

        if($result->isSuccess() && $action == 'UPDATE')
        {
            $userId = $GLOBALS['USER']->GetID();

            if($userId)
            {
                if (self::canUpdate($params['PREVIOUS_FIELDS'])) {
                    return $result;
                }

                $error = 'У вас недостаточно прав для редактирования данного звонка';

                $result->addError(new Error($error));
                $GLOBALS['APPLICATION']->ThrowException(new \CAdminException([$error]));
            }
        }

        return $result;
    }

    public static function checkOwner()
    {
        return false;
    }

    public static function checkCompletePermission($entityId, array $activity, $userId)
    {
        $bUpdate = parent::checkCompletePermission($entityId, $activity, $userId);

        if(!$bUpdate)
        {
            $userId = $GLOBALS['USER']->GetID();

            if($userId) {
                if (self::canUpdate($activity)) {
                    $bUpdate = true;
                }
            }
        }

        return $bUpdate;
    }
}
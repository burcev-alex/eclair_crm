<?php

namespace Studiobit\Project\Controller;

use Bitrix\Main\Loader;
use Studiobit\Base\View;

/**
 * ajax-контроллер для дела
 */

class Activity extends Prototype
{
	public function timelineAction()
    {
        $this->view = new View\Json();

        $id = $this->getParam('id');

        if($id && Loader::includeModule('crm'))
        {
            $rsActivity = \CCrmActivity::GetList([], ['ID' => $id], false, false, ['ID', 'SUBJECT', 'DEADLINE', 'COMPLETED']);
            if($arActivity =$rsActivity->fetch())
            {
                if($arActivity['COMPLETED'] == 'N')
                {
                    $userId = $GLOBALS['USER']->GetID();
                    $messages = [];

                    if ($this->getParam('important') == 'Y')
                    {
                        $timestamp = \MakeTimeStamp($arActivity['DEADLINE']) + 3600 * 3;

                        //создаем команду
                        $command = \Studiobit\Base\CommandManager::add();
                        $command->module('studiobit.project')
                            ->func('\Studiobit\Project\Command\Activity::importantOverdue('.$id.', '.$userId.')')
                            ->date(\ConvertTimeStamp($timestamp, 'FULL'))->save();

                        $messages[] = 'Дело "'.$arActivity['SUBJECT'].'" отмечено как важное';
                    }

                    if ($this->getParam('remind') == 'Y')
                    {
                        $datetime = $this->getParam('date').' '.$this->getParam('time');

                        //создаем команду
                        $command = \Studiobit\Base\CommandManager::add();
                        $command->module('studiobit.project')
                            ->func('\Studiobit\Project\Command\Activity::remindOverdueSms('.$id.', '.$userId.')')
                            ->date($datetime)->save();

                        $messages[] = 'Для дела "'.$arActivity['SUBJECT'].'" добавлено напоминание';
                    }

                    return $messages;
                }
            }
        }

        return [];
    }
}
?>
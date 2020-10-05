<?php

namespace Studiobit\Project\Controller;

use Bitrix\Main\Loader;
use Studiobit\Base\Tools;
use Studiobit\Base\View;
use Studiobit\Project as Project;
use Studiobit\Project\Entity\Crm\ContactTable;

/**
 * ajax-контроллер для контакта
 */

class Contact extends Prototype
{
	public function searchAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            'studiobit.project:contact.search',
            '',
            [],
            $componentResult
        );
    }

    public function setStatusAction()
    {
        $this->view = new View\Json();

        $status = (int)$this->getParam('status');
        $id = (int)$this->getParam('id');
        
        if(Loader::includeModule('crm')){
            $contact = new \CCrmContact(false);
            $contact->Update($id, $fields = ['UF_CRM_STATUS' => $status]);
            return 'success';
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

    public function getStockAction()
    {
        $this->view = new View\Json();
        
        $users = Tools::getUsersByRoleName('Биржа');
        $userId = $GLOBALS['USER']->GetID();

        if (in_array($userId, $users)) {

            $return = [
                'ENABLED' => false,
                'ITEMS' => ContactTable::getStock()
            ];
            
            $timeOff = \CUserOptions::GetOption(\Studiobit\Project\MODULE_ID, 'stock_off_time', time());
            
            if(time() >= $timeOff || empty($timeOff))
                $return['ENABLED'] = true;
            
            return $return;
        }

        throw new \Exception('Не достаточно прав, для просмотра контактов на бирже.');
    }

    public function offStockAction()
    {
        $this->view = new View\Json();

        $users = Tools::getUsersByRoleName('Биржа');
        $userId = $GLOBALS['USER']->GetID();

        if (in_array($userId, $users)) {
            
            \CUserOptions::SetOption(\Studiobit\Project\MODULE_ID, 'stock_off_time', time() + 60*60);
            return true;
        }

        throw new \Exception('Не достаточно прав, для просмотра контактов на бирже.');
    }

    public function applyStockAction()
    {
        $this->view = new View\Json();

        $users = Tools::getUsersByRoleName('Биржа');
        $userId = $GLOBALS['USER']->GetID();

        if (in_array($userId, $users)) {

            $id = (int)$this->getParam('id');

            $items = ContactTable::getStock();

            if(isset($items[$id]))
            {
                foreach(ContactTable::getStatusList() as $enum){
                    if($enum['XML_ID'] == 'WORK') {
                        $fields = [
                            'ASSIGNED_BY_ID' => $userId,
                            'UF_CRM_STATUS'  => $enum['ID']
                        ];
                        
                        $contact = new \CCrmContact(false);
                        $contact->Update($id, $fields);

                        ContactTable::updateStock();
                    }
                }
            }
            else{
                throw new \Exception('Данный контакт уже взят в обработку');
            }

            return true;
        }

        throw new \Exception('Не достаточно прав, для просмотра контактов на бирже.');
    }

    public function createTradeInTaskAction()
    {
        $this->view = new View\Json();

        $id = (int)$this->getParam('id');
        $comment = $this->getParam('comment');

        if(Loader::includeModule('crm') && Loader::includeModule('bizproc'))
        {
            $rsDeal = \CCrmContact::GetListEx([], ['ID' => $id], false, false, ['ID']);

            if($arDeal = $rsDeal->Fetch())
            {
                $templateId = \Bitrix\Main\Config\Option::get(Project\MODULE_ID, 'b_contact_tradein_task', 55);

                $params = [
                    'MESSAGE' => $comment
                ];

                if($workflowId = \CBPDocument::StartWorkflow($templateId, ['crm', "CCrmDocumentContact", 'CONTACT_' . $id], $params, $errors = [])){
                    return $workflowId;
                }
                else{
                    throw new \Exception(implode('<br />', $errors));
                }
            }
            else{
                throw new \Exception('Контакт не найден.');
            }
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

	public function createMoratoriumTaskAction()
	{
		$this->view = new View\Json();

		$id = (int)$this->getParam('id');
		$comment = $this->getParam('comment');
		$agency = $this->getParam('agency');
		$realtor = $this->getParam('realtor');

		if(
			Loader::includeModule('crm') &&
			Loader::includeModule('bizproc') &&
			(strlen($comment) > 0) &&
			(strlen($agency) > 0) &&
			(strlen($realtor) > 0)
		)
		{
			$rsContact = \CCrmContact::GetListEx([], ['ID' => $id], false, false, ['ID']);

			if($arContact = $rsContact->Fetch())
			{
				$templateId = \Bitrix\Main\Config\Option::get(Project\MODULE_ID, 'b_contact_cancel_moratorium_task', 59);

				$params = [
					'MESSAGE' => $comment,
					'AGENCY' => $agency,
					'REALTOR' => $realtor,
				];

				if($workflowId = \CBPDocument::StartWorkflow($templateId, ['crm', "CCrmDocumentContact", 'CONTACT_' . $id], $params, $errors = [])){
					return $workflowId;
				}
				else{
					throw new \Exception(implode('<br />', $errors));
				}
			}
			else{
				throw new \Exception('Контакт не найден.');
			}
		}
		else{
			$errors = [];
			if(strlen($comment) == 0){
				$errors[] = 'comment';
			}
			if(strlen($agency) == 0){
				$errors[] = 'agency';
			}
			if(strlen($realtor) == 0){
				$errors[] = 'realtor';
			}

			throw new \Exception(implode('|', $errors));
		}

		throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
	}
}
?>
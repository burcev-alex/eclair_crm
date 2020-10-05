<?php

namespace Studiobit\Project\Controller;

use Studiobit\Base\View;
use Studiobit\Project\Entity\FavoriteTable;

/**
 * ajax-контроллер для избранного
 */

class Favorite extends Prototype
{
    public function statusAction()
    {
        $this->view = new View\Json();

        $entityId = (int)$this->getParam('entity_id');
        $entityType = strtoupper(trim($this->getParam('entity_type')));

        if($entityId > 0 && !empty($entityType)){
            $userId = $GLOBALS['USER']->GetID();

            $rs = FavoriteTable::getList([
                'filter' => [
                    'UF_ENTITY_ID' => $entityId,
                    'UF_ENTITY_TYPE' => $entityType,
                    'UF_USER' =>$userId
                ]
            ]);

            if($rs->fetch()){
                return 'favorite';
            }
            else
            {
                return 'no_favorite';
            }
        }

        throw new \Exception('Не корректные параметры');
    }

    public function addAction()
    {
        $this->view = new View\Json();

        $entityId = (int)$this->getParam('entity_id');
        $entityType = strtoupper(trim($this->getParam('entity_type')));

        if($entityId > 0 && !empty($entityType)){
            $userId = $GLOBALS['USER']->GetID();

            $rs = FavoriteTable::getList([
                'filter' => [
                    'UF_ENTITY_ID' => $entityId,
                    'UF_ENTITY_TYPE' => $entityType,
                    'UF_USER' =>$userId
                ]
            ]);

            if($rs->fetch()){

            }
            else
            {
                $result = FavoriteTable::add(
                    [
                        'UF_ENTITY_ID' => $entityId,
                        'UF_ENTITY_TYPE' => $entityType,
                        'UF_USER' =>$userId
                    ]
                );

                if(!$result->isSuccess()){
                    throw new \Exception(implode('<br />', $result->getErrorMessages()));
                }
            }

            $entityTypeId = \CCrmOwnerType::Deal;

            switch($entityType){
                case 'LEAD':
                    $entityTypeId = \CCrmOwnerType::Lead;
                    break;
                case 'CONTACT':
                    $entityTypeId = \CCrmOwnerType::Contact;
                    break;
                case 'COMPANY':
                    $entityTypeId = \CCrmOwnerType::Company;
                    break;
            }

            return  \CCrmOwnerType::GetDescription($entityTypeId).' "'.\CCrmOwnerType::GetCaption($entityTypeId, $entityId, false).'" успешно добавлена в избранное';
        }

        throw new \Exception('Не корректные параметры');
    }

    public function removeAction()
    {
        $this->view = new View\Json();

        $entityId = (int)$this->getParam('entity_id');
        $entityType = strtoupper(trim($this->getParam('entity_type')));

        if($entityId > 0 && !empty($entityType)){
            $userId = $GLOBALS['USER']->GetID();

            $rs = FavoriteTable::getList([
                'filter' => [
                    'UF_ENTITY_ID' => $entityId,
                    'UF_ENTITY_TYPE' => $entityType,
                    'UF_USER' =>$userId
                ]
            ]);

            if($ar = $rs->fetch()){
                $result = FavoriteTable::delete($ar['ID']);

                if(!$result->isSuccess()){
                    throw new \Exception(implode('<br />', $result->getErrorMessages()));
                }
            }

            $entityTypeId = \CCrmOwnerType::Deal;

            switch($entityType){
                case 'LEAD':
                    $entityTypeId = \CCrmOwnerType::Lead;
                    break;
                case 'CONTACT':
                    $entityTypeId = \CCrmOwnerType::Contact;
                    break;
                case 'COMPANY':
                    $entityTypeId = \CCrmOwnerType::Company;
                    break;
            }

            return  \CCrmOwnerType::GetDescription($entityTypeId).' "'.\CCrmOwnerType::GetCaption($entityTypeId, $entityId, false).'" удален из избранного';
        }

        throw new \Exception('Не корректные параметры');
    }
}
?>
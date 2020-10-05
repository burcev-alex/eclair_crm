<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменения пользовательских полей сущностей crm
 */
class CrmHistory extends UserfieldsHistory
{
    public static function includeModules()
    {
        \Bitrix\Main\Loader::includeModule('crm');
    }

    public function setEntity($entityType, $entity)
    {
        if(is_object($entity))
        {
            $cache['entities'][$entityType] = $entity;
        }
    }
    
    public function getEntity($entityType)
    {
        if(!isset($cache['entities']))
            $cache['entities'] = [];

        if(!isset($cache['entities'][$entityType]))
        {
            switch ($entityType) {
                case 'CONTACT':
                    $cache['entities'][$entityType] = new \CCrmContact;
                    break;
                case 'DEAL':
                    $cache['entities'][$entityType] = new \CCrmDeal;
                    break;
                case 'COMPANY':
                    $cache['entities'][$entityType] = new \CCrmCompany;
                    break;
                case 'LEAD':
                    $cache['entities'][$entityType] = new \CCrmLead;
                    break;
            }
        }
        
        return $cache['entities'][$entityType];
    }

    /**
     * метод который нужно вызвать до изменения сущности
     * @param $fields - изменяемые поля
     * @param string $entityType - тип сущности
     */
    public function before($fields, $entityType = 'CONTACT')
    {
        $this->entityType = $entityType;

        $arSelect = [];
        foreach($fields as $name => $value){
            if(strpos($name, 'UF_') !== false){
                $arSelect[] = $name;
            }
        }

        if(!empty($arSelect))
        {
            $entity = $this->getEntity($this->entityType);
            if($entity)
            {
                $arSelect[] = 'ID';
                $rsItem = $entity->GetListEx([], ['ID' => $fields['ID']], false, false, $arSelect);
                if ($arItem = $rsItem->Fetch())
                {
                    $this->arBeforeItem = $arItem;
                }
            }
        }
    }

    /**
     *Метод который нужно вызвать после изменения сущности
     */
    public function after()
    {
        if(!empty($this->arBeforeItem) && !empty($this->entityType))
        {
            $entity = $this->getEntity($this->entityType);
            if($entity)
            {
                $rsItem = $entity->GetListEx([], ['ID' => $this->arBeforeItem['ID']], false, false, array_keys($this->arBeforeItem));
                if ($arItem = $rsItem->Fetch())
                {
                    \p2log([
                            'TIMESTAMP' => \ConvertTimeStamp(false, 'FULL'),
                            'USER_ID' => $GLOBALS['USER']->GetID(),
                            'PREV' => $this->arBeforeItem,
                            'NEW' => $arItem,
                            'ENTITY_TYPE' => $this->entityType
                        ],
                        'history_'.\ConvertTimeStamp()
                    );

                    foreach($arItem as $key => $value){
                        if($value !== $this->arBeforeItem[$key]){
                            $this->add([
                                'ENTITY_TYPE' => $this->entityType,
                                'UF_ENTITY_TYPE' => 'CRM_'.$this->entityType,
                                'ENTITY_ID' => $arItem['ID'],
                                'FIELD_NAME' => $key,
                                'VALUE' => $value,
                                'BEFORE_VALUE' => $this->arBeforeItem[$key]
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function add($params)
    {
        parent::add([
            'ENTITY_TYPE'=> $params['ENTITY_TYPE'],
            'ENTITY_ID' => $params['ENTITY_ID'],
            'EVENT_NAME' => 'Поле "'.$this->getFieldName($params).'"',
            'EVENT_TEXT_1' => $this->getFieldHtml([
                'VALUE' => $params['BEFORE_VALUE'],
                'ENTITY_TYPE' => $params['ENTITY_TYPE'],
                'UF_ENTITY_TYPE' => $params['UF_ENTITY_TYPE'],
                'FIELD_NAME' => $params['FIELD_NAME']
            ]),
            'EVENT_TEXT_2' => $this->getFieldHtml([
                'VALUE' => $params['VALUE'],
                'ENTITY_TYPE' => $params['ENTITY_TYPE'],
                'UF_ENTITY_TYPE' => $params['UF_ENTITY_TYPE'],
                'FIELD_NAME' => $params['FIELD_NAME']
            ])
        ]);
    }
}
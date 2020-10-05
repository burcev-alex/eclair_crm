<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменения hl-блока
 */
class HLBlockHistory extends UserfieldsHistory
{
    /**
     * @return HLBlockHistory
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    public static function includeModules()
    {
        \Bitrix\Main\Loader::includeModule('highloadblock');
    }

    public function setEntityType($name)
    {
        $this->entityType = $name;
    }

    /**
     * @param $classname
     * @return Main\Entity\DataManager
     */
    public function getEntity($classname)
    {
        if (!isset($cache['entity'][$classname])) {
            $cache['entity'][$classname] = new $classname();
        }
        return $cache['entity'][$classname];
    }

    /**
     * метод который нужно вызвать до изменения сущности
     * @param $fields - изменяемые поля
     * @param string $entityType - тип сущности
     */
    public function before($fields, $classname)
    {
        $arSelect = array_keys($fields);

        if (!empty($arSelect))
        {
            $entity = $this->getEntity($classname);
            
            if(empty($this->entityType)) {
                $this->entityType = 'HLBLOCK_' . $entity->getEntityID();
            }

            if ($entity) {
                $arSelect[] = 'ID';

                $rsItem = $entity->getList([
                    'filter' => ['ID' => $fields['ID']],
                    'select' => $arSelect
                ]);

                if ($arItem = $rsItem->fetch()) {
                    $this->arBeforeItem = $arItem;
                }
            }
        }
    }

    /**
     *Метод который нужно вызвать после изменения сущности
     */
    public function after($classname)
    {
        if (!empty($this->arBeforeItem))
        {
            $entity = $this->getEntity($classname);
            if ($entity) {
                $rsItem = $entity->getList([
                    'filter' => ['ID' => $this->arBeforeItem['ID']],
                    'select' => array_keys($this->arBeforeItem)
                ]);

                if ($arItem = $rsItem->fetch())
                {
                    foreach ($arItem as $key => $value) {
                        if (trim($value) !== trim($this->arBeforeItem[$key])) {
                            if (strpos($key, 'UF_') !== false) {
                                $this->addUserField([
                                    'ENTITY_TYPE' => $this->entityType,
                                    'UF_ENTITY_TYPE' => $entity->getUfId(),
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
    }

    public function addUserField($params)
    {
        parent::add([
            'ENTITY_TYPE' => $params['ENTITY_TYPE'],
            'ENTITY_ID' => $params['ENTITY_ID'],
            'EVENT_NAME' => 'Поле "' . $this->getFieldName($params) . '"',
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
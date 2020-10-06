<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменения раздела инфоблока
 */
class IblockSectionHistory extends UserfieldsHistory
{
    public static function includeModules()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
    }

    public function getEntity()
    {
        if (!isset($cache['entity'])) {
            $cache['entity'] = new \CIBlockSection();
        }
        return $cache['entity'];
    }

    /**
     * метод который нужно вызвать до изменения сущности
     * @param $fields - изменяемые поля
     * @param string $entityType - тип сущности
     */
    public function before($fields)
    {
        $arSelect = array_keys($fields);

        $this->entityType = 'SECTION';

        if (!empty($arSelect))
        {
            $entity = $this->getEntity();
            if ($entity) {
                $arSelect[] = 'ID';
                $rsItem = $entity->GetList([], ['ID' => $fields['ID'], 'IBLOCK_ID' => $fields['IBLOCK_ID']], false, $arSelect);
                if ($arItem = $rsItem->Fetch()) {
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
        if (!empty($this->arBeforeItem)) {
            $entity = $this->getEntity();
            if ($entity) {
                $rsItem = $entity->GetList(
                    [],
                    ['ID' => $this->arBeforeItem['ID'], 'IBLOCK_ID' => $this->arBeforeItem['IBLOCK_ID']],
                    false,
                    array_keys($this->arBeforeItem)
                );

                if ($arItem = $rsItem->Fetch())
                {
                    foreach ($arItem as $key => $value) {
                        if ($value !== $this->arBeforeItem[$key]) {
                            if (strpos($key, 'UF_') !== false) {
                                $this->addUserField([
                                    'ENTITY_TYPE' => $this->entityType,
                                    'UF_ENTITY_TYPE' => 'IBLOCK_'.$this->arBeforeItem['IBLOCK_ID'].'_SECTION',
                                    'ENTITY_ID' => $arItem['ID'],
                                    'FIELD_NAME' => $key,
                                    'VALUE' => $value,
                                    'BEFORE_VALUE' => $this->arBeforeItem[$key]
                                ]);
                            }
                            else {
                                $this->addField([
                                    'ENTITY_TYPE' => $this->entityType,
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

    public function addField($params)
    {
        $name = $this->getFieldName($params);

        if(!empty($name))
        {
            parent::add([
                'ENTITY_TYPE' => $params['ENTITY_TYPE'],
                'ENTITY_ID' => $params['ENTITY_ID'],
                'EVENT_NAME' => 'Поле "' . $name . '"',
                'EVENT_TEXT_1' => $params['BEFORE_VALUE'],
                'EVENT_TEXT_2' => $params['VALUE']
            ]);
        }
    }

    protected function getFieldName($params)
    {
        $fieldName = $params['FIELD_NAME'];
        if (strpos($fieldName, 'UF_') !== false){
            return parent::getFieldName($params);
        }

        $arNames = [
            'NAME' => 'Название',
            'DESCRIPTION' => 'Описание'
        ];

        return $arNames[$fieldName];
    }
}
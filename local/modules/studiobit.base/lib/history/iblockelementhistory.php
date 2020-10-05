<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменения элемента инфоблока
 */
class IblockElementHistory extends Prototype
{
    protected $iblock_id;

    public static function includeModules()
    {
        \Bitrix\Main\Loader::includeModule('iblock');
    }

    public function getEntity()
    {
        if (!isset($cache['entity'])) {
            $cache['entity'] = new \CIBlockElement();
        }
        return $cache['entity'];
    }

    protected function getCurrentData($fields){
        $return = [];
        $arSelect = [];
        $arPropertyFilter = [];
        $properties = $this->getFields($fields['IBLOCK_ID']);

        foreach($fields as $key => $value){
            if($key == 'PROPERTY_VALUES')
            {
                $arPropertyCodes = [];
                foreach($value as $propID => $propVal)
                {
                    if(is_integer($propID)){
                        $arPropertyFilter[] = $propID;
                    }
                    else{
                        $arPropertyCodes[] = $propID;
                    }
                }

                if(!empty($arPropertyCodes))
                {
                    foreach ($properties as $propID => $arProperty)
                    {
                        if(in_array($arProperty['CODE'], $arPropertyCodes))
                            $arPropertyFilter[] = $propID;
                    }
                }
            }
            else{
                $arSelect[] = $key;
            }
        }

        if (!empty($arSelect))
        {
            $entity = $this->getEntity();
            if ($entity)
            {
                $arSelect[] = 'ID';
                $rsItem = $entity->GetList([], ['ID' => $fields['ID'], 'IBLOCK_ID' => $fields['IBLOCK_ID']], false, false, $arSelect);
                if ($arItem = $rsItem->Fetch()) {
                    $return = $arItem;
                }

                if(!empty($arPropertyFilter)){
                    $return['PROPERTY_VALUES'] = [];
                    $rsPropValues = \CIBlockElement::GetPropertyValues($fields['IBLOCK_ID'], ['ID' => $fields['ID']], false, ['ID' => $arPropertyFilter]);
                    if($arPropValues = $rsPropValues->Fetch())
                    {
                        unset($arPropValues['IBLOCK_ELEMENT_ID']);
                        $return['PROPERTY_VALUES'] = $arPropValues;
                    }
                }
            }
        }

        return $return;
    }

    /**
     * метод который нужно вызвать до изменения сущности
     * @param $fields - изменяемые поля
     * @param string $entityType - тип сущности
     */
    public function before($fields)
    {
        $this->iblock_id = $fields['IBLOCK_ID'];
        $this->entityType = $this->getEntityType();
        $this->arBeforeItem = $this->getCurrentData($fields);
    }

    /**
     *Метод который нужно вызвать после изменения сущности
     */
    public function after()
    {
        if (!empty($this->arBeforeItem)) {
            $entity = $this->getEntity();
            if ($entity) {
                $arItem = $this->getCurrentData($this->arBeforeItem);

                foreach ($arItem as $key => $value)
                {
                    if($key == 'PROPERTY_VALUES')
                    {
                        foreach($value as $propID => $propValue)
                        {
                            if ($propValue !== $this->arBeforeItem['PROPERTY_VALUES'][$propID]) {
                                $this->addEvent([
                                    'ENTITY_TYPE' => $this->entityType,
                                    'UF_ENTITY_TYPE' => $this->entityType,
                                    'ENTITY_ID' => $arItem['ID'],
                                    'FIELD_NAME' => $propID,
                                    'VALUE' => $propValue,
                                    'BEFORE_VALUE' => $this->arBeforeItem['PROPERTY_VALUES'][$propID]
                                ]);
                            }
                        }
                    }
                    else
                    {
                        if ($value !== $this->arBeforeItem[$key]) {
                            $this->addEvent([
                                'ENTITY_TYPE' => $this->entityType,
                                'UF_ENTITY_TYPE' => $this->entityType,
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

    public function getEntityType()
    {
        return 'ELEMENT';
    }

    public function addEvent($params)
    {
        $name = $this->getFieldName($params);

        if(!empty($name))
        {
            parent::add([
                'ENTITY_TYPE' => $params['ENTITY_TYPE'],
                'ENTITY_ID' => $params['ENTITY_ID'],
                'EVENT_NAME' => 'Поле "' . $name . '"',
                'EVENT_TEXT_1' => $this->getFieldHtml([
                    'VALUE' => $params['BEFORE_VALUE'],
                    'FIELD_NAME' => $params['FIELD_NAME']
                ]),
                'EVENT_TEXT_2' => $this->getFieldHtml([
                    'VALUE' => $params['VALUE'],
                    'FIELD_NAME' => $params['FIELD_NAME']
                ])
            ]);
        }
    }

    /**
     * Название пользовательского поля
     * @param $params
     * @return string
     */
    protected function getFieldName($params)
    {
        $fieldName = $params['FIELD_NAME'];
        if(is_numeric($fieldName))
        {
            $properties = $this->getFields($this->iblock_id);

            if (isset($properties[$fieldName])) {
                $arProperty = $properties[$fieldName];
                return $arProperty['NAME'];
            }
        }

        $arNames = [
            'NAME' => 'Название',
            'PREVIEW_TEXT' => 'Описание',
            'DETAIL_TEXT' => 'Описание',
            'IBLOCK_SECTION_ID' => 'Раздел'
        ];

        return $arNames[$fieldName];
    }

    /** html-представление пользовательского поля
     * @param $params
     * @return string
     */
    protected function getFieldHtml($params)
    {
        if ($params['VALUE'] == "" || (is_array($params['VALUE']) && !count($params['VALUE'])))
            return '-Пусто-';

        $propertyID = $params['FIELD_NAME'];
        $properties = $this->getFields($this->iblock_id);

        if(isset($properties[$propertyID]))
        {
            $arProperty = $properties[$propertyID];
            $arProperty['~VALUE'] = $arProperty['VALUE'] = $params['VALUE'];
            
            if($arProperty['PROPERTY_TYPE'] == 'L'){
                $value = ['DISPLAY_VALUE' => $arProperty['VALUE']];
                $rsVal = \CIBlockProperty::GetPropertyEnum($arProperty['ID'], [], ['ID' => $arProperty['VALUE']]);
                if($arVal = $rsVal->Fetch())
                    $value['DISPLAY_VALUE'] = $arVal['VALUE'];
            }
            else
                $value =  \CIBlockFormatProperties::GetDisplayValue([], $arProperty, '');

            if(is_array($value['DISPLAY_VALUE']))
                return implode('<br />', $value['DISPLAY_VALUE']);
            else
                return $value['DISPLAY_VALUE'];
        }

        return $params['VALUE'];
    }

    /**
     * Все пользовательские поля сущности
     * @param $entityType
     * @return mixed
     */
    protected function getFields($iblock_id)
    {
        $cache = new Base\Cache($iblock_id, __CLASS__, self::CACHE_TIME);
        if ($cache->start())
        {
            $data = [];
            $rsProperties = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblock_id]);
            while ($arProp = $rsProperties->Fetch())
            {
                $data[$arProp['ID']] = $arProp;
            }
            $cache->end($data);
        }
        else
        {
            $data = $cache->getVars();
        }

        return $data;
    }
}
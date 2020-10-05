<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменений пользовтаельских полей сущности
 */
abstract class UserfieldsHistory extends Prototype
{
    /**
     * Название пользовательского поля
     * @param $params
     * @return string
     */
    protected function getFieldName($params)
    {
        $fieldName = $params['FIELD_NAME'];
        $fields = $this->getFields($params['UF_ENTITY_TYPE']);

        if(isset($fields[$fieldName]))
        {
            $arUserField = $fields[$fieldName];
            return !empty($arUserField['EDIT_FORM_LABEL']) ? $arUserField['EDIT_FORM_LABEL'] : $fieldName;
        }

        return '';
    }

    /** html-представление пользовательского поля
     * @param $params
     * @return string
     */
    protected function getFieldHtml($params)
    {
        if ($params['VALUE'] == "" || (is_array($params['VALUE']) && !count($params['VALUE'])))
            return '-Пусто-';

        $fieldName = $params['FIELD_NAME'];
        $fields = $this->getFields($params['UF_ENTITY_TYPE']);

        if(isset($fields[$fieldName]))
        {
            $arUserField = $fields[$fieldName];
            ob_start();
            $arUserField['VALUE'] = $params['VALUE'];

            if($arUserField["USER_TYPE"]["USER_TYPE_ID"] == 'file'){
                if($params['VALUE'] > 0) {
                    $arFile = \CFile::GetFileArray($params['VALUE']);
                    echo '<a target="_blank" href="'.htmlspecialcharsbx($arFile["SRC"]).'">'.htmlspecialcharsbx($arFile["FILE_NAME"]).'</a> ('.\CFile::FormatSize($arFile["FILE_SIZE"]).')';
                }
                else{
                    echo '-Пусто-';
                }
            }
            else
            {
                $GLOBALS['APPLICATION']->IncludeComponent(
                    "bitrix:system.field.view",
                    $arUserField["USER_TYPE"]["USER_TYPE_ID"],
                    array("arUserField" => $arUserField),
                    null,
                    array("HIDE_ICONS" => "Y")
                );
            }

            return ob_get_clean();
        }

        return '-Пусто-';
    }

    /**
     * Все пользовательские поля сущности
     * @param $entityType
     * @return mixed
     */
    protected function getFields($entityType)
    {
        $cache = new Base\Cache($entityType, __CLASS__, self::CACHE_TIME);
        if ($cache->start())
        {
            $data = $GLOBALS["USER_FIELD_MANAGER"]->getUserFields($entityType, 0, LANGUAGE_ID);
            $cache->end($data);
        }
        else
        {
            $data = $cache->getVars();
        }

        return $data;
    }
}
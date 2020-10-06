<?php
namespace Studiobit\Project\Entity;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('Keys');

/**
 сущность для HL-блока Ключ->Значение
 *
 * @method static int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class KeysTable extends \KeysBaseTable
{
    /**
     * @param \Bitrix\Main\Entity\Base $entity
     *
     * @throws \Bitrix\Main\LoaderException
     */
    public static function prepareEntity(&$entity)
    {

    }

    public static function set($key, $value){
        $rs = self::getList([
            'filter' => ['UF_KEY' => $key],
            'select' => ['ID']
        ]);

        if($ar = $rs->fetch()){
            self::update($ar['ID'], ['UF_VALUE' => $value]);
        }
        elseif(strlen(trim($key))){
            self::add(['UF_KEY' => trim($key), 'UF_VALUE' => $value]);
        }
    }

    public static function get($key, $value, $default_value = ''){
        $rs = self::getList([
            'filter' => ['UF_KEY' => $key],
            'select' => ['ID', 'UF_VALUE']
        ]);

        if($ar = $rs->fetch()){
            return $ar['UG_VALUE'];
        }

        return $default_value;
    }
}
?>
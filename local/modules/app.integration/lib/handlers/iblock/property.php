<?php

namespace App\Integration\Handlers\Iblock;

use \App\Integration as Union;

class Property
{
    /**
     * После добавления свойства в инфоблоке.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockPropertyAdd(&$arFields)
    {
        $arrSites = Union\Constructor::getSites();
        
        if(IntVal($arFields['IBLOCK_ID']) == 0){
            $arProperty = \CIBlockProperty::GetByID($arFields['ID'], false)->Fetch();
            $arFields['IBLOCK_ID'] = $arProperty['IBLOCK_ID'];
        }

        foreach ($arrSites as $siteId) {
            // конфигурация обмена
            $entityConfigSync = new Union\Constructor(0, $siteId, $arFields['IBLOCK_ID']);
            $configSync = $entityConfigSync->get();

            // вытягиваем ID внешнего инфоблока из конфигурации зависимости
            $arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);

            if (IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0) {
                $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
                $response = $endpoint->property('add', $arFields);
            }
        }
    }

    /**
     * После изменения свойства в инфоблоке.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockPropertyUpdate(&$arFields)
    {
        $arrSites = Union\Constructor::getSites();
        
        if(IntVal($arFields['IBLOCK_ID']) == 0){
            $arProperty = \CIBlockProperty::GetByID($arFields['ID'], false)->Fetch();
            $arFields['IBLOCK_ID'] = $arProperty['IBLOCK_ID'];
        }

        foreach ($arrSites as $siteId) {
            // конфигурация обмена
            $entityConfigSync = new Union\Constructor(0, $siteId, $arFields['IBLOCK_ID']);
            $configSync = $entityConfigSync->get();

            // вытягиваем ID внешнего инфоблока из конфигурации зависимости
            $arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);
            
            if (IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0) {
                $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
                $response = $endpoint->property('update', $arFields);
            }
        }
    }

    /**
     * После удаления свойства в инфоблоке.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockPropertyDelete(&$arFields)
    {
        $arrSites = Union\Constructor::getSites();

        foreach ($arrSites as $siteId) {
            // конфигурация обмена
            $entityConfigSync = new Union\Constructor(0, $siteId, $arFields['IBLOCK_ID']);
            $configSync = $entityConfigSync->get();

            // вытягиваем ID внешнего инфоблока из конфигурации зависимости
            $arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);

            if (IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0) {
                $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
                $response = $endpoint->property('delete', $arFields);
            }
        }
    }
}

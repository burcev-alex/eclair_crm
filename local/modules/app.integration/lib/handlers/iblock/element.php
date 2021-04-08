<?php

namespace App\Integration\Handlers\Iblock;

use \App\Base;
use \App\Integration as Union;

class Element
{
    /**
     * До добавления элемента в инфоблок.
     *
     * @param array $arFields
     * @return void
     */
    public static function onBeforeIBlockElementAdd(&$arFields)
    {
        $arFields['XML_ID'] = randString(12);
        $arFields['EXTERNAL_ID'] = $arFields['XML_ID'];
    }

    /**
     * После добавления элемента в инфоблок.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockElementAdd(&$arFields)
    {
        unlink($_SERVER['DOCUMENT_ROOT'].'/1-dump.html');

        \CModule::IncludeModule('catalog');
        \CModule::IncludeModule('iblock');

        $data = Base\Tools::getElementByIDWithProps($arFields['ID']);
        if(array_key_exists('PROPERTIES', $data) && array_key_exists('CML2_LINK', $data['PROPERTIES']['CML2_LINK'])){
            if(IntVal($data['PROPERTIES']['CML2_LINK']['VALUE']) > 0){
                $parent = Base\Tools::getElementByIDWithProps($data['PROPERTIES']['CML2_LINK']['VALUE']);
            }
            else{
                $parent = [];
            }
        }
        else{
            $parent = [];
        }

        $externalSectionId = false;
        if (IntVal($arFields['IBLOCK_SECTION_ID']) > 0) {
            $dbSection = \CIBlockSection::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'ID' => $arFields['IBLOCK_SECTION_ID']]);
            if ($arSection = $dbSection->Fetch()) {
                $data['IBLOCK_SECTION_DATA'] = $arSection;
                $externalSectionId = $arSection['XML_ID'] ? $arSection['XML_ID'] : $arSection['CODE'];
            } else {
                $data['IBLOCK_SECTION_DATA'] = [];
            }
        } else {
            $data['IBLOCK_SECTION_DATA'] = [];
        }

        if (array_key_exists('IBLOCK_SECTION', $arFields)) {
            $dbSection = \CIBlockSection::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'ID' => $arFields['IBLOCK_SECTION']]);
            $data['IBLOCK_SECTION_DATA'] = [];
            while ($arSection = $dbSection->Fetch()) {
                $data['IBLOCK_SECTION_DATA'] = $arSection;
                $externalSectionId = $arSection['XML_ID'] ? $arSection['XML_ID'] : $arSection['CODE'];
            }
        } else {
            $data['IBLOCK_SECTION_DATA'] = [];
        }

        $data['IBLOCK_SECTION_ID'] = $externalSectionId;

        // найти корневой раздел
        if(count($parent) == 0){
            $data['SECTION_PARENT'] = Union\Tools::getParentSection($data['IBLOCK_SECTION_DATA']['ID']);
            $nullSectionId = array_shift($data['SECTION_PARENT']);

            if (IntVal($nullSectionId) == 0) {
                $nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
            }
        }
        else{
            $data['SECTION_PARENT'] = Union\Tools::getParentSection($parent['IBLOCK_SECTION_ID']);
            $nullSectionId = array_shift($data['SECTION_PARENT']);

            if (IntVal($nullSectionId) == 0 && count($parent) > 0) {
                $nullSectionId = IntVal($parent['IBLOCK_SECTION_ID']);
            }
        }

        // конфигурация обмена
        $entityConfigSync = new Union\Constructor($nullSectionId);
        $configSync = $entityConfigSync->get();

        // вытягиваем ID внешнего инфоблока из конфигурации зависимости
        $data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);

        if (IntVal($data['PREVIEW_PICTURE']) > 0) {
            $data['PREVIEW_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PREVIEW_PICTURE']);
        }

        if (IntVal($data['DETAIL_PICTURE']) > 0) {
            $data['DETAIL_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['DETAIL_PICTURE']);
        }

        foreach ($data['PROPERTIES'] as $propertyCode => $propValues) {
            if (! array_key_exists('PROPERTY_TYPE', $propValues)) {
                foreach ($propValues as $key => $firstValue) {
                    if ($firstValue['PROPERTY_TYPE'] == 'E') {
                        $value = $firstValue['VALUE'];
                        $rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($firstValue['VALUE'])], false, false, ['ID', 'XML_ID']);
                        if ($arBindElement = $rsBindElement->Fetch()) {
                            $value = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
                        }
                        $data['PROPERTIES'][$propertyCode][$key]['VALUE'] = $value;
                    }
                }
            } else {
                if ($propValues['PROPERTY_TYPE'] == 'E') {
                    $data['PROPERTIES'][$propertyCode]['VALUE'] = $propValues['VALUE'];
                    $rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($propValues['VALUE'])], false, false, ['ID', 'XML_ID']);
                    while ($arBindElement = $rsBindElement->Fetch()) {
                        $data['PROPERTIES'][$propertyCode]['VALUE'] = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
                    }
                }
            }
        }

        $rsPrice = \CPrice::GetList(
            [
                'ID' => 'DESC'
            ],
            [
                'PRODUCT_ID' => $arFields['ID'],
                'CATALOG_GROUP_ID' => 1
            ]
        );

        if ($arrPrice = $rsPrice->Fetch()) {
            $data['PRICE'] = $arrPrice;
        }

        
        p2f($data);
        p2f($configSync);
        p2f($nullSectionId);

        if (IntVal($data['IBLOCK_EXTERNAL_ID']) > 0) {
            $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
            $response = $endpoint->product('add', $data);

            // HARD CODE / по возможности через API определить ID инфоблока торговых предложений
            if(IntVal($arFields['ID']) > 0){
                /*
                $rsOfferElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => 18, 'PROPERTY_CML2_LINK'=>$arFields['ID']], false, false, ['ID', 'ACTIVE']);
                while ($arOfferElement = $rsOfferElement->Fetch()) {
                    p2f($arOfferElement);
                    $el = new \CIBlockElement();
                    $arOfferFields = [
                        'ACTIVE' => $arOfferElement['ACTIVE']
                    ];
                    $el->Update($arOfferElement['ID'], $arOfferFields);
                }
                */
            }
        }
    }

    /**
     * После изменения элемента в инфоблоке.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockElementUpdate(&$arFields)
    {
        unlink($_SERVER['DOCUMENT_ROOT'].'/1-dump.html');

        \CModule::IncludeModule('catalog');

        $data = Base\Tools::getElementByIDWithProps($arFields['ID']);
        if(
            array_key_exists('PROPERTIES', $data) && 
            array_key_exists('CML2_LINK', $data['PROPERTIES']) && 
            strlen($data['PROPERTIES']['CML2_LINK']['VALUE']) > 0){
            $parent = Base\Tools::getElementByIDWithProps($data['PROPERTIES_VALUE']['CML2_LINK']);

            if(count($parent) == 0){
                $rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['XML_ID' => $data['PROPERTIES']['CML2_LINK']['VALUE']], false, false, ['ID', 'XML_ID']);
                if ($arBindElement = $rsBindElement->Fetch()) {
                    $parent = Base\Tools::getElementByIDWithProps($arBindElement['ID']);
                }
            }
        }
        else{
            $parent = [];
        }

        $externalSectionId = false;
        if (IntVal($arFields['IBLOCK_SECTION_ID']) > 0) {
            $dbSection = \CIBlockSection::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'ID' => IntVal($arFields['IBLOCK_SECTION_ID'])]);
            if ($arSection = $dbSection->Fetch()) {
                $data['IBLOCK_SECTION_DATA'] = $arSection;
                $externalSectionId = $arSection['XML_ID'] ? $arSection['XML_ID'] : $arSection['CODE'];
            } else {
                $data['IBLOCK_SECTION_DATA'] = [];
            }
        } else {
            $data['IBLOCK_SECTION_DATA'] = [];
        }

        if (array_key_exists('IBLOCK_SECTION', $arFields)) {
            $dbSection = \CIBlockSection::GetList([], ['IBLOCK_ID' => $arFields['IBLOCK_ID'], 'ID' => $arFields['IBLOCK_SECTION']]);
            $data['IBLOCK_SECTION_DATA'] = [];
            while ($arSection = $dbSection->Fetch()) {
                $data['IBLOCK_SECTION_DATA'] = $arSection;
                $externalSectionId = $arSection['XML_ID'] ? $arSection['XML_ID'] : $arSection['CODE'];
            }
        } else {
            $data['IBLOCK_SECTION_DATA'] = [];
        }

        $data['IBLOCK_SECTION_ID'] = $externalSectionId;

        // найти корневой раздел
        if(count($parent) == 0){
            $data['SECTION_PARENT'] = Union\Tools::getParentSection($data['IBLOCK_SECTION_DATA']['ID']);
            $nullSectionId = array_shift($data['SECTION_PARENT']);

            if (IntVal($nullSectionId) == 0) {
                $nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
            }
        }
        else{
            $data['SECTION_PARENT'] = Union\Tools::getParentSection($parent['IBLOCK_SECTION_ID']);
            $nullSectionId = array_shift($data['SECTION_PARENT']);

            if (IntVal($nullSectionId) == 0 && count($parent) > 0) {
                $nullSectionId = IntVal($parent['IBLOCK_SECTION_ID']);
            }
        }

        // конфигурация обмена
        $entityConfigSync = new Union\Constructor($nullSectionId);
        $configSync = $entityConfigSync->get();

        // вытягиваем ID внешнего инфоблока из конфигурации зависимости
        $data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);

        if (IntVal($data['PREVIEW_PICTURE']) > 0) {
            $data['PREVIEW_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['PREVIEW_PICTURE']);
        }

        if (IntVal($data['DETAIL_PICTURE']) > 0) {
            $data['DETAIL_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['DETAIL_PICTURE']);
        }

        foreach ($data['PROPERTIES'] as $propertyCode => $propValues) {
            if (! array_key_exists('PROPERTY_TYPE', $propValues)) {
                foreach ($propValues as $key => $firstValue) {
                    if ($firstValue['PROPERTY_TYPE'] == 'E') {
                        $value = $firstValue['VALUE'];
                        $rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($firstValue['VALUE'])], false, false, ['ID', 'XML_ID']);
                        if ($arBindElement = $rsBindElement->Fetch()) {
                            $value = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
                        }
                        $data['PROPERTIES'][$propertyCode][$key]['VALUE'] = $value;
                    }
                }
            } else {
                if ($propValues['PROPERTY_TYPE'] == 'E') {
                    $data['PROPERTIES'][$propertyCode]['VALUE'] = $propValues['VALUE'];
                    $rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($propValues['VALUE'])], false, false, ['ID', 'XML_ID']);
                    while ($arBindElement = $rsBindElement->Fetch()) {
                        $data['PROPERTIES'][$propertyCode]['VALUE'] = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
                    }
                }
            }
        }

        $rsPrice = \CPrice::GetList(
            [
                'ID' => 'DESC'
            ],
            [
                'PRODUCT_ID' => $arFields['ID'],
                'CATALOG_GROUP_ID' => 1
            ]
        );

        if ($arrPrice = $rsPrice->Fetch()) {
            $data['PRICE'] = $arrPrice;
        }

        p2f($data);

        if (IntVal($data['IBLOCK_EXTERNAL_ID']) > 0) {
            $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
            $response = $endpoint->product('update', $data);
        }
    }

    /**
     * После удаления элемента в инфоблоке.
     *
     * @param array $arFields
     * @return void
     */
    public static function onAfterIBlockElementDelete(&$arFields)
    {
        // найти корневой раздел
        $arFields['SECTION_PARENT'] = Union\Tools::getParentSection($arFields['IBLOCK_SECTION_ID']);
        $nullSectionId = array_shift($arFields['SECTION_PARENT']);
        if (IntVal($nullSectionId) == 0) {
            $nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
        }

        // конфигурация обмена
        $entityConfigSync = new Union\Constructor($nullSectionId);
        $configSync = $entityConfigSync->get();

        // вытягиваем ID внешнего инфоблока из конфигурации зависимости
        $arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields['IBLOCK_ID']);

        if (IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0) {
            $endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
            $response = $endpoint->product('delete', $arFields);
        }
    }
}

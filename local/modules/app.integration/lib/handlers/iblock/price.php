<?php

namespace App\Integration\Handlers\Iblock;

use \Bitrix\Main;
use \App\Base;
use \App\Integration as Union;

class Price {
	/**
	 * После добавления цены товара
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onPriceAdd($ID, &$arFields){
		\CModule::IncludeModule('catalog');
		\CModule::IncludeModule('iblock');

		$data = Base\Tools::getElementByIDWithProps($arFields['PRODUCT_ID']);
		$parent = Base\Tools::getElementByIDWithProps($data['PROPERTIES']['CML2_LINK']['VALUE']);

		// найти корневой раздел
		$arrSectionParent = Union\Tools::getParentSection($parent['IBLOCK_SECTION_ID']);
		$nullSectionId = array_shift($arrSectionParent);

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);

		if(IntVal($data['PREVIEW_PICTURE']) > 0){
			$data['PREVIEW_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['PREVIEW_PICTURE']);
		}

		if(IntVal($data['DETAIL_PICTURE']) > 0){
			$data['DETAIL_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['DETAIL_PICTURE']);
		}

		foreach ($data['PROPERTIES'] as $propertyCode => $propValues) {
			if(!array_key_exists("PROPERTY_TYPE", $propValues)){
				foreach($propValues as $key=>$firstValue){
					if ($firstValue['PROPERTY_TYPE'] == 'E') {

						$value = $firstValue['VALUE'];
						$rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($firstValue['VALUE'])], false, false, ['ID', 'XML_ID']);
						if ($arBindElement = $rsBindElement->Fetch()) {
							$value = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
						}
						$data['PROPERTIES'][$propertyCode][$key]['VALUE'] = $value;

					}
				}
			}
			else{
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
			array(
				'ID' => 'DESC'
			),
			array(
					"PRODUCT_ID" => $arFields['PRODUCT_ID'],
					"CATALOG_GROUP_ID" => 1
				)
		);

		if ($arrPrice = $rsPrice->Fetch())
		{
			$data['PRICE'] = $arrPrice;
		}

		if(IntVal($data['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->product("add", $data);
		}
	}

	/**
	 * После изменения цены товара
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onPriceUpdate($ID, &$arFields){
		\CModule::IncludeModule('iblock');
		\CModule::IncludeModule('catalog');

		$data = Base\Tools::getElementByIDWithProps($arFields['PRODUCT_ID']);
		$parent = Base\Tools::getElementByIDWithProps($data['PROPERTIES']['CML2_LINK']['VALUE']);

		// найти корневой раздел
		$arrSectionParent = Union\Tools::getParentSection($parent['IBLOCK_SECTION_ID']);
		$nullSectionId = array_shift($arrSectionParent);

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);


		if(IntVal($data['PREVIEW_PICTURE']) > 0){
			$data['PREVIEW_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['PREVIEW_PICTURE']);
		}

		if(IntVal($data['DETAIL_PICTURE']) > 0){
			$data['DETAIL_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['DETAIL_PICTURE']);
		}

		foreach ($data['PROPERTIES'] as $propertyCode => $propValues) {
			if(!array_key_exists("PROPERTY_TYPE", $propValues)){
				foreach($propValues as $key=>$firstValue){
					if ($firstValue['PROPERTY_TYPE'] == 'E') {

						$value = $firstValue['VALUE'];
						$rsBindElement = \CIBlockElement::GetList(['SORT' => 'ASC'], ['ID' => IntVal($firstValue['VALUE'])], false, false, ['ID', 'XML_ID']);
						if ($arBindElement = $rsBindElement->Fetch()) {
							$value = $arBindElement['XML_ID'] ? $arBindElement['XML_ID'] : $arBindElement['ID'];
						}
						$data['PROPERTIES'][$propertyCode][$key]['VALUE'] = $value;

					}
				}
			}
			else{
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
			array(
				'ID' => 'DESC'
			),
			array(
					"PRODUCT_ID" => $arFields['PRODUCT_ID'],
					"CATALOG_GROUP_ID" => 1
				)
		);

		if ($arrPrice = $rsPrice->Fetch())
		{
			$data['PRICE'] = $arrPrice;
		}
		
		if(IntVal($data['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->product("update", $data);
		}
	}
	
	/**
	 * После удаления цены товара
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onPriceDelete(&$arFields){
		
	}
}
?>
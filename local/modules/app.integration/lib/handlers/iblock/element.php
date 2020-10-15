<?php

namespace App\Integration\Handlers\Iblock;

use \Bitrix\Main;
use \App\Base;
use \App\Integration as Union;

class Element {
	
	/**
	 * После добавления элемента в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockElementAdd(&$arFields){
		\CModule::IncludeModule('catalog');
		\CModule::IncludeModule('iblock');

		$data = Base\Tools::getElementByIDWithProps($arFields['ID']);

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$data['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		if(IntVal($data['PREVIEW_PICTURE']) > 0){
			$data['PREVIEW_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PREVIEW_PICTURE']);
		}

		if(IntVal($data['DETAIL_PICTURE']) > 0){
			$data['DETAIL_PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['DETAIL_PICTURE']);
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
					"PRODUCT_ID" => $arFields['ID'],
					"CATALOG_GROUP_ID" => 1
				)
		);

		if ($arrPrice = $rsPrice->Fetch())
		{
			$data['PRICE'] = $arrPrice;
		}

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("add", $data);
	}

	/**
	 * После изменения элемента в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockElementUpdate(&$arFields){
		\CModule::IncludeModule('catalog');

		$data = Base\Tools::getElementByIDWithProps($arFields['ID']);

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$data['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

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
					"PRODUCT_ID" => $arFields['ID'],
					"CATALOG_GROUP_ID" => 1
				)
		);

		if ($arrPrice = $rsPrice->Fetch())
		{
			$data['PRICE'] = $arrPrice;
		}
		
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("update", $data);
	}
	
	/**
	 * После удаления элемента в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockElementDelete(&$arFields){
		
		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("delete", $arFields);
	}
}
?>
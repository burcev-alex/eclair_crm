<?php

namespace App\Integration\Handlers\Iblock;

use \Bitrix\Main;
use \App\Base;
use \App\Integration as Union;

class Property {
	
	/**
	 * После добавления свойства в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockPropertyAdd(&$arFields){

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->property("add", $arFields);
	}
	
	/**
	 * После изменения свойства в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockPropertyUpdate(&$arFields){

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->property("update", $arFields);
	}
	
	/**
	 * После удаления свойства в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockPropertyDelete(&$arFields){

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];
		
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->property("delete", $arFields);
	}
}
?>
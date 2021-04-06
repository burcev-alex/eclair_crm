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

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor(0, $arFields["IBLOCK_ID"]);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);


		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->property("add", $arFields);
		}
	}
	
	/**
	 * После изменения свойства в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockPropertyUpdate(&$arFields){

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor(0, $arFields["IBLOCK_ID"]);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);


		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->property("update", $arFields);
		}
	}
	
	/**
	 * После удаления свойства в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockPropertyDelete(&$arFields){

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor(0, $arFields["IBLOCK_ID"]);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);

		
		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->property("delete", $arFields);
		}
	}
}
?>
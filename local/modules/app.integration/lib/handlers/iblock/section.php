<?php

namespace App\Integration\Handlers\Iblock;

use \Bitrix\Main;
use \App\Base;
use \App\Integration as Union;

class Section {
	/**
	 * До добавления раздела в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onBeforeIBlockSectionAdd(&$arFields){
		$arFields['XML_ID'] = randString(12);
		$arFields['EXTERNAL_ID'] = $arFields['XML_ID'];
	}

	/**
	 * После добавления раздела в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionAdd(&$arFields){

		$arFields['PARENT'] = Union\Tools::getParentSection($arFields['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($arFields['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
		}

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields["IBLOCK_ID"]);
		
		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arFields["IBLOCK_ID"], 'ID' => $arFields["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$arFields['IBLOCK_SECTION_DATA'] = $arSection;
		}
		
		if(IntVal($arFields['PICTURE']) > 0){
			$arFields['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PICTURE']);
		}

		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("add", $arFields);
		}
	}

	/**
	 * После изменения раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionUpdate(&$arFields){

		$arFields['PARENT'] = Union\Tools::getParentSection($arFields['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($arFields['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
		}

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields["IBLOCK_ID"]);

		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arFields["IBLOCK_ID"], 'ID' => $arFields["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$arFields['IBLOCK_SECTION_DATA'] = $arSection;
		}

		if(IntVal($arFields['PICTURE']) > 0){
			$arFields['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PICTURE']);
		}
		
		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("update", $arFields);
		}
	}
	
	/**
	 * После удаления раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionDelete(&$arFields){

		$arFields['PARENT'] = Union\Tools::getParentSection($arFields['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($arFields['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($arFields['IBLOCK_SECTION_ID']);
		}

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$arFields['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($arFields["IBLOCK_ID"]);

		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($arFields['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("delete", $arFields);
		}
	}
}
?>
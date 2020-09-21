<?php

namespace App\Integration\Handlers\Iblock;

use \Bitrix\Main;
use \App\Base;
use \App\Integration as Union;

class Section {
	/**
	 * После добавления раздела в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionAdd(&$arFields){
		
		$arFields['PARENT'] = Base\Tools::getParentSection($arFields['ID']);

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		if(IntVal($arFields['PICTURE']) > 0){
			$arFields['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PICTURE']);
		}

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->section("add", $arFields);
	}

	/**
	 * После изменения раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionUpdate(&$arFields){

		$arFields['PARENT'] = Base\Tools::getParentSection($arFields['ID']);

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		if(IntVal($arFields['PICTURE']) > 0){
			$arFields['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($arFields['PICTURE']);
		}
		
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->section("update", $arFields);
	}
	
	/**
	 * После удаления раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionDelete(&$arFields){

		$arIblock = \CIBlock::GetByID($arFields["IBLOCK_ID"])->Fetch();
		$arFields['IBLOCK_EXTERNAL_ID'] = $arIblock['XML_ID'];

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->section("delete", $arFields);
	}
}
?>
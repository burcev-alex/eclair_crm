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
		
		$arFields['PARENT'] = Base\Tools::getParentSection($arFields['ID']);
		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arFields["IBLOCK_ID"], 'ID' => $arFields["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$arFields['IBLOCK_SECTION_DATA'] = $arSection;
		}

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
		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $arFields["IBLOCK_ID"], 'ID' => $arFields["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$arFields['IBLOCK_SECTION_DATA'] = $arSection;
		}

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
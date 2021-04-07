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
	 * Редактирование раздела в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onBeforeIBlockSectionUpdate(&$arFields){
        $arSection = \CIBlockSection::GetByID($arFields["ID"])->Fetch();
        if(strlen($arSection['XML_ID']) == 0){
		    $arFields['XML_ID'] = randString(12);
		    $arFields['EXTERNAL_ID'] = $arFields['XML_ID'];
        }
	}

	/**
	 * После добавления раздела в инфоблок
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionAdd(&$arFields){

        $data = $arFields;

		$data['PARENT'] = Union\Tools::getParentSection($data['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($data['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($data['IBLOCK_SECTION_ID']);
		}

        if(IntVal($nullSectionId) == 0){
            $nullSectionId = $data["IBLOCK_SECTION_ID"];
        }

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);
		
		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $data["IBLOCK_ID"], 'ID' => $data["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$data['IBLOCK_SECTION_DATA'] = $arSection;
		}
		
		if(IntVal($data['PICTURE']) > 0){
			$data['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['PICTURE']);
		}

		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($data['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("add", $data);
		}
	}

	/**
	 * После изменения раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionUpdate(&$arFields){
        $data = $arFields;

		$dbSection = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $data["IBLOCK_ID"], 'ID' => $data["IBLOCK_SECTION_ID"]));
		if($arSection = $dbSection->Fetch()){
			$data['IBLOCK_SECTION_DATA'] = $arSection;
		}

		$data['PARENT'] = Union\Tools::getParentSection($data['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($data['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($data['IBLOCK_SECTION_ID']);
		}

        if(IntVal($nullSectionId) == 0){
            $nullSectionId = $data["IBLOCK_SECTION_ID"];
        }

        if($data["IBLOCK_SECTION_ID"] == $nullSectionId){
            $data["IBLOCK_SECTION_ID"] = false;
            $data['IBLOCK_SECTION_DATA']['ID'] = false;
            $data['IBLOCK_SECTION_DATA']['DEPTH_LEVEL'] = 0;
        }

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);

		if(IntVal($data['PICTURE']) > 0){
			$data['PICTURE'] = Union\Tools::siteURL().\CFile::GetPath($data['PICTURE']);
		}
        
		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($data['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("update", $data);
		}
	}
	
	/**
	 * После удаления раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionDelete(&$arFields){
        $data = $arFields;

		$data['PARENT'] = Union\Tools::getParentSection($data['ID']);

		// найти корневой раздел
		$nullSectionId = array_shift($data['PARENT']);
		if(IntVal($nullSectionId) == 0){
			$nullSectionId = IntVal($data['IBLOCK_SECTION_ID']);
		}

        if(IntVal($nullSectionId) == 0){
            $nullSectionId = $data["IBLOCK_SECTION_ID"];
        }

		// конфигурация обмена
		$entityConfigSync = new Union\Constructor($nullSectionId);
		$configSync = $entityConfigSync->get();

		// вытягиваем ID внешнего инфоблока из конфигурации зависимости
		$data['IBLOCK_EXTERNAL_ID'] = $entityConfigSync->getIblockExternalId($data["IBLOCK_ID"]);
        
		// если есть привязка к инфоблоку, обмен разрешен
		if(IntVal($data['IBLOCK_EXTERNAL_ID']) > 0){
			$endpoint = new Union\Rest\Client\Web($configSync['host'], $configSync['url'], $configSync['token']);
			$response = $endpoint->section("delete", $data);
		}
	}
}
?>
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

		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("add", $arFields);
	}

	/**
	 * После изменения раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionUpdate(&$arFields){

		$arFields['PARENT'] = Base\Tools::getParentSection($arFields['ID']);
		
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("update", $arFields);
	}
	
	/**
	 * После удаления раздела в инфоблоке
	 *
	 * @param array $arFields
	 * @return void
	 */
	public static function onAfterIBlockSectionDelete(&$arFields){
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("delete", $arFields);
	}
}
?>
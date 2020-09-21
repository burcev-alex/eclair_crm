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

		$data = Base\Tools::getElementByIDWithProps($arFields['ID']);

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

		$data = Base\Tools::getElementByIDWithProps($arFields['ID']);
		
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
		$endpoint = new Union\Rest\Client\Web();
		$response = $endpoint->product("delete", $arFields);
	}
}
?>
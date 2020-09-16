<?php

namespace App\Integration\Rest\Client;

use \Bitrix\Main\Entity\Query;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Loader;
use \Bitrix\Crm;
use \Bitrix\Main\Entity;
use \App\Base\Tools;
use \Bitrix\Main\Type\Date;
use App\Project\Entity\Crm\ContactTable;
use App\Project\Entity\Crm\LeadTable;
use \App\Integration as Union;

/**
 * Class AbstractBase
 * @package App\Integration\Rest\Client
 */
abstract class AbstractBase
{
	/**
	 * Формирование блока ошибки(проблемы на стороне сервиса) для последующего логирования
	 *
	 * @param $response_json
	 * @param $result
	 * @param $url
	 * @param $data
	 */
	protected function getWarningMessage(&$response_json, $result, $url, $data)
	{
		if(IntVal($result->info->http_code) != 200){
			$response_json = [
				'http_code' => $result->info->http_code,
				'url' => $url,
				'request' => $data,
				'errors' => $response_json
			];
		}
	}

	/**
	 * Фатальная ошибка
	 *
	 * @param $url
	 * @param $data
	 *
	 * @return array
	 */
	protected function getErrorMessage($url, $data)
	{
		$response = [
			'http_code' => json_last_error(),
			'url' => $url,
			'request' => $data,
			'errors' => ''
		];

		return $response;
	}
}

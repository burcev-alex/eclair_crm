<?php

namespace App\Integration\Queue;

use \Bitrix\Main;
use \App\Integration as Union;

/**
 * Базовый класс от которому будут наследования
 *
 * Class AbstractBase
 * @package App\Integration\Queue
 */
abstract class AbstractBase implements Union\Queue\Host
{
	protected $resource;
	protected $authorId = 1;
	protected $responsibleId = 1;
	protected $responce = [];

	/**
	 * Слушатель очереди, вытягиваем информацию
	 * @return $this
	 */
	public function init($request)
	{
		// формируем данные которые вытягиваются из очереди
		// в последствии отдаются в command на выполнение
		$this->resource = $request;

		return $this;
	}

	public function setResource($request)
	{
		$this->resource = $request;
	}

	public function result()
	{
		return $this->responce;
	}
}

<?php

namespace App\Base\View;

use App\Base as Base;

class Json extends Prototype
{
	/**
	 * Создает новый MVC JSON view
	 *
	 * @param mixed $data Данные view
	 * @return void
	 */
	public function __construct($data = array())
	{
		$this->data = $data;
	}
	
	/**
	 * Отсылает http-заголовки для view
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		header('Content-type: application/json');
	}
	
	/**
	 * Формирует view
	 *
	 * @return string
	 */
	public function render()
	{
        return json_encode($this->data);
	}
}
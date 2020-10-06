<?php

namespace Studiobit\Base\View;

use Studiobit\Base as Base;

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
		if (defined('SITE_CHARSET') && SITE_CHARSET != 'UTF-8') {
			return json_encode(Base\Util::convertCharset($this->data, SITE_CHARSET, 'UTF-8'));
		} else {
			return json_encode($this->data);
		}
	}
}
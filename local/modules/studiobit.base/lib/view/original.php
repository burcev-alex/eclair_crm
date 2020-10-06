<?php

namespace Studiobit\Base\View;

use Studiobit\Base as Base;

class Original extends Prototype
{
	/**
	 * Создает новый MVC HTML view
	 *
	 * @param string $data HTML текст
	 * @return void
	 */
	public function __construct($data = '')
	{
		$this->data = $data;
	}
    
	/**
	 * Формирует view
	 *
	 * @return string
	 */
	public function render()
	{
        return is_array($this->data) ? implode('', $this->data) : $this->data;
	}
}
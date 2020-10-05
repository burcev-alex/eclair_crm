<?php
namespace Studiobit\Base\View;

use Studiobit\Base as Base;

class Prototype
{
	/**
	 * Каталог по умолчанию для файлов view
	 *
	 * @var string
	 */
	protected $baseDir = '';

	/**
	 * Имя view
	 *
	 * @var string
	 */
	protected $name = '';
	
	/**
	 * Данные view
	 *
	 * @var mixed
	 */
	protected $data = array();
	
	/**
	 * Создает новый MVC view
	 *
	 * @param string $name Название шаблона view
	 * @param mixed $data Данные view
	 * @return void
	 */
	public function __construct($name = '', $data = array())
	{
		if (!$this->baseDir) {
			$this->baseDir = \Studiobit\Base\BASE_DIR . '/views/';
		}
		$this->name = $name;
		$this->data = $data;
	}
	
	/**
	 * Отсылает http-заголовки для view
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
	}
	
	/**
	 * Формирует view
	 *
	 * @return string
	 */
	public function render()
	{
		throw new Base\Exception("Abstract view can't be rendered.");
	}
	
	/**
	 * Устанавливает данные
	 *
	 * @param mixed $data Данные
	 * @return void
	 */
	public function setData($data)
	{
		$this->data = $data;
	}
	
	/**
	 * Устанавливает базовый каталог
	 *
	 * @param string $dir Базовый каталог
	 * @return void
	 */
	public function setBaseDir($dir)
	{
		$this->baseDir = $dir;
	}
}
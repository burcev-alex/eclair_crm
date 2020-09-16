<?php

namespace App\Base\Controller;

use App\Base as Base;

class Prototype
{
	/**
	 * Request
	 *
	 * @var \Bitrix\Main\Context\HttpRequest
	 */
	protected $request = null;
	
	/**
	 * View
	 *
	 * @var Base\View\Prototype|null
	 */
	protected $view = null;
	
	/**
	 * Вернуть возвращенные экшном данные как есть, без признака success
	 *
	 * @var boolean
	 */
	protected $returnAsIs = false;
	
	/**
	 * Параметры
	 *
	 * @var array
	 */
	protected $params = array();

    /**
     * Создает новый контроллер
     *
     * @return void
     * @throws \Bitrix\Main\SystemException
     */
	public function __construct()
	{
		$this->request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
	}

    /**
     * "Фабрика" контроллеров
     *
     * @param string $name Имя сущности
     * @return \App\Base\Controller\Prototype
     * @throws Base\Exception
     */
	public static function factory($namespace = '', $name)
	{
		if(empty($namespace))
            $namespace = __NAMESPACE__;

        $name = preg_replace('/[^A-z0-9_]/', '', $name);
		$className = '\\' . $namespace . '\\' . ucfirst($name);
		
		if (!class_exists($className)) {
			throw new Base\Exception(sprintf('Controller "%s" doesn\'t exists.', $className));
		}
		
		return new $className();
	}

    /**
     * Выполняет экшн контроллера
     *
     * @param string $name Имя экшена
     * @return mixed
     * @throws Base\Exception
     */
	public function doAction($name)
	{
		$name = preg_replace('/[^A-z0-9_]/', '', $name);
		$methodName = $name . 'Action';
		
		if (!method_exists($this, $methodName)) {
			throw new Base\Exception(sprintf('Action "%s" doesn\'t exists.', $name));
		}
		
		//JSON view by default
		$this->view = new Base\View\Json();
		
		$response = new \stdClass();
		$response->success = false;
		try {
			$response->data = call_user_func(array($this, $methodName));
			$response->success = true;
		} catch(\Exception $e) {
			$response->code = $e->getCode();
			$response->message = $e->getMessage();
		} catch(\Bitrix\Main\SystemException $e) {
			$response->code = $e->getCode();
			$response->message = $e->getMessage();
		} catch(\Bitrix\Main\ArgumentException $e) {
			$response->code = $e->getCode();
			$response->message = $e->getMessage();
		} catch(\Bitrix\Main\ObjectException $e) {
			$response->code = $e->getCode();
			$response->message = $e->getMessage();
		}
		
		try {
			$this->view->setData($this->returnAsIs ? (
				isset($response->data) ? $response->data : null
			) : $response);
			$this->view->sendHeaders();
			print $this->view->render();
		} catch(\Exception $e) {
			print $e->getMessage();
		}

        if($this->returnAsIs && !$response->success){
            print $response->message;
        }
	}
	
	/**
	 * Возвращает код, сгенерированный компонентом Bitrix
	 *
	 * @param string $name Имя компонента
	 * @param string $template Шаблон компонента
	 * @param array $params Параметры компонента
	 * @param mixed $componentResult Данные, возвращаемые компонентом
	 * @return string
	 */
	protected function getComponent($name, $template = '', $params = array(), &$componentResult = null)
	{
		ob_start();
		$componentResult = $GLOBALS['APPLICATION']->IncludeComponent($name, $template, $params);
		$result = ob_get_clean();
		
		return $result;
	}
	
	/**
	 * Возвращает код, сгенерированный включаемой областью Bitrix
	 *
	 * @param string $path Путь до включаемой области
	 * @param array $params Массив параметров для подключаемого файла
	 * @param array $function_params Массив настроек данного метода
	 * @return string
	 */
	protected function getIncludeArea($path, $params = array(), $function_params = array())
	{
		ob_start();
		$includeResult = $GLOBALS['APPLICATION']->IncludeFile($path, $params, $function_params);
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
	
	/**
	 * Устанавливает параметры из пар в массиве
	 *
	 * @param array $pairs Пары [ключ][значение]
	 * @return void
	 */
	public function setParamsPairs($pairs)
	{
		foreach ($pairs as $name => $value) {
			$this->params[$name] = $value;
		}
	}
	
	/**
	 * Возвращает значение входного параметра
	 *
	 * @param string $name Имя параметра
	 * @param mixed $default Значение по умолчанию
	 * @return mixed
	 */
	protected function getParam($name, $default = '')
	{
		$result = array_key_exists($name, $this->params)
			? $this->params[$name]
			: $this->request->get($name);
		
		return $result === null ? $default : $result;
	}

    /**
     * @return \CUser
     */
    protected function getUser(){
	    return $GLOBALS['USER'];
    }
}
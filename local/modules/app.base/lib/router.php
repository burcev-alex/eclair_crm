<?php

namespace App\Base;

/*класс для обработки ajax-запросов и вызова нужных методов контролеров*/

class Router
{
	/**
	 * Request
	 *
	 * @var \Bitrix\Main\Context\HttpRequest
	 */
	protected $request = null;
    const EVENT = 'onRegisterNamespaceForRouter';
	
	public function __construct()
	{
		$this->request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
	}
	
	public function execute()
	{
        $uri = $this->request->getRequestUri();
        $uriParts = explode('/', $uri);

        $isAjax = false;
        $parts = array();

        foreach($uriParts as $part){
            if(empty($part))
                continue;

            if($part == 'ajax'){
                $isAjax = true;
                continue;
            }

            if($isAjax && strpos($part, '?') === false && strpos($part, '.php') === false)
            {
                $parts[] = strtolower($part);
            }
        }

        $namespace = 'App\Base\Controller';

        if(count($parts) == 2) //ajax-контроллер базового модуля
        {
            $class = $parts[0];
            $action = $parts[1];
        }
        else//внешний ajax-контроллер базового модуля
        {
            //вызываем событие, чтобы получить список псевдонимов для пространств имен
            //например, matrix - \Studibobit\Matrix\Controller
            
            $event = new \Bitrix\Main\Event('app.base', self::EVENT);
            $event->send();

            $arRouteToNamespace = array();

            foreach ($event->getResults() as $eventResult)
            {
                if($eventResult->getType() !== \Bitrix\Main\EventResult::ERROR)
                {
                    $handlerRes = $eventResult->getParameters();
                    $arRouteToNamespace[$handlerRes['ROUTE']] = $handlerRes['NAMESPACE'];
                }
            }

            $route = $parts[0];
            
            if(isset($arRouteToNamespace[$route])){
                $namespace = $arRouteToNamespace[$route];
            }
            $class = $parts[1];
            $action = $parts[2];
        }

        //получаем экземпляр контроллера
        $controller = \App\Base\Controller\Prototype::factory($namespace, $class);
        
        //вызываем метод
        return $controller->doAction($action);
	}
}
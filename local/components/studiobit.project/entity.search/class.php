<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/*
 * Базовый класс для компонентов поиска сущностей crm
 * */

use \Studiobit\Base;
use \Bitrix\Main;
use \Bitrix\Main\Loader;

Main\Localization\Loc::loadMessages(__FILE__);

class CStudiobitEntitySearchComponent extends CBitrixComponent
{
    /**
	 * Преодопределение параметров
	 * @param $params
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		$params = parent::onPrepareComponentParams($params);
		return $params;
	}

    protected function getUser(){
        global $USER;
        return $USER;
    }

    protected function doAction()
    {
        $action = $this->request->get('action');		
        if (check_bitrix_sessid())
        {
            if (is_callable([$this, $action . "Action"])) {
                call_user_func(
                    [$this, $action . "Action"]
                );
            }
        }
    }

    protected function listAction()
    {

		
        $return = [
            'result' => 'success',
            'items' => $this->getItems()
        ];
				
        $this->jsonResponse($return);
    }

    protected function jsonResponse($json){
        global $APPLICATION;
        $APPLICATION->RestartBuffer();

        if(!defined('PUBLIC_AJAX_MODE'))
        {
            define('PUBLIC_AJAX_MODE', true);
        }

        header('Content-type: application/json');
        echo json_encode($json);
        require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
        die();
    }

    public function getItems()
    {
        return [];
    }


    public function executeComponent(){
		
        if(Loader::includeModule('studiobit.project') && Loader::includeModule('crm'))
        {
            $this->doAction();
            $this->includeComponentTemplate();

            return $this->arResult;
        }
        else
        {
            ShowError(GetMessage('F_NO_MODULE'));
            return false;
        }
    }
}
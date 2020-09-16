<?php
namespace App\Base\Component;

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Single extends \CBitrixComponent
{
    public function getUser(){
        global $USER;
        return $USER;
    }

    protected function doAction()
    {
        if (check_bitrix_sessid())
        {
            $action = $this->request->getPost('action');

            if(empty($action))
                $action = $this->request->get('action');

            if (!empty($action) && is_callable([$this, $action . 'Action'])) {
                call_user_func(
                    [$this, $action . 'Action']
                );
            }
        }
    }

    public function jsonResponse($json){
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

    public function getTitle()
    {
        return '';
    }

    public function setTitle()
    {
        if($this->arParams['SET_TITLE']){
            global $APPLICATION;
            $APPLICATION->SetTitle($this->getTitle());
        }
    }
}
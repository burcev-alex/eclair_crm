<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Studiobit\Base;
use \Bitrix\Main;
use \Bitrix\Main\Loader;

Main\Localization\Loc::loadMessages(__FILE__);

class CStudiobitJsDefineComponent extends CBitrixComponent
{
    protected function getUser(){
        global $USER;
        return $USER;
    }

    protected function getData(){
        $this->arResult['DATA'] = [
            'DefaultDealCategoryId' => 0, //направление сделки по умолчанию,
            'BusinessCardText' => '', //текст длявизитки,
            'bAdmin' => $this->getUser()->IsAdmin()
        ];

        if($this->getUser()->IsAuthorized()){
            $rsUser = \CUser::GetByID($this->getUser()->GetID());
            $arUser = $rsUser->Fetch();

            $fullname = \CUser::FormatName(\CSite::GetDefaultNameFormat(), $arUser);
            $phone = $arUser['PERSONAL_PHONE'];

            $this->arResult['DATA']['BusinessCardText'] = 'ГК "Стрижи": '.$fullname.' , тел: '.$phone;
        }
    }

    public function executeComponent(){
        if(Loader::includeModule('studiobit.project') && Loader::includeModule('crm'))
        {
            $this->getData();
            $this->includeComponentTemplate();
        }
        else
        {
            return false;
        }
    }
}
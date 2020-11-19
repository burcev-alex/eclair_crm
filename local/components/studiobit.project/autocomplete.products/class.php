<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Studiobit\Base;
use \Bitrix\Main;
use \Bitrix\Main\Loader;

Main\Localization\Loc::loadMessages(__FILE__);

class CStudiobitAutocompleteProductsComponent extends CBitrixComponent
{

    public function executeComponent(){
        if(Loader::includeModule('studiobit.project') && Loader::includeModule('crm'))        {

            $this->includeComponentTemplate();
        }
        else
        {
            return false;
        }
    }
}

<?
namespace Studiobit\Project\Handlers;

use Bitrix\Crm\Integration\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Studiobit\Project as Project;

Loc::loadMessages(__FILE__);

/*класс для обработчиков событий модуля main*/

class Main
{
    public static function onPageStart()
    {
        global $APPLICATION;

        //логирование всех запросов
        //\p2log([$APPLICATION->GetCurPage(true), $_REQUEST], 'requests');
        
        //перехватываем поиск сущностей crm
        if($_REQUEST['studiobit_crm_select_deal'] == 'Y'){
            Project\Custom\CrmSelect::searchDeal();
        }
        elseif($_REQUEST['studiobit_crm_select_friend'] == 'Y'){
            Project\Custom\CrmSelect::searchFriend();
        }

        if($APPLICATION->GetCurPage() == '/bitrix/components/bitrix/crm.activity.planner/slider.php'){
            Loader::includeModule('crm');
            
            switch ( $_REQUEST['TYPE_ID'])
            {
                case \CCrmActivityType::Meeting:
                    $_REQUEST['PROVIDER_ID'] = Project\Custom\Activities\Meeting::getId();
                    break;
                case \CCrmActivityType::Call:
                    $_REQUEST['PROVIDER_ID'] = Project\Custom\Activities\Call::getId();
                    break;
            }
        }
    }

    public static function OnProlog()
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();		
        if(!$request->isAdminSection())
        {
            $asset = Asset::getInstance();
            //файл для кастомных стилей, подключается на всех страницах
            $asset->addCss('/local/static/css/'.Project\MODULE_ID.'/custom.css');
            //файл для кастомных скриптов, подключается на всех страницах
            $asset->addJs('/local/static/js/'.Project\MODULE_ID.'/custom.js');
            \CJSCore::Init([
                'studiobit_project_favorite',
                'studiobit_project_stock'
            ]);

            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:js.define',
                '',
                [],
                null,
                ['HIDE_ICONS' => 'Y']
            );
            $js = ob_get_clean();
            $asset->addString($js, 'STUDIBOT_JS_DEFINE');
        }
    }

    public static function onBeforeUserAdd($fields)
    {
    	// если ADMIN_NOTES заполнено, то это первичная загрузка данных
	    // проверку не делаем
	    if(strlen($fields['ADMIN_NOTES']) > 0){
	    	return true;
	    }

        $errors = [];
        /*if (empty($fields['PERSONAL_PHOTO']))
        {
            $errors[] = 'Не заполнена фотография';
        }

        if (empty($fields['PERSONAL_PHONE']))
        {
            $errors[] = 'Не заполнен личный телефон';
        }*/

        if (!empty($errors))
        {
            global $APPLICATION;

            $APPLICATION->ThrowException(implode('<br />', $errors));

            return false;
        }

        return true;
    }

    public static function onBeforeUserUpdate($fields)
    {
        $errors = [];
        /*if (isset($fields['PERSONAL_PHOTO']) && empty($fields['PERSONAL_PHOTO']))
        {
            $errors[] = 'Не заполнена фотография';
        }

        if (isset($fields['PERSONAL_PHONE']) && empty($fields['PERSONAL_PHONE']))
        {
            $errors[] = 'Не заполнен личный телефон';
        }*/

        /*
        if(count($fields['UF_CONTROLLER']) > 0) {
	        // найти контролеров
	        $arControllerUserList = Project\Tools::getDepartmentController($fields['UF_DEPARTMENT']);

	        $currentUser = false;
	        foreach ($arControllerUserList as $key => $user) {
		        if ($user['ID'] == $fields['ID']) {
			        $currentUser = true;
		        }
	        }
	        if (count($arControllerUserList) > 0) {
		        if (!$currentUser) {
			        $errors[] = 'В поздразделении может быть только один контролер';
		        }
	        }
        }
        */

        if (!empty($errors))
        {
            global $APPLICATION;

            $APPLICATION->ThrowException(implode('<br />', $errors));

            return false;
        }

        return true;
    }

    public static function OnBeforeEventAdd($event, $lid, $arFields, $message_id, $files, $languageId){
        if(in_array($event, ['IM_NEW_MESSAGE', 'IM_NEW_MESSAGE_GROUP', 'IM_NEW_NOTIFY_GROUP', 'IM_NEW_NOTIFY'])){
            return false;
        }
    }
}

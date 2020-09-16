<?
namespace App\Base\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use App\Base;

Loc::loadMessages(__FILE__);

/*класс для обработчиков событий модуля main*/

class Main
{
    public static function OnProlog()
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if(!$request->isAdminSection())
        {
	        //подключаем js ядро базового модуля и обработчик путей для слайдера
	        UI\Extension::load(['app', 'app_route']);
        }

        //инициализация уровней доступа модуля
        $rights = new Base\Rights(Base\MODULE_ID);
        $rights->Init();
    }
}

<?
namespace Studiobit\Base\Handlers;

use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Studiobit\Base as Base;

Loc::loadMessages(__FILE__);

/*класс для обработчиков событий модуля main*/

class Main
{
    public static function OnProlog()
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if(!$request->isAdminSection())
        {
            \CJSCore::Init('studiobit_route'); //подключаем js ядро базового модуля и обработчик путей для слайдера
        }

        //инициализация уровней доступа модуля
        $rights = new Base\Rights(Base\MODULE_ID);
        $rights->Init();
    }
}

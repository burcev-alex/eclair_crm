<?
namespace App\Base;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Event
{
    /**
     * Добавляет обработчики событий
     *
     * @return void
     */
    public static function setupEventHandlers()
    {
        $localDir = realpath(BASE_DIR . '/../..');
        if(file_exists($localDir . '/vendor/autoload.php')) {
            require_once($localDir . '/vendor/autoload.php');
        }

        if(defined('APP_EVENT_HANDLERS_DISABLED'))
            return;

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        //main
        $eventManager->addEventHandler('main', 'OnProlog', ['\App\Base\Handlers\Main', 'OnProlog']);

	    // новый тип свойства
	    $eventManager->addEventHandler('main', 'OnUserTypeBuildList', ['\App\Base\UserField\SliderRange', 'GetUserTypeDescription']);
        $eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\App\Base\UserField\Double', 'GetUserTypeDescription']);
        $eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\App\Base\UserField\PropertyIblock', 'GetUserTypeDescription']);
        $eventManager->addEventHandler('main', 'OnUserTypeBuildList', ['\App\Base\UserField\Html', 'GetUserTypeDescription']);

        $event = new \Bitrix\Main\Event('app.base', 'setupEventHandlers');
        $event->send();
    }
}

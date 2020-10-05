<?
namespace Studiobit\Base;

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
        // $localDir = realpath(BASE_DIR . '/../..');
        // require_once($localDir . '/vendor/autoload.php');
        
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        
        //main
        $eventManager->addEventHandler('main', 'OnProlog', ['\Studiobit\Base\Handlers\Main', 'OnProlog']);
        
        //pull
        $eventManager->addEventHandler('pull', "OnGetDependentModule", ['\Studiobit\Base\PullSchema', 'OnGetDependentModule']);

	    // новый тип свойства
	    $eventManager->addEventHandler('main', 'OnUserTypeBuildList', ['\Studiobit\Base\UserField\SliderRange', 'GetUserTypeDescription']);
        $eventManager->addEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Studiobit\Base\UserField\Double', 'GetUserTypeDescription']);

        $event = new \Bitrix\Main\Event('studiobit.base', 'setupEventHandlers');
        $event->send();
    }
}

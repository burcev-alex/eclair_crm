<?

namespace App\Integration;

use Bitrix\Main;
use Bitrix\Main\Application;

class Event
{
	public static function onPageStart()
	{
		self::setupEventHandlers();
	}

	/**
	 * Добавляет обработчики событий
	 *
	 * @return void
	 */
	protected static function setupEventHandlers()
	{
		$eventManager = Main\EventManager::getInstance();

		// element of iblock
		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockElementAdd', ['\App\Integration\Handlers\Iblock\Element', 'onBeforeIBlockElementAdd']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementAdd', ['\App\Integration\Handlers\Iblock\Element', 'onAfterIBlockElementAdd']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', ['\App\Integration\Handlers\Iblock\Element', 'onAfterIBlockElementUpdate']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockElementDelete', ['\App\Integration\Handlers\Iblock\Element', 'onAfterIBlockElementDelete']);

		// property of iblock
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockPropertyAdd', ['\App\Integration\Handlers\Iblock\Property', 'onAfterIBlockPropertyAdd']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockPropertyUpdate', ['\App\Integration\Handlers\Iblock\Property', 'onAfterIBlockPropertyUpdate']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockPropertyDelete', ['\App\Integration\Handlers\Iblock\Property', 'onAfterIBlockPropertyDelete']);

		// sections of iblock
		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockSectionAdd', ['\App\Integration\Handlers\Iblock\Section', 'onBeforeIBlockSectionAdd']);
		$eventManager->addEventHandler('iblock', 'OnBeforeIBlockSectionUpdate', ['\App\Integration\Handlers\Iblock\Section', 'onBeforeIBlockSectionUpdate']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionAdd', ['\App\Integration\Handlers\Iblock\Section', 'onAfterIBlockSectionAdd']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionUpdate', ['\App\Integration\Handlers\Iblock\Section', 'onAfterIBlockSectionUpdate']);
		$eventManager->addEventHandler('iblock', 'OnAfterIBlockSectionDelete', ['\App\Integration\Handlers\Iblock\Section', 'onAfterIBlockSectionDelete']);

		// price of iblock
		$eventManager->addEventHandler('catalog', 'OnPriceAdd', ['\App\Integration\Handlers\Iblock\Price', 'onPriceAdd']);
		$eventManager->addEventHandler('catalog', 'OnPriceUpdate', ['\App\Integration\Handlers\Iblock\Price', 'onPriceUpdate']);
		$eventManager->addEventHandler('catalog', 'OnPriceDelete', ['\App\Integration\Handlers\Iblock\Price', 'onPriceDelete']);
	}
}

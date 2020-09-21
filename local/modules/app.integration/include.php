<?php
namespace App\Integration;

/**
 * Базовый каталог модуля
 */
const BASE_DIR = __DIR__;
/**
 * Имя модуля
 */
const MODULE_ID = 'app.integration';

IncludeModuleLangFile(__FILE__);

$arClassBase = array(
    // rest client
    '\App\Integration\Rest\Client\Web' => 'lib/rest/client/web.php',
    '\App\Integration\Rest\Client\AbstractBase' => 'lib/rest/client/abstractBase.php',

	// rest server
	'\App\Integration\Rest\Server\Main' => 'lib/rest/server/main.php',

	'\App\Integration\Event' => 'lib/event.php',
	'\App\Integration\Tools' => 'lib/tools.php',

	'\App\Integration\Entity\Crm\ActivityTable' => 'lib/entity/crm/activity.php',
    '\App\Integration\Entity\Crm\ContactTable' => 'lib/entity/crm/contact.php',
    '\App\Integration\Entity\Crm\LeadTable' => 'lib/entity/crm/lead.php',

	'\App\Integration\Queue\Host' => 'lib/queue/host.php',
    '\App\Integration\Queue\AbstractBase' => 'lib/queue/abstractBase.php',
	'\App\Integration\Queue\Deal\IncomingOrder' => 'lib/queue/deal/incomingOrder.php',
	
    '\App\Integration\Handlers\Iblock\Element' => 'lib/handlers/iblock/element.php',
    '\App\Integration\Handlers\Iblock\Section' => 'lib/handlers/iblock/section.php',
    '\App\Integration\Handlers\Iblock\Property' => 'lib/handlers/iblock/property.php',
);

$arClassLib = array();

\Bitrix\Main\Loader::registerAutoLoadClasses(
	'app.integration',
	array_merge($arClassBase, $arClassLib)

);
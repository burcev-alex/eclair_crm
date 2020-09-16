<?php
namespace App\Base;

/**
 * Базовый каталог модуля
 */
const BASE_DIR = __DIR__;
/**
 * Имя модуля
 */
const MODULE_ID = 'app.base';

IncludeModuleLangFile(__FILE__);

$arClassBase = [
	'\App\Base\Tools' => 'lib/tools.php',
    '\App\Base\Cache' => 'lib/cache.php',
    '\App\Base\Rights' => 'lib/rights.php',
    '\App\Base\CommandManager' => 'lib/command.php',
	'\App\Base\Event' => 'lib/event.php',
	'\App\Base\Event\Handlers\IBlock' => 'lib/handlers/iblock.php',
    '\App\Base\Event\Handlers\Main' => 'lib/handlers/main.php',
	'\App\Base\Entity\HighloadBlockTable' => 'lib/entity/hl.php',
    '\App\Base\Entity\UserFieldEnumTable' => 'lib/entity/userfieldenum.php',
    '\App\Base\Entity\CommandTable' => 'lib/entity/command.php',
    '\App\Base\Entity\CommandLogTable' => 'lib/entity/commandlog.php',
    '\App\Base\Rest\ServerApi' => 'lib/rest/server_api.php',
    '\App\Base\Rest\Api' => 'lib/rest/abstract_api.php',
	'\App\Base\Controller\Prototype' => 'lib/controller/prototype.php',
    '\App\Base\Controller\Example' => 'lib/controller/example.php',
    '\App\Base\Form\Prototype' => 'lib/form/prototype.php',
	'\App\Base\View\Prototype' => 'lib/view/prototype.php',
	'\App\Base\View\Php' => 'lib/view/php.php',
	'\App\Base\View\Json' => 'lib/view/json.php',
	'\App\Base\View\Xml' => 'lib/view/xml.php',
	'\App\Base\View\Html' => 'lib/view/html.php',
	'\App\Base\Router' => 'lib/router.php',
	'\App\Base\Exception' => 'lib/exception.php',
	'\App\Base\Vue' => 'lib/vue.php',
	'\App\Base\Rest\RestClient' => 'lib/rest/client_api.php',
	'\App\Base\UserField\PropertyIblock' => 'lib/userfield/propertyiblock.php',
];

$arClassLib = [];

\Bitrix\Main\Loader::registerAutoLoadClasses(
    MODULE_ID,
	array_merge($arClassBase, $arClassLib)

);

$pathJs = Tools::getPathForStatic().'/js/'.MODULE_ID;

\CJSCore::RegisterExt(
    'app',
    [
        'js' => $pathJs.'/core.js',
        'rel' => ['jquery3', 'utils']
    ]
);

\CJSCore::RegisterExt(
    'app_route',
    [
        'js' => Tools::getPathForStatic().'/js/route.js',
        'rel' => ['app']
    ]
);

<?php
namespace Studiobit\Base;

/**
 * Базовый каталог модуля
 */
const BASE_DIR = __DIR__;
/**
 * Имя модуля
 */
const MODULE_ID = 'studiobit.base';

IncludeModuleLangFile(__FILE__);

$arClassBase = array(
	'\Studiobit\Base\Tools' => 'lib/tools.php',
    '\Studiobit\Base\Cache' => 'lib/cache.php',
    '\Studiobit\Base\Rights' => 'lib/rights.php',
    '\Studiobit\Base\PullSchema' => 'lib/pull.php',
    '\Studiobit\Base\CommandManager' => 'lib/command.php',
    '\Studiobit\Base\History\Prototype' => 'lib/history/prototype.php',
    '\Studiobit\Base\History\UserfieldsHistory' => 'lib/history/userfieldshistory.php',
    '\Studiobit\Base\History\CrmHistory' => 'lib/history/crmhistory.php',
    '\Studiobit\Base\History\IblockSectionHistory' => 'lib/history/iblocksectionhistory.php',
    '\Studiobit\Base\History\IblockElementHistory' => 'lib/history/iblockelementhistory.php',
    '\Studiobit\Base\History\HLBlockHistory' => 'lib/history/hlblockhistory.php',
    '\Studiobit\Base\History\Writer' => 'lib/history/writer.php',
	'\Studiobit\Base\Event' => 'lib/event.php',
	'\Studiobit\Base\Event\Handlers\IBlock' => 'lib/handlers/iblock.php',
    '\Studiobit\Base\Event\Handlers\Main' => 'lib/handlers/main.php',
	'\Studiobit\Base\CAbstractEntity' => 'lib/abstractentity.php',
	'\Studiobit\Base\Entity\HighloadBlockTable' => 'lib/entity/hl.php',
    '\Studiobit\Base\Entity\UserFieldEnumTable' => 'lib/entity/userfieldenum.php',
    '\Studiobit\Base\Entity\FormFieldPermsTable' => 'lib/entity/formfieldperms.php',
    '\Studiobit\Base\Entity\BPVariableTable' => 'lib/entity/bpvariable.php',
    '\Studiobit\Base\Entity\BPOptionsTable' => 'lib/entity/bpoptions.php',
    '\Studiobit\Base\Entity\CommandTable' => 'lib/entity/command.php',
    '\Studiobit\Base\Excel\Import\Prototype' => 'lib/excel/import/prototype.php',
    '\Studiobit\Base\Excel\Export\Prototype' => 'lib/excel/export/prototype.php',
    '\Studiobit\Base\Converter' => 'lib/converter.php',
    '\Studiobit\Base\Rest\Api' => 'lib/rest/abstract_api.php',
    '\Studiobit\Base\Rest\Internal\BaseInternal' => 'lib/rest/internal/base.php',
	'\Studiobit\Base\FileDiskProperty' => 'lib/property_filedisk.php',
	'\Studiobit\Base\Controller\Prototype' => 'lib/controller/prototype.php',
    '\Studiobit\Base\Controller\Example' => 'lib/controller/example.php',
    '\Studiobit\Base\Controller\Excel' => 'lib/controller/excel.php',
    '\Studiobit\Base\Controller\Form' => 'lib/controller/form.php',
    '\Studiobit\Base\Controller\Bizproc' => 'lib/controller/bizproc.php',
    '\Studiobit\Base\Form\Prototype' => 'lib/form/prototype.php',
	'\Studiobit\Base\View\Prototype' => 'lib/view/prototype.php',
	'\Studiobit\Base\View\Php' => 'lib/view/php.php',
	'\Studiobit\Base\View\Json' => 'lib/view/json.php',
	'\Studiobit\Base\View\Xml' => 'lib/view/xml.php',
	'\Studiobit\Base\View\Html' => 'lib/view/html.php',
	'\Studiobit\Base\Router' => 'lib/router.php',
	'\Studiobit\Base\Exception' => 'lib/exception.php',
	'\Studiobit\Base\Rest\RestClient' => 'lib/rest/client_api.php'
);

$arClassLib = array(
);

\Bitrix\Main\Loader::registerAutoLoadClasses(
	'studiobit.base',
	array_merge($arClassBase, $arClassLib)

);

\CJSCore::RegisterExt(
    "studiobit",
    array(
        "js" => "/local/static/js/studiobit.base/core.js",
        "rel" => Array("jquery2", "utils")
    )
);

\CJSCore::RegisterExt(
    "studiobit_route",
    array(
        "js" => "/local/static/js/route.js",
        "rel" => Array("studiobit", "sidepanel")
    )
);

\CJSCore::RegisterExt(
    "studiobit_import",
    array(
        "js" => "/local/static/js/studiobit.base/import.js",
        "css" => "/local/static/css/studiobit.base/import.css",
        "rel" => Array("studiobit")
    )
);

\CJSCore::RegisterExt(
    "studiobit_export",
    array(
        "js" => "/local/static/js/studiobit.base/export.js",
        "rel" => Array("studiobit")
    )
);

\CJSCore::RegisterExt(
    "studiobit_loader",
    array(
        "js" => "/local/static/js/studiobit.base/loader.js",
        "css" => "/local/static/css/studiobit.base/loader.css",
        "rel" => Array("studiobit")
    )
);

\CJSCore::RegisterExt(
    "studiobit_dialog",
    array(
        "js" => "/local/static/js/studiobit.base/dialog.js",
        "rel" => Array("studiobit")
    )
);

\CJSCore::RegisterExt(
    "studiobit_form_perms",
    array(
        "js" => "/local/static/js/studiobit.base/formperms.js",
        "rel" => Array("studiobit_dialog")
    )
);
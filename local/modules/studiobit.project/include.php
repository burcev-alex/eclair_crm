<?php
namespace Studiobit\Project;

defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;

/**
 * Базовый каталог модуля
 */
const BASE_DIR = __DIR__;

/**
 * Имя модуля
**/

const MODULE_ID = 'studiobit.project';

/*константы модуля*/
const CALL_CENTER_DEPARTMENT = 137;
const CATEGORY_ID_TRADE_IN = 1;
const MANAGERS = 42;
const CALL_CENTER = 104;
const IBLOCK_ID_CHANNELS = 29;

IncludeModuleLangFile(__FILE__);

$arClassBase = array(
	'\Studiobit\Project\Event' => 'lib/event.php',
    '\Studiobit\Project\Custom\CrmSelect' => 'lib/custom/crmselect.php',
    '\Studiobit\Project\Helper\Text' => 'lib/helper/text.php',
    //обработчики ajax-запросов
    '\Studiobit\Project\Controller\Prototype' => 'lib/controller/prototype.php',
	'\Studiobit\Project\Controller\Contact' => 'lib/controller/contact.php',
    '\Studiobit\Project\Controller\Company' => 'lib/controller/company.php',
    '\Studiobit\Project\Controller\Activity' => 'lib/controller/activity.php',
    '\Studiobit\Project\Controller\Favorite' => 'lib/controller/favorite.php',
    '\Studiobit\Project\Controller\Agreement' => 'lib/controller/agreement.php',
    //обработчики событий
    '\Studiobit\Project\Handlers\Base' => 'lib/handlers/base.php',
    '\Studiobit\Project\Handlers\Main' => 'lib/handlers/main.php',
	'\Studiobit\Project\Handlers\Pull' => 'lib/handlers/pull.php',
    '\Studiobit\Project\Handlers\Crm' => 'lib/handlers/crm.php',
    '\Studiobit\Project\Handlers\Agreement' => 'lib/handlers/agreement.php',
    '\Studiobit\Project\Handlers\Matrix' => 'lib/handlers/matrix.php',
    //сущности
    '\Studiobit\Project\Entity\Crm\ContactTable' => 'lib/entity/crm/contact.php',
    '\Studiobit\Project\Entity\Crm\CompanyTable' => 'lib/entity/crm/company.php',
    '\Studiobit\Project\Entity\Crm\DealTable' => 'lib/entity/crm/deal.php',
    '\Studiobit\Project\Entity\Crm\LeadTable' => 'lib/entity/crm/lead.php',
    '\Studiobit\Project\Entity\FavoriteTable' => 'lib/entity/favorite.php',
    '\Studiobit\Project\Entity\AgreementTable' => 'lib/entity/agreement.php',
    '\Studiobit\Project\Entity\BankTable' => 'lib/entity/bank.php',
    '\Studiobit\Project\Entity\PaymentTypeTable' => 'lib/entity/paymenttype.php',
    '\Studiobit\Project\Entity\PaymentTable' => 'lib/entity/payment.php',
    '\Studiobit\Project\Entity\PaymentScheduleTable' => 'lib/entity/paymentschedule.php',
    '\Studiobit\Project\Entity\DealStage1CTable' => 'lib/entity/dealstage1c.php',
    '\Studiobit\Project\Entity\StatusContactTable' => 'lib/entity/statuscontact.php',
    '\Studiobit\Project\Entity\Keys' => 'lib/entity/keys.php',
    //команды
    '\Studiobit\Project\Command\Activity' => 'lib/command/activity.php',
    '\Studiobit\Project\Command\User' => 'lib/command/user.php',
    '\Studiobit\Project\Command\Crm' => 'lib/command/crm.php',
    '\Studiobit\Project\Command\Object' => 'lib/command/object.php',
    //формы
    '\Studiobit\Project\Form\Agreement' => 'lib/form/agreement.php', //
    '\Studiobit\Project\Form\PaymentSchedule' => 'lib/form/paymentschedule.php', //
    '\Studiobit\Project\Form\Payment' => 'lib/form/payment.php', //
	// интеграции
    '\Studiobit\Project\Integration\ErpClient' => 'lib/integration/erp.php', //
    '\Studiobit\Project\Integration\Prices' => 'lib/integration/prices.php', //
    '\Studiobit\Project\Integration\SiteClient' => 'lib/integration/site.php', //
    //отчеты
    '\Studiobit\Project\Report\Manager' => 'lib/report/manager.php',
    '\Studiobit\Project\Report\Contact' => 'lib/report/contact.php',
	// интеграция

	'\Studiobit\Project\Integration\Rest\ServerApi' => 'lib/integration/rest/server_api.php',
);

$arClassLib = array(
);

Loader::registerAutoLoadClasses(
	MODULE_ID,
	array_merge($arClassBase, $arClassLib)

);

\CJSCore::RegisterExt(
    "studiobit_project",
    array(
        "js" => "/local/static/js/".MODULE_ID."/core.js",
        "rel" => Array("jquery2", "utils")
    )
);

\CJSCore::RegisterExt(
    "studiobit_project_search_entity",
    array(
        "js" => "/local/static/js/".MODULE_ID."/search_entity.js",
        "rel" => Array("studiobit_project")
    )
);

//\CJSCore::RegisterExt(
//    "studiobit_project_timeline",
//    array(
//        "js" => "/local/static/js/".MODULE_ID."/timeline.js",
//        "css" => "/local/static/css/".MODULE_ID."/timeline.css",
//        "rel" => Array("studiobit_project", "masked_input")
//    )
//);

\CJSCore::RegisterExt(
    "studiobit_project_favorite",
    array(
        "js" => "/local/static/js/".MODULE_ID."/favorite.js",
        "css" => "/local/static/css/".MODULE_ID."/favorite.css",
        "rel" => Array("studiobit_project")
    )
);

\CJSCore::RegisterExt(
    "studiobit_project_stock",
    array(
        "js" => "/local/static/js/".MODULE_ID."/stock.js",
        "css" => "/local/static/css/".MODULE_ID."/stock.css",
        "rel" => Array("studiobit_project")
    )
);

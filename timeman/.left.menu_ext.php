<?

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intranet/public/timeman/.left.menu_ext.php");

$aMenuLinks = [];

$aMenuLinks[] = [
	Loc::getMessage("TOP_MENU_ABSENCE"),
	SITE_DIR . "timeman/index.php",
	[],
	["menu_item_id" => "menu_absence"],
	"CBXFeatures::IsFeatureEnabled('StaffAbsence')",
];

if (ModuleManager::isModuleInstalled("timeman"))
{
	$aMenuLinks[] = [
		Loc::getMessage("TOP_MENU_TIMEMAN"),
		SITE_DIR . "timeman/timeman.php",
		[],
		["menu_item_id" => "menu_timeman"],
		"CBXFeatures::IsFeatureEnabled('timeman')",
	];

	if (ModuleManager::isModuleInstalled("faceid"))
	{
		$aMenuLinks[] = [
			'Bitrix24.Time',
			SITE_DIR . "timeman/b24time.php",
			[],
			["menu_item_id" => "menu_bitrix24time"],
			"",
		];
	}

	$aMenuLinks[] = [
		Loc::getMessage("TOP_MENU_WORK_REPORT"),
		SITE_DIR . "timeman/work_report.php",
		[],
		["menu_item_id" => "menu_work_report"],
		"CBXFeatures::IsFeatureEnabled('timeman')",
	];

	if (Loader::includeModule('timeman'))
	{
		global $USER;
		$permissionsManager = \Bitrix\Timeman\Service\DependencyManager::getInstance()->getUserPermissionsManager($USER);
		if ($permissionsManager->canReadSchedules())
		{
			$aMenuLinks[] = [
				Loc::getMessage("TOP_MENU_WORK_SCHEDULES"),
				SITE_DIR . "timeman/schedules/",
				[],
				["menu_item_id" => "menu_schedules_list"],
				"CBXFeatures::IsFeatureEnabled('timeman')",
			];
		}

		if ($permissionsManager->canUpdateSettings())
		{
			$aMenuLinks[] = [
				Loc::getMessage("TOP_MENU_SETTINGS_PERMISSIONS"),
				SITE_DIR . "timeman/settings/permissions/",
				[],
				["menu_item_id" => "menu_worktime_settings_permissions"],
				"CBXFeatures::IsFeatureEnabled('timeman')",
			];
		}
	}
}

if (ModuleManager::isModuleInstalled("meeting"))
{
	$aMenuLinks[] = [
		Loc::getMessage("TOP_MENU_MEETING"),
		SITE_DIR . "timeman/meeting/",
		[],
		["menu_item_id" => "menu_meeting"],
		"CBXFeatures::IsFeatureEnabled('Meeting')",
	];
}
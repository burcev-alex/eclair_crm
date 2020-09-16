<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/services/contact_center/.left.menu_ext.php");

$aMenuLinks[] = array(
	GetMessage("MENU_CONTACT_CENTER"),
	"/services/contact_center/",
	array(),
	array("menu_item_id" => "menu_contact_center"),
	""
);

if (CModule::IncludeModule("imopenlines"))
{
	if (\Bitrix\ImOpenlines\Security\Helper::isStatisticsMenuEnabled())
	{
		$aMenuLinks[] = array(
			GetMessage("MENU_CONTACT_CENTER_IMOL_DETAILED_STATISTICS"),
			"/services/contact_center/openlines/statistics.php",
			array(),
			array("menu_item_id" => "menu_openlines_detail_statistics"),
			""
		);
		$aMenuLinks[] = array(
			GetMessage("MENU_CONTACT_CENTER_IMOL_STATISTICS"),
			"/services/contact_center/openlines/",
			array(),
			array("menu_item_id" => "menu_openlines_statistics"),
			""
		);
	}
}
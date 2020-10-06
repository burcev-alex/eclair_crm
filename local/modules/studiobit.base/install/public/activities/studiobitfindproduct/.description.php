<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	"NAME" => GetMessage("BPDDA_DESCR_NAME"),
	"DESCRIPTION" => GetMessage("BPDDA_DESCR_DESCR"),
	"TYPE" => "activity",
	"CLASS" => "StudiobitFindProduct",
	"JSCLASS" => "BizProcActivity",
	"CATEGORY" => array(
		"ID" => "other",
	),
	"RETURN" => array(
		"FindProductId" => array(
			"NAME" => GetMessage("BPSWFA_DESCR_FIND_PRODUCT_ID"),
			"TYPE" => "integer",
		),
		"FindProductList" => array(
			"NAME" => GetMessage("BPSWFA_DESCR_FIND_PRODUCT_LIST"),
			"TYPE" => "integer",
		),
	),
);
?>

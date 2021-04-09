<?php

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

set_time_limit(0);

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(dirname(dirname(__FILE__))));

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

global $USER;

$USER->Authorize(1);

use Bitrix\Main\Loader;

Loader::includeModule('crm');
Loader::includeModule('iblock');
Loader::includeModule('app.base');
Loader::includeModule('app.indegration');

$interval = 600; // мин

// интервал запуска анализа заказов
$timestamp = \COption::GetOptionString('app.indegration', 'cron_last_timestamp', (mktime() - (60 * $interval)));

$el = new \CIBlockElement();

// CATALOG
$arFilter = [
	'IBLOCK_ID' => 17,
	">=TIMESTAMP_X" => \ConvertTimeStamp($timestamp, "FULL"), 
	"<=TIMESTAMP_X" => \ConvertTimeStamp(time(), "FULL"), 
];
$arSelect = [
	'ID',
	'NAME',
	'ACTIVE',
	'IBLOCK_ID',
	'IBLOCK_SECTION_ID'
];
$element = 0;
$rsCatalog = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
while($arElement = $rsCatalog->Fetch()){
	$arFields = [
		'ID' => $arElement['ID'],
		'NAME' => $arElement['NAME'],
		'ACTIVE' => $arElement['ACTIVE'],
		'IBLOCK_ID' => $arElement['IBLOCK_ID'],
		'IBLOCK_SECTION_ID' => $arElement['IBLOCK_SECTION_ID']
	];

	$el->Update($arElement['ID'], $arFields);
	$element++;
}


// OFFERS
$arFilter = [
	'IBLOCK_ID' => 18,
	">=TIMESTAMP_X" => \ConvertTimeStamp($timestamp, "FULL"), 
	"<=TIMESTAMP_X" => \ConvertTimeStamp(time(), "FULL"), 
];
$arSelect = [
	'ID',
	'NAME',
	'ACTIVE',
	'IBLOCK_ID',
	'IBLOCK_SECTION_ID'
];
$offer = 0;
$rsCatalog = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
while($arElement = $rsCatalog->Fetch()){
	$arFields = [
		'ID' => $arElement['ID'],
		'NAME' => $arElement['NAME'],
		'ACTIVE' => $arElement['ACTIVE'],
		'IBLOCK_ID' => $arElement['IBLOCK_ID'],
		'IBLOCK_SECTION_ID' => $arElement['IBLOCK_SECTION_ID']
	];

	$el->Update($arElement['ID'], $arFields);
	$offer++;
}

// перезаписать время запуска
\COption::SetOptionString('app.integration', 'cron_last_timestamp', mktime());

echo "\r\nElement:".$element."\r\n";
echo "\r\nOffer:".$offer."\r\n";
echo "\r\nOK\r\n";
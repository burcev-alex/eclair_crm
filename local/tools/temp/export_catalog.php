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

if(!isset($INTERVAL))
	$INTERVAL = 3000;
else
	$INTERVAL = intval($INTERVAL);
if($INTERVAL <= 0)
	@set_time_limit(0);

$start_time = time();

$_SESSION['BX_CML2_EXPORT'] = [
    'PROPERTY_MAP' => false,
    'SECTION_MAP' => false,
    'PRICES_MAP' => false,
    'work_dir' => false,
    'file_dir' => false,
];

$NS = [
	'URL_DATA_FILE' => '/upload/export_tmp.xml',
	'IBLOCK_ID' => 17,
	'DOWNLOAD_CLOUD_FILES' => 'N',
	"SECTIONS_FILTER" => 'all',
	"ELEMENTS_FILTER" => 'all',
	"next_step" => array(),
];

//We have to strongly check all about file names at server side
$ABS_FILE_NAME = false;
$WORK_DIR_NAME = false;
if (isset($NS['URL_DATA_FILE']) && (strlen($NS['URL_DATA_FILE']) > 0)) {
    $filename = trim(str_replace('\\', '/', trim($NS['URL_DATA_FILE'])), '/');
    if (
        preg_match('/[^a-zA-Z0-9\s!#\$%&\(\)\[\]\{\}+\.;=@\^_\~\/\\\\\-]/i', $filename)
        || HasScriptExtension($filename)
    ) {
        $arErrors[] = 'IBLOCK_CML2_FILE_NAME_ERROR';
    } else {
        $FILE_NAME = rel2abs($_SERVER['DOCUMENT_ROOT'], '/'.$filename);
        if ((strlen($FILE_NAME) > 1) && ($FILE_NAME === '/'.$filename)) {
            $ABS_FILE_NAME = $_SERVER['DOCUMENT_ROOT'].$FILE_NAME;
            if (strtolower(substr($ABS_FILE_NAME, -4)) != '.xml') {
                $ABS_FILE_NAME .= '.xml';
            }
            $WORK_DIR_NAME = substr($ABS_FILE_NAME, 0, strrpos($ABS_FILE_NAME, '/') + 1);
        }
    }
}

if ($fp = fopen($ABS_FILE_NAME, 'wb')) {
    @chmod($ABS_FILE_NAME, BX_FILE_PERMISSIONS);
    if (strtolower(substr($ABS_FILE_NAME, -4)) == '.xml') {
        $DIR_NAME = substr($ABS_FILE_NAME, 0, -4).'_files';
        if (
                        is_dir($DIR_NAME)
                        || @mkdir($DIR_NAME, BX_DIR_PERMISSIONS)
                    ) {
            $_SESSION['BX_CML2_EXPORT']['work_dir'] = $WORK_DIR_NAME;
            $_SESSION['BX_CML2_EXPORT']['file_dir'] = substr($DIR_NAME.'/', strlen($WORK_DIR_NAME));
        }
    }
} else {
	$arErrors[] = 'IBLOCK_CML2_FILE_ERROR';
}

if ($fp = fopen($ABS_FILE_NAME, 'ab')) {
    $obExport = new \CIBlockCMLExport();
    if ($obExport->Init($fp, $NS['IBLOCK_ID'], $NS['next_step'], true, $_SESSION['BX_CML2_EXPORT']['work_dir'], $_SESSION['BX_CML2_EXPORT']['file_dir'])) {
        if ($NS['DOWNLOAD_CLOUD_FILES'] === 'N') {
            $obExport->DoNotDownloadCloudFiles();
		}
		
		$obExport->StartExport();
		$obExport->StartExportMetadata();

		// свойства
		$obExport->ExportProperties($_SESSION['BX_CML2_EXPORT']['PROPERTY_MAP']);
		
		// выгрузка разделов
		$obExport->ExportSections(
			$_SESSION['BX_CML2_EXPORT']['SECTION_MAP'],
			$start_time,
			$INTERVAL,
			$NS['SECTIONS_FILTER'],
			$_SESSION['BX_CML2_EXPORT']['PROPERTY_MAP']
		);

		// выгрузка элементов
		$obExport->ExportElements(
			$_SESSION['BX_CML2_EXPORT']['PROPERTY_MAP'],
			$_SESSION['BX_CML2_EXPORT']['SECTION_MAP'],
			$start_time,
			$INTERVAL,
			0,
			$NS['ELEMENTS_FILTER']
		);

		$obExport->EndExportCatalog();
        $obExport->ExportProductSets();
        $obExport->EndExport();

    } else {
        $arErrors[] = 'IBLOCK_CML2_IBLOCK_ERROR';
	}
	
	#exec("tar -czvf /home/bitrix/ext_www/ecler.gk-strizhi.ru/upload/export_tmp_files.tar.gz ".$DIR_NAME);
	#exec("rm -rf ".$DIR_NAME);
}

echo 'OK';
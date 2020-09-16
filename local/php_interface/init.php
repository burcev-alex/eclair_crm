<?php

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");

define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log.txt");

require_once('app_init.php');

if (!function_exists('p')) {
	function p($arr, $admin = false)
	{
		global $USER;
		$message = '<pre style="font-size: 10pt; background-color: #fff; color: #000; margin: 10px; padding: 10px; border: 1px solid red; text-align: left; max-width: 800px; max-height: 600px; overflow: scroll">' . print_r($arr, true) . '</pre>';
		if ($admin) {
			if ($USER->IsAdmin()) {
				echo $message;
			}
		} else {
			echo $message;
		}
	}
}

if (!function_exists('p2f')) {
	function p2f($obj, $admOnly = true)
	{
		global $USER;

		if (!is_object($USER))
			$USER = new \CUser();

		if ($USER->IsAdmin() || $admOnly === false) {
			if ($admOnly) $userID = 1; else $userID = $USER->GetID();
			if (IntVal($userID) == 0) {
				$userID = 'none';
			}
			$dump = "<pre style='font-size: 11px; font-family: tahoma;'>" . print_r($obj, true) . "</pre>";
			$files = $_SERVER["DOCUMENT_ROOT"] . "/" . $userID . "-dump.html";
			$fp = fopen($files, "a+");
			fwrite($fp, $dump);
			fclose($fp);
		}
	}
}

if (!function_exists('p2log')) {
	function p2log($obj, $key = '')
	{
		if (empty($key)) {
			$key = 'main';
		}

		$dump = print_r($obj, true) . "\r\n";
		$files = $_SERVER["DOCUMENT_ROOT"] . "/upload/log/" . $key . ".log";
		$fp = fopen($files, "a+");
		fwrite($fp, $dump);
		fclose($fp);
	}
}

if (!function_exists('pr')) {
	/**
	 * Выводит информацию об объекте в стилизованном, удобочитаемом виде.
	 * C выводом отображения строки откуда вызывана
	 * @param $o
	 * @param bool $option true: показывать стек вызовов
	 * @param bool $stack true: var_dump, false: print_r
	 */
	function pr($o, $option = false, $stack = false)
	{
		$bt = debug_backtrace();
		$bt = $bt[0];
		$dRoot = $_SERVER["DOCUMENT_ROOT"];
		$dRoot = str_replace("/", "\\", $dRoot);
		$bt["file"] = str_replace($dRoot, "", $bt["file"]);
		$dRoot = str_replace("\\", "/", $dRoot);
		$bt["file"] = str_replace($dRoot, "", $bt["file"]);

		if (php_sapi_name() != 'cli') {
			?>
			<div style='font-size:9pt; color:#000; background:#fff; border:1px dashed #000;'>
				<div style='padding:3px 5px; background:#99CCFF; font-weight:bold;'>File: <?= $bt["file"] ?>
					[<?= $bt["line"] ?>]
				</div>
				<? if ($option) { ?>
					<pre style='padding:10px;'><? var_dump(!$stack ? $o : debug_backtrace()); ?></pre>
				<? } else { ?>
					<pre style='padding:10px;'><? print_r(!$stack ? $o : debug_backtrace()); ?></pre>
				<? } ?>

			</div>
		<? } else {
			fprintf("File: %s \n ______________________ \n Count: %s", $bt['file']);
			print_r($o);
		} ?>
		<?php
	}
}



Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'onSaleDeliveryRestrictionsClassNamesBuildList',
    'myDeliveryFunction'
);

function myDeliveryFunction()
{
    return new \Bitrix\Main\EventResult(
        \Bitrix\Main\EventResult::SUCCESS,
        array(
            '\MyDeliveryRestriction' => '/local/php_interface/include/mydelrestriction.php',
        )
    );
}

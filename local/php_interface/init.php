<?php

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/wsrubi.smtp/classes/general/wsrubismtp.php");

define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/log.txt");
require_once ($_SERVER["DOCUMENT_ROOT"]."/local/lib/CSms4bBase.php");
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


function sendByWhatsApp($phone, $msg)
{
    $phone = trim($phone, '+');
    $phone[0] = 7;

    $msg = str_replace('"', '\'', $msg);
    // Отправка текста
    $url = 'https://new39066241.wazzup24.com/api/v1.1/send_message';
    // echo $msg;
    $data = "
    {
    \"transport\": \"whatsapp\",
    \"from\": \"79913788268\",
    \"to\": \"$phone\",
    \"text\": \"$msg\"
    }";


    $options = array(
        'http' => array(
            'method' => 'POST',
            'content' => $data,
            'header' => "Content-type: application/json\r\n" .
                "Authorization: 400811b478d64879af044f086e7ba021\r\n"
        )
    );
    $context  = stream_context_create( $options );
    $result = file_get_contents( $url, false, $context );
    $response = json_decode( $result );
    return $response;
}

function sendSMS($phone,$msg){
    $LOGIN = 'Abramova';
    $PASSWORD = '1cbit-abramova';

    $SMS4B = new \Csms4bBase($LOGIN,$PASSWORD);

    $SMS4B->SendSMS($msg,$phone,'ECLAIR CAFE');
}


function generate_string($strength = 4) {
    $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }

    return $random_string;
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

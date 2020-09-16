<?
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

set_time_limit(0);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Base;
use App\Integration;

global $USER;

\CModule::IncludeModule("app.base");
\CModule::IncludeModule("app.integration");

// Запросы с того же сервера не имеют заголовка HTTP_ORIGIN
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
	$_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
	$API = new Integration\Rest\Server\Main($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
	echo $API->processAPI();
} catch (\Exception $e) {

	$status = 200;
	if (substr_count($e->getMessage(), "No API Key provided") > 0) {
		$status = 401;
		$text = "Unauthorized";
	}

	if ($status != 200) {
		header("HTTP/1.1 " . $status . " " . $text);
	}
	echo json_encode(array('error' => $e->getMessage()));
}
<?

namespace Studiobit\Base\Rest;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

abstract class Api
{
	/**
	 * Property: method
	 * GET, POST, PUT, DELETE
	 */
	protected $method = '';
	/**
	 * Property: endpoint
	 * The Model requested in the URI. eg: /files
	 */
	protected $endpoint = '';
	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /files/process
	 */
	protected $verb = '';
	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = array();
	/**
	 * Property: file
	 * Stores the input of the PUT request
	 */
	protected $file = null;

	/**
	 * Body Request
	 * @var array
	 */
	protected $content = array();

	protected $request = array();

	/**
	 * Allow for CORS, assemble and pre-process the data
	 *
	 * API constructor.
	 *
	 * @param $request
	 *
	 * @throws \Exception
	 */
	public function __construct($request)
	{
		header("Access-Control-Allow-Orgin: *");
		header("Access-Control-Allow-Methods: *");
        header("Content-type: application/json; charset=utf-8");

		$this->args = explode('/', rtrim($request, '/'));
		$this->endpoint = array_shift($this->args);
		if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
			$this->verb = array_shift($this->args);
		}
		$this->method = $_SERVER['REQUEST_METHOD'];
		if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this->method = 'DELETE';
			} elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this->method = 'PUT';
			} else {
				throw new \Exception("Unexpected Header");
			}
		}
		switch ($this->method) {
			case 'DELETE':
			case 'POST':
				// тело
				$phpInput = file_get_contents('php://input');

				// голова запроса
				$request_headers = $this->header();

				$this->log("Php header", $request_headers);
				$this->log("Php input", $phpInput);
				// избавляемся от BOM
				$phpInput = $this->removeBOM($phpInput);

				// декодируем тело запроса
				$this->content = $this->decodeBody($phpInput, $request_headers);

				// логирование
				$this->log("Body", $this->content);

				$this->request = $this->cleanInputs($_POST);

				if (count($this->request) == 0) {
					$this->request = $this->cleanInputs($_GET);
				}
				break;
			case 'GET':
				$this->request = $this->cleanInputs($_GET);
				break;
			default:
				$this->response('Invalid Method', 405);
				break;
		}
	}

	public function processAPI()
	{
		// проверка контента
		if ((count($this->content) == 0) && (is_array($this->content)) && ($this->method == "POST")) {
			$resultMessage = array("error" => "Bad Request");

			return $this->response($resultMessage, 400);
		} elseif ((empty($this->content)) && ($this->method == "POST")) {
			return $this->response(array("error" => $this->content), 400);
		} elseif (method_exists($this, $this->endpoint)) {
			return $this->response($this->{$this->endpoint}($this->args));
		}

		$resultMessage = array("error" => "Method: $this->endpoint not found");

		return $this->response($resultMessage, 404);
	}

	/**
	 * Формирование ответа
	 *
	 * @param $data
	 * @param int $status
	 *
	 * @return string
	 */
	private function response($data, $status = 200)
	{
		header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));

		return json_encode($data);
	}

	/**
	 * Очистка данных от "мусора"
	 *
	 * @param $data
	 *
	 * @return array|string
	 */
	private function cleanInputs($data)
	{
		$clean_input = array();
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$clean_input[$k] = $this->cleanInputs($v);
			}
		} else {
			$clean_input = trim(strip_tags($data));
		}

		return $clean_input;
	}

	/**
	 * Статусы ответов
	 *
	 * @param $code
	 *
	 * @return mixed
	 */
	private function requestStatus($code)
	{
		$status = array(
			200 => 'OK',
			400 => 'Bad Request',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			414 => 'Request-URL Too Long',
			500 => 'Internal Server Error',
		);

		return ($status[$code]) ? $status[$code] : $status[500];
	}

	/**
	 * Голова запроса
	 * @return array|false
	 */
	private function header()
	{
		$result = array();

		if (!function_exists('getallheaders')) {
			$headers = array();
			foreach ($_SERVER as $name => $value) {
				if (strtolower(substr($name, 0, 5)) == 'http_') {
					$ucwords = ucwords(strtolower(str_replace('_', ' ', substr($name, 5))));
					$headers[str_replace(' ', '-', $ucwords)] = $value;
				}
			}
			$result = $headers;
		} else {
			$result = getallheaders();
		}

		return $result;
	}

	/**
	 * Декодируем тело запроса
	 *
	 * @param $phpInput
	 * @param $request_headers
	 *
	 * @return array|mixed|string
	 */
	private function decodeBody($phpInput, $request_headers)
	{
		$result = array();

		$body = htmlspecialchars_decode(trim($phpInput));

		if($this->isJson($body)){
			$result = json_decode($body);
		}
		else{
			$result = new \SimpleXMLElement($body);
		}

		return $result;
	}

	private function isJson($string) {
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Удалить BOM из строки
	 *
	 * @param string $str - исходная строка
	 *
	 * @return string $str - строка без BOM
	 */
	private function removeBOM($str = "")
	{
		$bom = pack('H*','EFBBBF');
		$str = preg_replace("/^$bom/", '', $str);

		return $str;
	}

	/**
	 * Логирование запросов
	 *
	 * @param $nameTitle
	 * @param $obj
	 */
	protected function log($nameTitle, $obj)
	{
		$style = "font-size: 11px; font-family: tahoma;";
		$dump = "<pre style='" . $style . "'>" . $nameTitle . "--" . date("Y.m.d H:i:s") . "</pre>";
		$dump .= "<pre style='" . $style . "'>" . print_r($obj, true) . "</pre>";
		$dump .= "<pre style='" . $style . "'>";
		for ($i = 1; $i <= 120;
		     $i++) {
			$dump .= "-";
		}
		$dump .= "</pre>";
		$fileName = $this->endpoint . "-" . $this->method . "-" . date("Ymd") . "-dump.html";
		$files = $_SERVER["DOCUMENT_ROOT"] . "/upload/rest_log/" . $fileName;
		$fp = fopen($files, "a+");
		fwrite($fp, $dump);
		fclose($fp);
	}
}

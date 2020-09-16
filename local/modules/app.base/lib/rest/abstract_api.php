<?

namespace App\Base\Rest;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;

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

	protected $headers = array();

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

		$this->headers = $this->header();

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

				$parseBody = $this->parse_raw_http_request($phpInput);

				if (count($parseBody['data']) > 0) {
					$this->file = $parseBody['file'];
					$this->request = $parseBody['data'];
				}

				if (count($parseBody['data']) == 0) {
					// декодируем тело запроса
					$this->content = $this->decodeBody($phpInput, $request_headers);
				}
				else{
					$this->content = "multi-form";
				}

				// логирование
				$this->log("Body", $this->content);

				if (count($this->request) == 0) {
					$this->request = $this->cleanInputs($_POST);
				}

				if (count($this->request) == 0) {
					$this->request = $this->cleanInputs($_GET);
				}

				if(strlen($this->cleanInputs($_GET)['apiKey']) > 0){
					$this->request['apiKey'] = $this->cleanInputs($_GET)['apiKey'];
				}

				break;
			case 'GET':
				$this->request = $this->cleanInputs($_GET);
				break;
			default:
				$this->response('Invalid Method', 405);
				break;
		}

		$this->log("THIS", $this);
	}

	public function processAPI()
	{
		// проверка контента
		if ((count($this->content) == 0) && (is_array($this->content)) && ($this->method == "POST")) {
			$resultMessage = array("error" => "Bad Request");

			return $this->response($resultMessage, 400);
		} elseif ((empty($this->content)) && ($this->method == "POST")) {
			return $this->response(array("error" => "Content is do not found"), 400);
		} elseif (method_exists($this, $this->endpoint)) {
			$data = $this->{$this->endpoint}($this->args);
			$httpStatus = $data['status']=="error"?400:200;
			return $this->response($data, $httpStatus);
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
			$result = json_decode($body, true);
		}
		else{
			try {
				$result = new \SimpleXMLElement($body);
			}
			catch (\Exception $e){
				if($e->getMessage() == "String could not be parsed as XML"){

					$result = $phpInput;
				}
			}
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



	private function parse_raw_http_request($input)
	{
		$a_data = $result = [];
		// read incoming data

		// grab multipart boundary from content type header
		preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
		$boundary = $matches[1];

		if(strlen($boundary) == 0) {
			$exp = explode("Content-Disposition", $input);
			$boundary = trim($exp[0]);
			$boundary = str_replace("-", "", $boundary);
		}

		$this->log("FILE boundary", $boundary);

		// split content by boundary and get rid of last -- element
		$a_blocks = preg_split("/-+$boundary/", $input);
		array_pop($a_blocks);

		// loop data blocks
		foreach ($a_blocks as $id => $block)
		{
			$prefix = "";
			if (empty($block))
				continue;

			// you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

			// parse uploaded files
			if (strpos($block, 'Content-Transfer-Encoding') !== FALSE)
			{
				// match "name", then everything after "stream" (optional) except for prepending newlines
				preg_match("/name=\"([^\"]*)\".*Content-Transfer-Encoding: binary[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
				$prefix = "file__";

				$expUrl = explode("/", $matches[1]);
				$matches[1] = $expUrl[count($expUrl)-1];
				if($expUrl[count($expUrl)-1] == "web"){
					$prefix = "";
				}
			}
			// parse all other fields
			else
			{
				// match "name" and optional value in between newline sequences
				preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
			}

			$a_data[$prefix.$matches[1]] = $matches[2];
		}

		foreach($a_data as $name=>$binary){
			if(substr_count($name, "file__") > 0) {
				$extName = explode(".", $name);
				$ext = $extName[count($extName) - 1];

				$pathFile = $_SERVER["DOCUMENT_ROOT"] . "/upload/tmp/" . randString(15) . "." . $ext;
				file_put_contents($pathFile, $binary);

				$result[] = $pathFile;

				unset($a_data[$name]);
			}
		}

		return ['data'=>$a_data, 'file'=>$result];
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

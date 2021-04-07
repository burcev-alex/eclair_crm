<?

namespace Studiobit\Base\Rest;

use Studiobit\Base;
use Studiobit\Base\IblockOrm;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

/**
 * Работа c RESTful
 * Class RestClient
 */
class RestClient implements \Iterator, \ArrayAccess
{

	public $options;
	public $handle; // cURL resource handle.
	public $url;

	// Populated after execution:
	public $response; // Response body.
	public $headers; // Parsed reponse header object.
	public $info; // Response info object.
	public $error; // Response error string.
	public $response_status_lines; // indexed array of raw HTTP response status lines.

	protected $debug = false;

	// Populated as-needed.
	public $decoded_response; // Decoded response body.

	public function __construct($options = [])
	{
		$strDebug = Main\Config\Option::get('studiobit.base', 'debug', 'N');

		if ($strDebug == "Y") {
			$this->debug = true;
		} else {
			$this->debug = false;
		}

		$default_options = [
			'headers' => [],
			'parameters' => [],
			'curl_options' => [],
			'user_agent' => "PHP RestClient/0.1.6",
			'base_url' => null,
			'format' => null,
			'format_regex' => "/(\w+)\/(\w+)(;[.+])?/",
			'decoders' => [
				'json' => 'json_decode',
				'php' => 'unserialize',
				'plain' => 'trim'
			],
			'username' => null,
			'password' => null
		];

		$this->options = array_merge($default_options, $options);
		if (array_key_exists('decoders', $options)) {
			$this->options['decoders'] = array_merge(
				$default_options['decoders'],
				$options['decoders']
			);
		}
	}

	public function set_option($key, $value)
	{
		$this->options[$key] = $value;
	}

	public function register_decoder($format, $method)
	{
		// Decoder callbacks must adhere to the following pattern:
		//   array my_decoder(string $data)
		$this->options['decoders'][$format] = $method;
	}

	// Iterable methods:
	public function rewind()
	{
		$this->decode_response();

		return reset($this->decoded_response);
	}

	public function current()
	{
		return current($this->decoded_response);
	}

	public function key()
	{
		return key($this->decoded_response);
	}

	public function next()
	{
		return next($this->decoded_response);
	}

	public function valid()
	{
		return is_array($this->decoded_response)
			&& (key($this->decoded_response) !== null);
	}

	// ArrayAccess methods:
	public function offsetExists($key)
	{
		$this->decode_response();

		return is_array($this->decoded_response) ?
			isset($this->decoded_response[$key]) : isset($this->decoded_response->{$key});
	}

	public function offsetGet($key)
	{
		$this->decode_response();
		if (!$this->offsetExists($key)) {
			return null;
		}

		return is_array($this->decoded_response) ?
			$this->decoded_response[$key] : $this->decoded_response->{$key};
	}

	public function offsetSet($key, $value)
	{
		throw new \Exception("Decoded response data is immutable.");
	}

	public function offsetUnset($key)
	{
		throw new \Exception("Decoded response data is immutable.");
	}

	/**
	 * GET запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function get($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'GET', $parameters, $headers);
	}

	/**
	 * POST запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function post($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'POST', $parameters, $headers);
	}

	/**
	 * PUT запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function put($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'PUT', $parameters, $headers);
	}

	/**
	 * PATCH запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function patch($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'PATCH', $parameters, $headers);
	}

	/**
	 * DELETE запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function delete($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'DELETE', $parameters, $headers);
	}

	/**
	 * HEAD запрос
	 *
	 * @param $url
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function head($url, $parameters = [], $headers = [])
	{
		return $this->execute($url, 'HEAD', $parameters, $headers);
	}

	/**
	 * Генерация запросов
	 *
	 * @param $url
	 * @param string $method
	 * @param array $parameters
	 * @param array $headers
	 *
	 * @return RestClient
	 */
	public function execute($url, $method = 'GET', $parameters = [], $headers = [])
	{
		$client = clone $this;
		$client->url = $url;
		$client->handle = curl_init();
		$curlopt = [
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_USERAGENT => $client->options['user_agent'],
			CURLOPT_CONNECTTIMEOUT => 100,
			CURLOPT_TIMEOUT => 7200
		];

		if ($client->options['username'] && $client->options['password']) {
			$curlopt[CURLOPT_USERPWD] = sprintf(
				"%s:%s",
				$client->options['username'],
				$client->options['password']
			);
		}

		if (count($client->options['headers']) || count($headers)) {
			$curlopt[CURLOPT_HTTPHEADER] = [];
			$headers = array_merge($client->options['headers'], $headers);
			foreach ($headers as $key => $values) {
				foreach (is_array($values) ? $values : [$values] as $value) {
					$curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
				}
			}
		}

		if ($client->options['format']) {
			$client->url .= '.' . $client->options['format'];
		}

		// Allow passing parameters as a pre-encoded string (or something that
		// allows casting to a string). Parameters passed as strings will not be
		// merged with parameters specified in the default options.
		if (is_array($parameters)) {
			$parameters = array_merge($client->options['parameters'], $parameters);
			$parameters_string = http_build_query($parameters);
		} else {
			$parameters_string = (string)$parameters;
		}

		if (strtoupper($method) == 'POST') {
			if (!defined('REST_CONVERT_UNICODE')) {
				$parameters_string = json_encode($parameters, JSON_UNESCAPED_UNICODE);
			} else {
				$parameters_string = json_encode($parameters);
			}

			$curlopt[CURLOPT_RETURNTRANSFER] = true;
			$curlopt[CURLOPT_POST] = true;
			$curlopt[CURLOPT_POSTFIELDS] = $parameters_string;

			$curlopt[CURLOPT_HTTPHEADER] = array(
				'Content-Type: application/json',
				'Content-Length: ' . mb_strlen($parameters_string));
		} elseif (strtoupper($method) != 'GET') {
			$curlopt[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
			$curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
		} elseif ($parameters_string) {
			$client->url .= strpos($client->url, '?') ? '&' : '?';
			$client->url .= $parameters_string;
		}

		if ($client->options['base_url']) {
			if ($client->url[0] != '/' && substr($client->options['base_url'], -1) != '/') {
				$client->url = '/' . $client->url;
			}
			$client->url = $client->options['base_url'] . $client->url;
		}

		$curlopt[CURLOPT_URL] = $client->url;

		if ($client->options['curl_options']) {
			// array_merge would reset our numeric keys.
			foreach ($client->options['curl_options'] as $key => $value) {
				$curlopt[$key] = $value;
			}
		}
		curl_setopt_array($client->handle, $curlopt);
        
		$client->parse_response(curl_exec($client->handle));
		$client->info = (object)curl_getinfo($client->handle);
		$client->error = curl_error($client->handle);

		if ($this->debug) {
			#p2f($curlopt[CURLOPT_HTTPHEADER]);
			#p2f($curlopt);
			#p2f($client->info);
			#p2f($client->error);
		}

		curl_close($client->handle);

		return $client;
	}

	/**
	 * парсинг ответа REST
	 *
	 * @param $response
	 */
	public function parse_response($response)
	{
		if ($this->debug) {
			p2f("PARSE RESPONSE: " . $response);
		}

		$headers = [];
		$this->response_status_lines = [];
		$line = strtok($response, "\n");
		do {
			if (strlen(trim($line)) == 0) {
				// Since we tokenize on \n, use the remaining \r to detect empty lines.
				if (count($headers) > 0) {
					break; // Must be the newline after headers, move on to response body
				}
			} elseif (strpos($line, 'HTTP') === 0) {
				// One or more HTTP status lines
				$this->response_status_lines[] = trim($line);
			} else {
				// Has to be a header
				list($key, $value) = explode(':', $line, 2);
				$key = trim(strtolower(str_replace('-', '_', $key)));
				$value = trim($value);

				if (empty($headers[$key])) {
					$headers[$key] = $value;
				} elseif (is_array($headers[$key])) {
					$headers[$key][] = $value;
				} else {
					$headers[$key] = [$headers[$key], $value];
				}
			}
		} while ($line = strtok("\n"));

		$this->headers = (object)$headers;
		$this->response = strtok("");

		if (!$this->response) {
			// поиск 200 ОК ответа
			foreach ($this->response_status_lines as $lineHeader) {
				if (substr_count($lineHeader, "200") > 0) {
					$this->response = "OK";
				}
			}
		}

		if (mb_detect_encoding($this->response) != "UTF-8") {
			if (substr_count($this->response, "Message") == 0) {
				$this->response = iconv(mb_detect_encoding($this->response), "utf8", str_replace(array(" ", "\0"), array("", ""), trim($this->response)));
			}
		}

		if ($this->debug) {
			#p2f($this->response_status_lines);
			#p2f($this->headers);
			#p2f($this->response);
		}
	}

	/**
	 * Определить в каом формате пришел ответ
	 * @return mixed
	 * @throws \Exception
	 */
	public function get_response_format()
	{
		if (!$this->response) {
			throw new \Exception(
				"A response must exist before it can be decoded. " . $this->error . ". Url: " . $this->url
			);
		}

		// User-defined format.
		if (!empty($this->options['format'])) {
			return $this->options['format'];
		}

		// Extract format from response content-type header.
		if (!empty($this->headers->content_type)) {
			if (preg_match($this->options['format_regex'], $this->headers->content_type, $matches)) {
				return $matches[2];
			}
		}

		throw new \Exception(
			"Response format could not be determined." . $this->error . ". Url: " . $this->url
		);
	}

	/**
	 * Декодирование результатов
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function decode_response()
	{
		if (empty($this->decoded_response)) {
			$format = $this->get_response_format();
			if (!array_key_exists($format, $this->options['decoders'])) {
				throw new \Exception("'${format}' is not a supported " .
					"format, register a decoder to handle this response." . $this->error . ". Url: " . $this->url);
			}

			$this->decoded_response = call_user_func(
				$this->options['decoders'][$format],
				$this->response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
			);
		}

		return $this->decoded_response;
	}
}

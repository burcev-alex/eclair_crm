<?

namespace App\Base\Rest;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use App\Base;
use App\Base\Rest;

class ServerApi extends Rest\Api
{
	protected $apiKey;
	protected $debug = false;

	public function __construct($request, $origin)
	{
		global $USER;
		parent::__construct($request);

		$this->apiKey = Main\Config\Option::get('app.base', 'server_rest_api', '123456');

		if (Main\Config\Option::get('app.base', 'debug', 'N') == "Y") {
			$this->debug = true;
		}

		if (!array_key_exists('apiKey', $this->request)) {
			throw new \Exception('No API Key provided');
		} elseif ($this->apiKey != $this->request['apiKey']) {
			throw new \Exception('Invalid API Key');
		}

		// авторизуемся под админов
		$USER->Authorize(1);
	}

	/**
	 * Тестовый запрос
	 * @return string
	 */
	protected function test()
	{
		if ($this->method == 'GET') {
			return array("message" => "Hello world!");
		} else {
			return array("message" => "Only accepts GET requests");
		}
	}
}

<?

namespace App\Integration\Rest\Server;

use Bitrix\Main\Config;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use App\Base\Rest;
use App\Integration as Union;

class Main extends Rest\Api
{
	protected $apiKey;
	protected $content;
	protected $debug = false;

	/**
	 * @var место хранения данных
	 */
	private $repository;

	public function __construct($request, $origin)
	{
		parent::__construct($request);

		if (Config\Option::get('app.integration', 'debug', 'N') == "Y") {
			$this->debug = true;
		}

		$this->apiKey = Config\Option::get('app.integration', 'server_rest_api');

		$arrHeaders = [];
		foreach($this->headers as $key=>$value){
			$arrHeaders[mb_strtolower($key)] = $value;
		}

		if (!array_key_exists('x-apikey', $arrHeaders)) {
            throw new \Exception('No API Key provided');
        } elseif ($this->apiKey != $arrHeaders['x-apikey']) {
            throw new \Exception('Invalid API Key');
        }

		$this->init();
	}

	/**
	 * Инициализация соединения
	 */
	private function init()
    {
		global $USER;

		// авторизуемся под админов
		$USER->Authorize(1);
	}

	/**
     * Тестовый запрос
     *
     * @return array
     */
    protected function test()
    {
        if ($this->method == 'GET') {
            return [
                'status' => 'success',
                'data' => [
                    'message' => 'Hello world!',
                    'header' => $this->headers,
                ],
            ];
        } else {
            return [
                'status' => 'error',
                'data' => ['message' => 'Only accepts GET requests'],
            ];
        }
    }

	/**
	 * Добавление заказа
	 *
	 * @return array
	 */
	protected function order()
	{
		if ($this->method == 'POST') {

			$resource = $this->content;
			
			$entity = new Union\Queue\Deal\IncomingOrder();

			if($entity instanceof Union\Queue\AbstractBase){
				$entity->init($resource)->command();
				$result = $entity->result();
			}
			else{
				$result = [];
			}

			if(count($result) > 0){
				$arData['status'] = "success";
				$arData['data'] = $result;
			}
			else{
				$arData['status'] = "error";
				$arData['data'] = [];
			}

			return $arData;
		} else {
			return array(
				'status' => 'error',
				'data' => ["message" => "Only accepts POST requests"]
			);
		}
	}
}

<?

namespace App\Integration\Rest\Client;

use App\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use App\Integration\Rest;

Loc::loadMessages(__FILE__);

/**
 * Работа c шиной Web
 * Class Web
 */
class Web extends Rest\Client\AbstractBase
{
	protected $url = "";
	protected $host = '';
	protected $api;
	private $token = "";
	private $userId;

	protected $format = "json";

	public function __construct($host, $url, $token, $format='json', $userId = 1)
	{
		// settings
		$this->url = $url;
		$this->host = $host;
		$this->token = $token;
		$this->format = $format;
		$this->userId = $userId;

		$option = array(
			'format' => $this->format
		);

		if(strlen($this->token) > 0){
			$option['headers']['X-ApiKey'] = $this->token;
			$option['headers']['X-ServiceAuthor'] = $userId;
			#$option['headers']['content_type'] = 'application/json;charset=utf-8';
		}

		$this->api = new Base\Rest\RestClient($option);
	}

	/**
	 * Отправка информации по товару
	 * 
	 * @param $action
	 * @param $data
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function product($action, $data)
	{
		$id = 0;
		if($action != "add"){
			$id = $data['ID'];
		}
		
		$response_json = array();
		$method = "product/".$id."/".$action."/";

		$data['params'] = [
			'action' => $action,
			'id' => $id,
			'method' => 'product'
		];

		$requestString = json_encode($data); // JSON_UNESCAPED_UNICODE
		p2f(json_encode($data, JSON_PRETTY_PRINT));

		try {
			$result = $this->api->post($this->url . $method, $requestString);
			$response_json = $result->decode_response();

			$this->getWarningMessage($response_json, $result, $this->url . $method, $data);

		} catch (\Exception $e) {
			$response_json = $this->getErrorMessage($this->url . $method, $data);
			$response_json['errors'] = $e->getMessage();
		}

		return $response_json;
	}

	/**
	 * Отправка информации по разделу
	 * 
	 * @param $action
	 * @param $data
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function section($action, $data)
	{
		$id = 0;
		if($action != "add"){
			$id = $data['ID'];
		}
		
		$response_json = array();
		$method = "section/".$id."/".$action."/";

		$data['params'] = [
			'action' => $action,
			'id' => $id,
			'method' => 'section'
		];

		$requestString = json_encode($data); // JSON_UNESCAPED_UNICODE
		
		p2f(json_encode($data, JSON_PRETTY_PRINT));

		try {
			$result = $this->api->post($this->url . $method, $requestString);
			$response_json = $result->decode_response();

			$this->getWarningMessage($response_json, $result, $this->url . $method, $data);

		} catch (\Exception $e) {
			$response_json = $this->getErrorMessage($this->url . $method, $data);
			$response_json['errors'] = $e->getMessage();
		}

		return $response_json;
	}

	/**
	 * Отправка информации по свойству
	 * 
	 * @param $action
	 * @param $data
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function property($action, $data)
	{
		$id = 0;
		if($action != "add"){
			$id = $data['ID'];
		}
		
		$response_json = array();
		$method = "property/".$id."/".$action."/";

		$data['params'] = [
			'action' => $action,
			'id' => $id,
			'method' => 'property'
		];

		$requestString = json_encode($data); // JSON_UNESCAPED_UNICODE
		p2f(json_encode($data, JSON_PRETTY_PRINT));

		try {
			$result = $this->api->post($this->url . $method, $requestString);
			$response_json = $result->decode_response();

			$this->getWarningMessage($response_json, $result, $this->url . $method, $data);

		} catch (\Exception $e) {
			$response_json = $this->getErrorMessage($this->url . $method, $data);
			$response_json['errors'] = $e->getMessage();
		}

		return $response_json;
	}
}

?>
<?

namespace Studiobit\Base\Integration;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Studiobit\Base\Integration\FileObject;

Loc::loadMessages(__FILE__);

/**
 * Работа c шиной
 * Class ErpClient
 */
class ErpClient
{
	protected $url = "https://host/api/";
	protected $host = 'host';
	protected $api;
	private $authLogin = "admin";
	private $authPass = "admin";
	protected $isAuth = false;

	protected $format = "json";

	public function __construct()
	{
		// settings
		$this->url = Main\Config\Option::get('studiobit.base', 'bdko_url', '');
		$this->host = Main\Config\Option::get('studiobit.base', 'bdko_host', '');
		$this->authLogin = Main\Config\Option::get('studiobit.base', 'bdko_ntlm_login', '');
		$this->authPass = Main\Config\Option::get('studiobit.base', 'bdko_ntlm_pass', '');
		$this->format = Main\Config\Option::get('studiobit.base', 'bdko_format', 'json');

		if ((strlen($this->authPass) > 0) && (strlen($this->authLogin) > 0)) {
			$this->isAuth = true;
		}

		if ($this->isAuth) {
			$option = array(
				'username' => $this->authLogin,
				'password' => $this->authPass
			);
		} else {
			$option = array();
		}
		$this->api = new Base\Rest\RestClient($option);
	}

	/**
	 * работа с контрагентами (компаниями)
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function Customer($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "POST") {
				$result = $this->api->post($this->url . __FUNCTION__, $data);
				$response_json = $result->decode_response();
			} else if ($type == "PUT") {
				$result = $this->api->put($this->url . __FUNCTION__, $data);
				$response_json = $result->decode_response();
			} else if ($type == "DELETE") {
				if (strlen($data["ID"]) > 0) {
					$result = $this->api->delete($this->url . __FUNCTION__ . "/" . $data["ID"], $data);
					$response_json = $result->decode_response();
				}
			} else if ($type == "GET") {
				$stringOptions = "";
				if (strlen($options["inn"]) > 0) {
					$stringOptions = "/?inn=" . $options["inn"];
				} else if (strlen($data["ID"]) > 0) {
					$stringOptions = "/" . $data["ID"];
				}
				$result = $this->api->get($this->url . __FUNCTION__ . $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (Base\RestClientException $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Документы
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function Document($type = "POST", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "POST") {
				$result = $this->api->post($this->url . __FUNCTION__, $data);
				$response_json = $result->decode_response();
			} else if ($type == "PUT") {
				$result = $this->api->put($this->url . __FUNCTION__ . "/" . $data["ID"], $data);
				$response_json = $result->decode_response();
			} else if ($type == "DELETE") {
				if (strlen($data["ID"]) > 0) {
					$result = $this->api->delete($this->url . __FUNCTION__ . "/" . $data["ID"], $data);
					$response_json = $result->decode_response();
				}
			} else if ($type == "GET") {
				$stringOptions = "";
				if (strlen($data["roi_id"]) > 0) {
					$stringOptions = "/" . $options["roi_id"];
				}

				$result = $this->api->get($this->url . __FUNCTION__ . $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (Base\RestClientException $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Справочник - Тип клиента
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function CustomerType($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "";

				$result = $this->api->get($this->url . __FUNCTION__ . $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (Base\RestClientException $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}
}

?>
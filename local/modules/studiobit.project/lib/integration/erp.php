<?

namespace Studiobit\Project\Integration;

use Studiobit\Base;

/**
 * Работа c шиной
 * Class ErpClient
 */
class ErpClient
{
	protected $url;
	protected $host;
	protected $api;
	protected $apiKey;
	private $authLogin;
	private $authPass;
	protected $isAuth = false;

	protected $format = "json";

	public function __construct()
	{
		// settings
		$this->url = 'https://oldcrm.gk-strizhi.ru/api/v1/';
		$this->host = 'oldcrm.gk-strizhi.ru';
		$this->authLogin = '';
		$this->authPass = '';
		$this->format = 'php';
		$this->apiKey = '123456';

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
	 * ЖК
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 * @throws \Exception
	 */
	public function building($type = "GET", $data, $options = array())
	{
		$response_json = array();
        
		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Объекты ЖК
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function objects($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

    /**
     * Фото квартир
     *
     * @param string $type
     * @param $data
     * @param array $options
     *
     * @return array|mixed
     */
    public function objects_pictures($type = "GET", $data, $options = array())
    {
        $response_json = array();

        try {
            if ($type == "GET") {
                $stringOptions = "apiKey=".$this->apiKey;
                $result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
                $response_json = $result->decode_response();
            }
        } catch (\Exception $e) {
            $response_json = array("ERROR" => $e->getMessage());
        }

        return $response_json;
    }

	/**
	 * Контакты
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function contacts($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

    public function timeline($type = "GET", $data, $options = array())
    {
        $response_json = array();

        try {
            if ($type == "GET") {
                $stringOptions = "apiKey=".$this->apiKey;
                $result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
                $response_json = $result->decode_response();
            }
        } catch (\Exception $e) {
            $response_json = array("ERROR" => $e->getMessage());
        }

        return $response_json;
    }

	public function timeline_contact($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	public function timeline_deal($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Сделки
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function deals_list($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Компании
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function company($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Пользователи системы
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function users($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

	/**
	 * Мои активности
	 *
	 * @param string $type
	 * @param $data
	 * @param array $options
	 *
	 * @return array|mixed
	 */
	public function activity($type = "GET", $data, $options = array())
	{
		$response_json = array();

		try {
			if ($type == "GET") {
				$stringOptions = "apiKey=".$this->apiKey;
				$result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
				$response_json = $result->decode_response();
			}
		} catch (\Exception $e) {
			$response_json = array("ERROR" => $e->getMessage());
		}

		return $response_json;
	}

    /**
     * Мои активности (без лишней информации)
     *
     * @param string $type
     * @param $data
     * @param array $options
     *
     * @return array|mixed
     */
    public function activityLite($type = "GET", $data, $options = array())
    {
        $response_json = array();

        try {
            if ($type == "GET") {
                $stringOptions = "apiKey=".$this->apiKey;
                $result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
                $response_json = $result->decode_response();
            }
        } catch (\Exception $e) {
            $response_json = array("ERROR" => $e->getMessage());
        }

        return $response_json;
    }

    /**
     * Транзакции
     *
     * @param string $type
     * @param $data
     * @param array $options
     *
     * @return array|mixed
     */
    public function transactions($type = "GET", $data, $options = array())
    {
        $response_json = array();

        try {
            if ($type == "GET") {
                $stringOptions = "apiKey=".$this->apiKey;
                $result = $this->api->get($this->url . __FUNCTION__ . "/?". $stringOptions, $data);
                $response_json = $result->decode_response();
            }
        } catch (\Exception $e) {
            $response_json = array("ERROR" => $e->getMessage());
        }

        return $response_json;
    }
}

?>
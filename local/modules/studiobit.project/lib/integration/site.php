<?

namespace Studiobit\Project\Integration;

use Studiobit\Base;

/**
 * Работа c сайтом
 * Class SiteClient
 */
class SiteClient
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
		$this->url = 'https://gk-strizhi.ru/api/v1/';
		$this->host = 'gk-strizhi.ru';
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

    private function get($function, $data, $options = array())
    {
        try {
            $stringOptions = "apiKey=".$this->apiKey;
            $result = $this->api->get($this->url . $function . "/?". $stringOptions, $data);
            $response_json = $result->decode_response();
        } catch (\Exception $e) {
            $response_json = array("ERROR" => $e->getMessage());
        }

        return $response_json;
    }

    public function realtor_change_manager($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function notice_expire_soon($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function change_contact_stage($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function change_stage($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function payment_deal($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function sms($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }

    public function dismissal_manager($data, $options = array())
    {
        return $this->get(__FUNCTION__, $data, $options);
    }
}

?>
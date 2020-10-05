<?
namespace Studiobit\Base\Integration;

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
 * Работа c CloudConvert
 * Class CloudConvert
 */
class CloudConvert {
	protected $url = "https://api.cloudconvert.com/convert";
	protected $api;
	protected $urlCloud;
	protected $fileName;

	public function __construct(){
		$this->url = Main\Config\Option::get('studiobit.base', 'cloudconvert_url', '');
		$this->api = Main\Config\Option::get('studiobit.base', 'cloudconvert_api', '');
	}

	/**
	 * Конвертация файла из одного формата в другой
	 * @param $pathFile
	 * @param $fileName
	 * @param string $formatFrom
	 * @param string $formatTo
	 * @return string
	 */
	public function query($pathFile, $fileName, $formatFrom = "docx", $formatTo = "pdf"){
		$result = $resultCurl = "";

		$host = Main\Config\Option::get('main', 'server_name', '');
		$pathFile = $host.$pathFile;

		if(substr_count($pathFile, "http") == 0){
			$pathFile = "http://".$pathFile;
		}

		// записываем название файла
		$this->fileName = $fileName;

		// конвертация
		if($formatTo == "pdf") {
			$query = "https://api.cloudconvert.com/convert?apikey=" . $this->api . "&input=download&filename=" . $fileName . ".docx&download=inline&save=true&inputformat=" . $formatFrom . "&outputformat=" . $formatTo . "&file=" . $pathFile;
		}
		else if($formatTo == "jpg"){
			$query = "https://api.cloudconvert.com/convert?apikey=" . $this->api . "&input=download&filename=" . $fileName . ".pdf&download=inline&save=true&timeout=0&wait=true&converteroptions[resize]=&converteroptions[resizemode]=maxiumum&converteroptions[resizeenlarge]=&converteroptions[strip_metatags]=false&converteroptions[density]=300&converteroptions[page_range]=1-1&converteroptions[disable_alpha]=true&converteroptions[quality]=70&inputformat=" . $formatFrom . "&outputformat=" . $formatTo . "&file=" . $pathFile;
		}

		if($curl = curl_init()){
			$headers = array("Content-type: application/x-www-form-urlencoded; charset=utf-8");
			curl_setopt($curl, CURLOPT_URL, $query);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$resultCurl = curl_exec($curl);
			curl_close($curl);
		}

		if(substr_count($resultCurl, "error") == 0) {
			$tmpExp = explode("https://", $resultCurl);
			$this->urlCloud = trim("https://" . $tmpExp[1]);
		}
		else{
			AddMessage2Log($resultCurl);
			$this->urlCloud = "";
		}

		return $this;
	}

	/**
	 * Скачиваем файл который был сконвертировать сервисом
	 * @return string
	 */
	public function download($format = "pdf"){
		$filePath = "";
		if($format == "jpg") {
			$dirName = "/upload/agreement/jpg/";
		}
		else{
			$dirName = "/upload/agreement/pdf/";
		}

		// скачиваем файл
		if(strlen($this->urlCloud) > 0) {
			if ($ch = curl_init($this->urlCloud)) {
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
				$output = curl_exec($ch);

				// определить название файла
				$arExt = explode(".", $this->fileName);
				$name = $arExt[0] . "." . $format;

				// путь к файлу
				$filePath = $dirName . $name;

				// проверить что нет ошибок
				if (substr_count($output, "error") == 0) {
					$fh = fopen($_SERVER["DOCUMENT_ROOT"] . $filePath, 'w');
					fwrite($fh, $output);
					fclose($fh);
				}
			}
		}
		else{
			$filePath = "";
		}

		return $filePath;
	}
}
?>
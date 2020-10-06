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
 * Работа c утилитой unoconv
 * Class Unoconv
 */
class Unoconv {

	// ссылка на файл
	protected $urlCloud;

	// записываем название файла
	protected $fileName;

	/**
	 * Конвертация файла из одного формата в другой
	 *
	 * @param $pathFile
	 * @param $fileName
	 * @param string $formatFrom
	 * @param string $formatTo
	 *
	 * @return $this
	 */
	public function query($pathFile, $fileName, $formatFrom = "docx", $formatTo = "pdf"){

		// записываем название файла
		$this->fileName = $fileName;

		// определить название файла
		$arExt = explode(".", $this->fileName);
		$this->fileName = $arExt[0].".".$formatTo;

		$dirPath = "/upload/agreement/";

		// путь к файлу
		$this->urlCloud = $pathFile;

		$docRoot = $_SERVER["DOCUMENT_ROOT"];
		if(substr_count($this->urlCloud, $docRoot) == 0){
			$this->urlCloud = $docRoot.$this->urlCloud;
		}

		exec("unoconv -f ".$formatTo." -o '".$docRoot.$dirPath.$formatTo."/".$this->fileName."' '".$this->urlCloud."'", $output);

		return $this;
	}

	/**
	 * Скачиваем файл который был сконвертировать сервисом
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public function download($format = "pdf"){
		$dirName = "/upload/agreement/".$format."/";

		// определить название файла
		$arExt = explode(".", $this->fileName);
		$name = $arExt[0].".".$format;

		// путь к файлу
		$filePath = $_SERVER["DOCUMENT_ROOT"].$dirName.$name;

		if(!file_exists($filePath)){
			$filePath = "";
		}

		return str_replace($_SERVER["DOCUMENT_ROOT"], "", $filePath);
	}
}
?>
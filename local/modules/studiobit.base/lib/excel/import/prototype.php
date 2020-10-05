<?
namespace Studiobit\Base\Excel\Import;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;

Loc::loadMessages(__FILE__);

/**
 * Импорт данных из экселя, базовый класс
 * Class Import
 */
class Prototype
{
	const IBLOCK_ID = 57;
	protected $data = false;
	protected $errors = array();
	protected $log = array();
	protected $arSettings = array();
    protected $options = array();
    protected $arParams = array();
	
	public function __construct($params = array())
	{
        $this->arParams = $params;
	}
	
	public function getErrors(){
		return $this->errors;
	}
	
	/**
	 * Парсит excel
	 * @return array
	 */
	public function getFile()
    {
        if(empty($this->options['PATH']))
        {
            $tmpFilePath = '/upload/tmp/';

            $fileExt = GetFileExtension($_FILES['file']['name']);
            $filename = substr(md5(time()), 0, 8).'.'.$fileExt;

            $filePath = $_SERVER["DOCUMENT_ROOT"] . $tmpFilePath . $filename;

            move_uploaded_file($_FILES['file']['tmp_name'], $filePath);

            if (!file_exists($filePath)) {
                $this->errors[] = 'Файл не найден';
                return false;
            }

            $this->options['PATH'] = $filePath;
        }
		
		return $this->options['PATH'];
	}

    protected function checkFile()
    {
        $filePath = $this->getFile();
        if($filePath !== false) {
            $fileExt = \GetFileExtension($filePath);
            if (!in_array($fileExt, array('xls', 'xlsx', 'ods'))) {
                $this->errors[] = 'Данный формат файла не поддерживается';
                return false;
            }

            if (ini_get('mbstring.func_overload') != 0) {
                if ($fileExt !== 'xlsx') {
                    $this->errors[] = 'Поддерживается только формат файлов xlsx. Для поддержки форматов xls и ods обратитесь к администратору.';
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    protected function sheet()
    {
        static $sheet = false;

        if($sheet === false) {
            $filePath = $this->getFile();

			$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $objPHPExcel = $objReader->load($filePath);
            unset($objReader);

            $objPHPExcel->setActiveSheetIndex(0); //for *.ods files
            $sheet = $objPHPExcel->getActiveSheet();
        }

        return $sheet;
    }

    protected function calculateCount()
    {
        if($this->options['ALL'] == 0) {
            $sheet = $this->sheet();
            $settings = $this->getSettings();
            $start = (int)$settings['PROPERTY_START_VALUE'];

            if($start == 0)
                $start = 2;

            while($start)
            {
                $set = false;
                foreach($settings['PROPERTY_SETTINGS_VALUE'] as $col => $ufName)
                {
                    if(strlen($ufName)){
                        $cell = $sheet->getCellByColumnAndRow($col, $start);
                        $value = $cell->getValue();
                        
                        if(!empty($value)) 
						{
                            p2log(array('col' => $col - 1, 'row' => $start, 'value' => $value), 'import');
                            $set = true;
                            break;
                        }
                    }
                }

                if($set)
                {
                    $this->options['ALL']++;
                    $start++;
                }
                else
                {
                    $start = 0;
                }
            }
        }
        p2log('строк '.$this->options['ALL'], 'import');
        return $this->options['ALL'];
    }

    public function isTime($ufName){
        return false;
    }
	
	public function loadData()
	{
        $sheet = $this->sheet();

        $settings = $this->getSettings();
        $start = (int)$settings['PROPERTY_START_VALUE'] + $this->options['LOAD'];
        $result = array();

        $row = 0;
        
        while($start && $row < $this->options['SIZE'])
        {
            $set = false;
            $item = array();
            foreach ($settings['PROPERTY_SETTINGS_VALUE'] as $col => $ufName) {
                if (strlen($ufName)) {
                    $cell = $sheet->getCellByColumnAndRow($col + 1, $start);
                    $value = $cell->getFormattedValue();

                    if (strstr($value, '=') == true) {
                        $value = $cell->getOldCalculatedValue();
                    }
                    else
                    {
                        if ($this->isTime($ufName) && $value >= 0 && $value <= 1) {
                            $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($value, 'hh:mm');
                        }
                        elseif(\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) && !$this->isTime($ufName)) {
                            $value= $cell->getValue();
                            $value = \ConvertTimeStamp(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value), 'FULL');
                        }
                    }

                    $item[$ufName] = $value;
                    if (!empty($value))
                        $set = true;
                }
            }

            if ($set)
            {
                $result[] = $item;
                $start++;
            }
            else
            {
                $start = 0;
            }

            $row++;
        }

        $this->options['LOAD'] += count($result);
        return $result;
	}
	
	public function getData()
	{
		if(!is_array($this->data)){
			$this->data = $this->loadData();
		}
		
		return $this->data;
	}

    protected function getSettingsCode(){
        return 0;
    }
	
	public function getSettings()
    {
        if(empty($this->arSettings)){
			if(\Bitrix\Main\Loader::includeModule('iblock')){
				
				$arFilter = array('IBLOCK_ID' => self::IBLOCK_ID, '=CODE' => $this->getSettingsCode());
				$rsSettings = \CIBlockElement::GetList(array(), $arFilter, false, false, array('ID', 'PROPERTY_SETTINGS', 'PROPERTY_START', 'PROPERTY_FILE'));
				if($arSettings = $rsSettings->Fetch()){
					if((int)$arSettings['PROPERTY_FILE_VALUE']){
						$arSettings['PROPERTY_FILE_VALUE'] = \CFile::GetFileArray($arSettings['PROPERTY_FILE_VALUE']);
					}
					
					//ksort($arSettings['PROPERTY_SETTINGS_VALUE']);
					
					$this->arSettings = $arSettings;
				}
			}
		}
		return $this->arSettings;
	}

    protected function uid()
    {
        return 'import_'.$this->arSettings['ID'];
    }

    protected function initOptions()
    {
        $uid = $this->uid();

        if(isset($_SESSION[$uid]) && is_array($_SESSION[$uid]) && empty($_FILES['file'])){
            $this->options = $_SESSION[$uid];
        }
        else
        {
            $this->options = array(
                'ALL' => 0,
                'LOAD' => 0,
                'STATE' => '',
                'SIZE' => 5,
                'PATH' => ''
            );
        }
    }

    protected function saveOptions()
    {
        $_SESSION[$this->uid()] = $this->options;
    }

    protected function clearOptions()
    {
        unset($_SESSION[$this->uid()]);
    }

    public function import(){

    }

	public function run()
    {
        $this->initOptions();

        if(empty($this->options['STATE']))
        {
            if($this->checkFile()) {
                $this->options['STATE'] = 'LOAD';
            }
        }
        elseif($this->options['STATE'] == 'LOAD')
        {
            if($this->calculateCount()){
                $this->options['STATE'] = 'IMPORT';
            }
            else
            {
                $this->errors[] = 'Файл с данными пуст или имеет некорректный формат.';
            }
        }
        elseif($this->options['STATE'] == 'IMPORT')
        {
            $this->import();

            if($this->options['ALL'] == $this->options['LOAD']){
                $this->options['STATE'] = 'FINISH';
            }
        }

        $this->saveOptions();

        if(!empty($this->errors) || $this->options['STATE'] == 'FINISH')
        {
            $this->clearOptions();
        }

		return empty($this->errors);
	}
	
	public function getLog(){
		return $this->log;
	}

    public function addLog($item){
        $this->log[] = $item;
    }

    public function addSuccess($title, $message){
        $this->log[] = array(
            'MESSAGE' => $message,
            'TITLE' => $title,
            'TYPE' => 'OK'
        );
    }

    public function addError($title, $message){
        $this->log[] = array(
            'MESSAGE' => $message,
            'TITLE' => $title,
            'TYPE' => 'ERROR'
        );
    }

    public function getCount($type = 'ALL')
    {
        if($type == 'LOAD')
            return $this->options['LOAD'];

        return (int)$this->options['ALL'];
    }

    public function getProgress()
    {
        if($this->options['ALL'] > 0)
            return ceil($this->options['LOAD'] * 100 / $this->options['ALL']);
        return 0;
    }

    public function getState()
    {
        return $this->options['STATE'];
    }

    public function getStateMessage()
    {
        $arStates = array(
            'LOAD' => 'Чтение файла',
            'IMPORT' => 'Импорт',
            'FINISH' => 'Импорт завершен'
        );

        return $arStates[$this->getState()];
    }
}
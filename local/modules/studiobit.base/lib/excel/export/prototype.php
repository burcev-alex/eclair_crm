<?
namespace Studiobit\Base\Excel\Export;

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
    const IBLOCK_ID = 58;
	
	const XLSX = 0;
    const PDF = 1;
    const PHPExcel = 2;
    const PhpSpreadsheet = 3;

    protected $objPHPExcel = false;
    protected $errors = array();
    protected $arSettings = array();
    protected $arParams = array();
    protected $arResult = array();

    public function __construct($params = array())
    {
        $this->arParams = $params;
    }

    public function getErrors(){
        return $this->errors;
    }

    protected function getSettingsCode(){
        return '';
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

                    $this->arSettings = $arSettings;
                }
            }
        }
        return $this->arSettings;
    }

    protected function getTypeEntity()
    {
        return Export::PhpSpreadsheet;
    }

    public function getEntity()
    {
        if($this->objPHPExcel === false) {
            $settings = $this->getSettings();
			
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $settings['PROPERTY_FILE_VALUE']['SRC'];

            if($this->getTypeEntity() == Export::PHwPExcel) {
                $inputFileType = \PHPExcel_IOFactory::identify($filePath);
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                //$objReader->setReadDataOnly(true);
                $this->objPHPExcel = $objReader->load($filePath);
            }
            else
            {
                $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $objReader->setIncludeCharts(true);
                $this->objPHPExcel = $objReader->load($filePath);
            }

            unset($objReader);
        }

        return $this->objPHPExcel;
    }

    public function export(){
        //$entity = $this->getEntity();
    }

    public function run()
    {
        try {
            //generate
            $this->export();

        }
        catch(\Exception $e){
            $this->errors[] = $e->getMessage();
        }

        return empty($this->errors);
    }

    public function save($type = Export::XLSX, $file = 'php://output')
    {
        try {
            $settings = $this->getSettings();
            $filename = $settings['PROPERTY_FILE_VALUE']['FILE_NAME'];
            $fileExt = GetFileExtension($filename);

            if($type == Export::PDF)
            {
                $contentType = 'application/pdf';
                $filename = str_replace('.'.$fileExt, '.pdf', $filename);
            }
            else
            {
                $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                $filename = str_replace('.'.$fileExt, '.xlsx', $filename);
            }

            if($file == 'php://output') {
                header('Content-Type: ' . $contentType);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: max-age=0');
            }

            if($type == Export::PDF)
            {
                $from = $_SERVER['DOCUMENT_ROOT'].'/upload/tmp/'.substr(md5(time()), 0, 5).'.xlsx';
                $to = $_SERVER['DOCUMENT_ROOT'].'/upload/tmp/'.$filename;

                $this->saveFile($from);

                $converter = new Converter();

                if($converter->execute($from, $to))
                {
                    if($file == 'php://output') {
                        header('Content-Length: ' . filesize($to));
                        readfile($to);

                    }
                    else
                    {
                        copy($to, $file);
                    }

                    unlink($to);
                }

                unlink($from);
            }
            else
            {
                $this->saveFile($file);
            }
        }
        catch(\Exception $e){
            $this->errors[] = $e->getMessage();
        }

        return empty($this->errors);
    }

    protected function saveFile($file = 'php://output'){
        $entity = $this->getEntity();
        
        if($this->getTypeEntity() == Export::PHPExcel) {
            $objWriter = \PHPExcel_IOFactory::createWriter($entity, 'Excel2007');
        }
        else
        {
            $objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($entity);
            $objWriter->setIncludeCharts(TRUE);
        }

        $objWriter->save($file);
    }
}
<?
namespace Studiobit\Project\Integration;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Localization\Loc;
use Studiobit\Matrix\Entity\Object;

Loc::loadMessages(__FILE__);

/**
 * Работа с продуктами
 * Class Product
 */
class Prices extends Base\Rest\Internal\BaseInternal
{
    protected $iblock_id = 0;
    protected $debug = 'N';
    protected $timeCache = 3600;

    public function __construct()
    {
        if(Main\Loader::includeModule('studiobit.matrix')) {
            $this->iblock_id = Object::getIBlockID();
            $this->debug = \Bitrix\Main\Config\Option::get('studiobit.base', 'debug', 'N');

            if ($this->debug == "Y") {
                $this->timeCache = 1;
            }
        }
    }

    /**
     * Обработка данных которые пришли из внешнего источника
     *
     * @param $arResult
     *
     * @return int
     */
    public function save($arResult)
    {
        $result = 0;
        p2log(\ConvertTimeStamp(false, 'FULL').': Начало импорта цен', 'import_prices');
        p2log(\ConvertTimeStamp(false, 'FULL').': Количество цен - '.count($arResult->PRICES->OBJECT), 'import_prices');
        foreach ($arResult->PRICES->OBJECT as $xml)
        {
            $arItem = self::xml2array($xml);

            $object = Object::getObjectByXmlID($arItem['ID']);

            if($object)
            {
                if (!empty($arItem['CURRENT'])) {
                    $object->setPrice($arItem['CURRENT']);
                }

                if (!empty($arItem['NOMINAL'])) {
                    $object->setNominalPrice($arItem['NOMINAL']);
                }

                if (!empty($arItem['ZA-KV-M'])) {
                    $arItem['ZA-KV-M'] = str_replace(',', '.', $arItem['ZA-KV-M']);
                    $arItem['ZA-KV-M'] = str_replace(' ', '', $arItem['ZA-KV-M']);
                    $arItem['ZA-KV-M'] = (float)$arItem['ZA-KV-M'];
                    $object->prop(['PRICE' => $arItem['ZA-KV-M']]);
                }

                $result++;
            }

            if($result % 500 == 0){
                sleep(2); //пауза 2 секунды, если много записей
            }
        }
        p2log(\ConvertTimeStamp(false, 'FULL').': Обновлено цен - '.$result, 'import_prices');

        return $result;
    }
}

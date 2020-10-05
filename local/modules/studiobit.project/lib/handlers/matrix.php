<?
namespace Studiobit\Project\Handlers;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Studiobit\Base;
use Studiobit\Project as Project;
use Studiobit\Matrix\Entity\Object;
use Studiobit\Matrix\Entity\ObjectStatus;
use Studiobit\Matrix\Entity\SectionTable;

Loc::loadMessages(__FILE__);

class Matrix
{
    /**
     * Событие перед изменением статуса у помещения
     *
     * @param \Bitrix\Main\Event $event
     */
    public static function onBeforeObjectSetStatus(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();

        if($arParams[0] instanceof Object){
            $object = $arParams[0];
            $newStatus = $arParams[1];

            if($newStatus == ObjectStatus::Open){
                if(\Bitrix\Main\Loader::includeModule('crm')) {
                    //ищем сделки, в которых есть этот товар
                    $rsProducts = \CCrmProductRow::GetList(
                        array(),
                        array('OWNER_TYPE' => 'D', '=PRODUCT_ID' => $object->getId()),
                        false,
                        false,
                        array('ID', 'OWNER_ID', 'PRODUCT_ID')
                    );

                    while($arProduct = $rsProducts->Fetch()){
                        $rsDeal = \CCrmDeal::GetListEx(
                            [],
                            ['ID' => $arProduct['OWNER_ID']],
                            false,
                            false,
                            ['ID', 'CLOSED', 'STAGE_ID', 'CATEGORY_ID']
                        );

                        if($arDeal = $rsDeal->Fetch()) {
                            if (
                                $arDeal['CLOSED'] !== 'Y' ||
                                $arDeal['STAGE_ID'] == \CCrmDeal::GetFinalStageID($arDeal['CATEGORY_ID'])) {
                                $event->addResult(new EventResult(EventResult::ERROR));
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Событие изменения статуса у помещения
     * 
     * @param \Bitrix\Main\Event $event
     */
    public static function onObjectSetStatus(\Bitrix\Main\Event $event)
    {
        $arParams = $event->getParameters();
        \p2f('onObjectSetStatus',false);
        if($arParams[0] instanceof Object){
            $object = $arParams[0];
            \p2f($object,false);
            $status = $object->getStatus();
            \p2f($status,false);
            if($status == ObjectStatus::Open)
            {
                $nominalPrice = $object->getNominalPrice();
                \p2f($nominalPrice,false);
                if($nominalPrice['PRICE'] > 0)
                    $object->setPrice($nominalPrice['PRICE']);
            }
            elseif($status == ObjectStatus::Booking){
                $object->prop([
                    'IS_ASSIGMENT_SELL' => false
                ]);
            }
        }
    }
}

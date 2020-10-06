<?php

namespace Studiobit\Project\Controller;

use Studiobit\Base as Base;
use Studiobit\Matrix as Matrix;
use Bitrix\Main\Loader;
use Studiobit\Base\View;

/**
 * Контроллер для объектов
 */

class Object extends Prototype
{
    public function createTradeInAction()
    {
        $dealId = $this->getParam("dealId");
        
        $errors = [];

        $this->view = new View\Json();
        $this->returnAsIs = true;

        if(Loader::includeModule('crm') && Loader::includeModule('iblock') && Loader::includeModule('studiobit.matrix'))
        {
            if($dealId)
            {
                if(\Studiobit\Matrix\Entity\Object::getList([], ['=PROPERTY_DEAL' => $dealId], []) == 0){
                    $arRequiredFields = [
                        'UF_CRM_TD_STREET' => 'Улица',
                        'UF_CRM_TD_HOUSE' => 'Дом',
                        'UF_CRM_TD_PRICE' => 'Ориентировочная стоимость квартиры',
                        'UF_CRM_TD_FLOOR' => 'Этаж',
                        'UF_CRM_TD_FLOORS_CNT' => 'Этажность',
                        'UF_CRM_TD_AREA' => 'Общая площадь',
                        'UF_CRM_TD_COMMENT' => 'Комментарий сотрудника Trade-in'
                    ];
                    
                    $rsDeal = \CCrmDeal::GetListEx([], ['ID' => $dealId], false, false, array_merge(['ID'], array_keys($arRequiredFields)));
                    
                    if($arDeal = $rsDeal->Fetch()){
                        foreach($arRequiredFields as $fieldName => $fieldTitle) {
                            if (empty(trim($arDeal[$fieldName]))) {
                                $errors[] = 'В сделке не заполнено поле "'.$fieldTitle.'"';
                            }
                        }
                        
                        if(empty($errors)){
                            $el = new \CIBlockElement();
                            $elId = $el->Add([
                                'IBLOCK_ID' => Matrix\Entity\Object::getIBlockID(),
                                'IBLOCK_SECTION_ID' => 530, // Разделы - Трейд-ин в деньги - Квартиры - Вторичка
                                'ACTIVE' => 'Y',
                                'NAME' => $arDeal['UF_CRM_TD_STREET'].', '.$arDeal['UF_CRM_TD_HOUSE'],
                                'DETAIL_TEXT' => $arDeal['UF_CRM_TD_COMMENT'],
                            ]);
                            
                            if($elId){
                                \CIBlockElement::SetPropertyValuesEx(
                                    $elId,
                                    Matrix\Entity\Object::getIBlockID(),
                                    [
                                        'TYPE' => 'TRADE_IN_APARTMENT',
                                        'LEVEL' => $arDeal['UF_CRM_TD_FLOOR'],
                                        'COUNT_LEVEL' => $arDeal['UF_CRM_TD_FLOORS_CNT'],
                                        'AREA' => $arDeal['UF_CRM_TD_AREA'],
                                        'DEAL' => $dealId
                                    ]
                                );
                                
                                $object = Matrix\Entity\Object::getObjectByID($elId);
                                if($object){
                                    $object->setStatus(Matrix\Entity\ObjectStatus::Open);
                                    $object->setPrice($arDeal['UF_CRM_TD_PRICE']);
                                    $btn = '<a class="ui-btn ui-btn-primary" href="'.$object->getUrl().'">Карточка объекта</a>';

                                    return ['result' => 'success', 'id' => $object->getId(), 'btn' => $btn];
                                }
                                else{
                                    $errors[] = 'Не удалось создать объект.';
                                }
                            }
                            else{
                                $errors[] = 'Не удалось создать объект: '.$el->LAST_ERROR;
                            }
                        }
                    }
                    else{
                        $errors[] = 'Не удалось найти сделку.';
                    }
                }
                else{
                    $errors[] = 'Для данной сделки уже создан объект ТрейдИн.';
                }
            }
            else{
                $errors[] = 'Не задана сделка.';
            }

        }
        else{
            $errors[] = 'Не удалось подключить необходимые модули.';
        }
        
        return [
            'result' => 'fail', 
            'errors' => $errors, 
            'error' => implode('<br />', $errors)
        ];
    }
}
?>
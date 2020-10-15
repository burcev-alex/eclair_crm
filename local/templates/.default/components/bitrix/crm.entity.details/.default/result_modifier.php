<?php
/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 03.12.2018
 * Time: 0:05
 */

$arResult['REST_USE'] = false;//$GLOBALS['USER']->IsAdmin();
if($arResult['ENTITY_TYPE_NAME'] == 'DEAL'){
    $arResult['EDITOR']['ENTITY_FIELDS'][] = [
        'name' => 'BUTTON_PAYMENT',
        'title' => 'Оплата заказа',
        'type' => 'custom',
        'editable' => false,
        'data' => [
            'view' => 'BUTTON_PAYMENT'
        ]
    ];
}
$phone = [];
if(isset($arResult['EDITOR']['ENTITY_DATA']['CLIENT_INFO']['CONTACT_DATA'])){
    foreach($arResult['EDITOR']['ENTITY_DATA']['CLIENT_INFO']['CONTACT_DATA'] as $contactData){
        if(isset($contactData['advancedInfo']['multiFields'])){
            foreach($contactData['advancedInfo']['multiFields'] as $field){
                if($field['TYPE_ID'] == 'PHONE'){
                    $phone = $field;
                }
            }
        }
    }
}
$arFieldPaymentBtn = $APPLICATION->IncludeComponent(
    'studiobit.project:field.deal.payment',
    '',
    [
        'ENTITY_TYPE' => 'DEAL',
        'ENTITY_ID' => $arResult['EDITOR']['ENTITY_DATA']['ID'],
        'MODE' => 'BUFFER',
        'PHONE' => $phone,
        'PRICE' =>  $arResult['EDITOR']['ENTITY_DATA']['OPPORTUNITY'],
        'PAYMENT' =>  $arResult['EDITOR']['ENTITY_DATA']['UF_PAYMENT']
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
$arResult['EDITOR']['ENTITY_DATA']['BUTTON_PAYMENT'] = $arFieldPaymentBtn['HTML'];

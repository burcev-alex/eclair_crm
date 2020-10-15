<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\Loader::includeModule('studiobit.base');
\Bitrix\Main\Loader::includeModule('studiobit.project');

use Studiobit\Project;

global $APPLICATION, $USER;

$deletedTabs = ['tab_quote', 'tab_invoice', 'tab_tree', 'tab_portrait'];

$bAdmin = $GLOBALS['USER']->IsAdmin();

if(!$bAdmin){
    $deletedTabs[] = 'tab_bizproc';
}



foreach($arResult['TABS'] as $key => $arTab)
{
    if(in_array($arTab['id'], $deletedTabs))
    {
        unset($arResult['TABS'][$key]);
    }
}



if(!$arResult['ENTITY_ID']) {
    $arResult['ENTITY_DATA']['FORMATTED_NAME'] = 'Создание контакта';

    ob_start();
    $APPLICATION->IncludeComponent(
        'studiobit.project:contact.search',
        '',
        [
            'GUID' => $arResult['GUID']
        ],
        false
    );
    $arResult['SEARCH'] = ob_get_clean();
}

// foreach($arResult['ENTITY_FIELDS'] as $key => &$arField)
// {
    // if($arField['name'] == 'UF_CRM_CHANNEL') //канал - источник
    // {
        // $arField['type'] = 'custom';
        // $arField['data']['view'] = 'UF_CRM_CHANNEL_VIEW';
        // $arField['data']['edit'] = 'UF_CRM_CHANNEL_EDIT';

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.contact.channel',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'MODE' => 'VIEW'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_CHANNEL_VIEW'] = ob_get_clean();

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.contact.channel',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'MODE' => 'EDIT'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_CHANNEL_EDIT'] = ob_get_clean();
    // }
    // elseif($arField['name'] == 'UF_CRM_STATUS') //статус
    // {
        // $arField['type'] = 'custom';
        // $arField['data']['view'] = 'UF_CRM_STATUS_VIEW';
        // $arField['data']['edit'] = 'UF_CRM_STATUS_EDIT';

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.contact.status',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'MODE' => 'VIEW'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_STATUS_VIEW'] = ob_get_clean();

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.contact.status',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'MODE' => 'EDIT'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_STATUS_EDIT'] = ob_get_clean();
    // }
    // elseif($arField['name'] == 'ASSIGNED_BY_ID') //ответственный
    // {
        // if($arResult['ENTITY_DATA']['TYPE_ID'] == 'PARTNER'){
            // if(!$bAdmin && !$bContoller) {
                // $arField['editable'] = false;
            // }
        // }
    // }
    // elseif($arField['name'] == 'UF_CRM_TD_USER') //канал - источник
    // {
        // $arField['type'] = 'custom';
        // $arField['data']['view'] = 'UF_CRM_TD_USER_VIEW';
        // $arField['data']['edit'] = 'UF_CRM_TD_USER_EDIT';

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.user',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'FIELD_NAME' => 'UF_CRM_TD_USER',
                // 'ROLE' => 'Трейд ин',
                // 'MODE' => 'VIEW'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_TD_USER_VIEW'] = ob_get_clean();

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.user',
            // '',
            // [
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'FIELD_NAME' => 'UF_CRM_TD_USER',
                // 'ROLE' => 'Трейд ин',
                // 'MODE' => 'EDIT'
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_CRM_TD_USER_EDIT'] = ob_get_clean();
    // }
    // elseif($arField['name'] == 'UF_COMMENT_BINGO') //Комментарий Бинго
    // {
        // $arField['type'] = 'custom';
        // $arField['data'] = array(
            // 'view' => 'UF_COMMENT_BINGO_VIEW_HTML',
            // 'edit' => 'UF_COMMENT_BINGO_EDIT_HTML',
        // );

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.comment.table',
            // '',
            // [
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'FIELD_NAME' => 'UF_COMMENT_BINGO',
                // 'MODE' => 'EDIT',
                // 'RESPONSIBLE' => [
                    // $arResult['ENTITY_DATA']['ASSIGNED_BY_ID'],
                    // $arResult['ENTITY_DATA']['CREATED_BY_ID']
                // ]
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_COMMENT_BINGO_EDIT_HTML'] = ob_get_clean();

        // ob_start();
        // $APPLICATION->IncludeComponent(
            // 'studiobit.project:field.comment.table',
            // '',
            // [
                // 'ENTITY_TYPE' => 'CONTACT',
                // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
                // 'FIELD_NAME' => 'UF_COMMENT_BINGO',
                // 'MODE' => 'SHOW',
                // 'RESPONSIBLE' => []
            // ],
            // false,
            // ['HIDE_ICONS' => 'Y']
        // );
        // $arResult['ENTITY_DATA']['UF_COMMENT_BINGO_VIEW_HTML'] = ob_get_clean();
    // }
// }
// unset($arField);

// $arResult['ENTITY_FIELDS'][] = [
    // 'name' => 'ORIGIN_ID',
    // 'title' => 'Внешний код',
    // 'type' => 'text'
// ];

// /*добавляем свойство Сделки*/
// $arResult['ENTITY_FIELDS'][] = [
    // 'name' => 'DEALS',
    // 'title' => 'Сделки',
    // 'type' => 'custom',
    // 'editable' => false,
    // 'data' => [
        // 'view' => 'DEALS'
    // ]
// ];

ob_start();
$APPLICATION->IncludeComponent(
    'studiobit.project:field.contact.deals',
    '',
    [
        'ENTITY_TYPE' => 'CONTACT',
        'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
$arResult['ENTITY_DATA']['DEALS'] = ob_get_clean();

//статусы контакта
// ob_start();
// $APPLICATION->IncludeComponent(
    // 'studiobit.project:field.contact.status',
    // 'progressbar',
    // [
        // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
        // 'ENTITY_TYPE' => 'CONTACT',
        // 'MODE' => 'EDIT'
    // ],
    // false,
    // ['HIDE_ICONS' => 'Y']
// );
// $arResult['PROGRESS_BAR_HTML'] = ob_get_clean();

/*добавляем свойство Задача на оценку*/
// $arResult['ENTITY_FIELDS'][] = [
    // 'name' => 'BUTTON_TRADE_IN',
    // 'title' => 'Задача на оценку',
    // 'type' => 'custom',
    // 'editable' => false,
    // 'data' => [
        // 'view' => 'BUTTON_TRADE_IN'
    // ]
// ];

// $arFieldTradeInBtn = $APPLICATION->IncludeComponent(
    // 'studiobit.project:field.button.tradein_task',
    // '',
    // [
        // 'ENTITY_TYPE' => 'CONTACT',
        // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
        // 'MODE' => 'BUFFER'
    // ],
    // false,
    // ['HIDE_ICONS' => 'Y']
// );
// $arResult['ENTITY_DATA']['BUTTON_TRADE_IN'] = $arFieldTradeInBtn['HTML'];

/*добавляем свойство Создать Объект*/
// $arResult['ENTITY_FIELDS'][] = [
    // 'name' => 'ADD_OBJECT_TRADE_IN',
    // 'title' => 'Кнопка "Создать Объект ТрейдИн"',
    // 'type' => 'custom',
    // 'editable' => false,
    // 'data' => [
        // 'view' => 'ADD_OBJECT_TRADE_IN'
    // ]
// ];

// ob_start();
// $APPLICATION->IncludeComponent(
    // 'studiobit.project:field.button.create_tradein_product',
    // '',
    // [
        // 'ENTITY_TYPE' => 'CONTACT',
        // 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
    // ],
    // false,
    // ['HIDE_ICONS' => 'Y']
// );
// $arResult['ENTITY_DATA']['ADD_OBJECT_TRADE_IN'] = ob_get_clean();

/*добавляем свойство Отмена моратория*/
// $arResult['ENTITY_FIELDS'][] = [
	// 'name' => 'CANCEL_MORATORIUM',
	// 'title' => 'Отмена моратория',
	// 'type' => 'custom',
	// 'editable' => false,
	// 'data' => [
		// 'view' => 'CANCEL_MORATORIUM'
	// ]
// ];

// ob_start();
// $APPLICATION->IncludeComponent(
	// 'studiobit.project:field.button.cancel_moratorium_task',
	// '',
	// [
		// 'ENTITY_TYPE' => 'CONTACT',
		// 'ENTITY_ID' => $arResult['ENTITY_DATA']['ID'],
		// 'COUNT_DAY_LIMIT' => 3
	// ],
	// false,
	// ['HIDE_ICONS' => 'Y']
// );
// $arResult['ENTITY_DATA']['CANCEL_MORATORIUM'] = ob_get_clean();

//права для полей
// \Studiobit\Base\Entity\FormFieldPermsTable::prepareCrmFields($arResult['EDITOR_CONFIG_ID'], $arResult['ENTITY_FIELDS'], $arResult['ENTITY_ID'] == 0);
// $arResult['FIELDS_STYLE'] = \Studiobit\Base\Entity\FormFieldPermsTable::getCrmStyle($arResult['EDITOR_CONFIG_ID'], $arResult['ENTITY_FIELDS'], $arResult['ENTITY_ID'] == 0);

// if($arResult['ENTITY_DATA']['TYPE_ID'] == 'PARTNER'){
    // Если тип контакта = Риелтор, то во вкладке Сделки показываем сделки его клиентов
    // foreach($arResult['TABS'] as &$arTab){
        // if($arTab['id'] == 'tab_deal'){
            // $arTab['loader']['componentData']['params']['ADDITIONAL_FILTER'] = [
                // 'UF_CRM_REALTOR' => $arResult['ENTITY_DATA']['ID'],
                // 'ASSOCIATED_CONTACT_ID' => ''
            // ];
        // }
    // }
    // unset($arTab);

    // foreach($arResult['ENTITY_FIELDS'] as $key => &$arField) {
        // if ($arField['name'] == 'UF_REG_IN_BINGO') //Зарегистрирован в Бинго
        // {
            // показываем поле, если карточка контакта с типом Риелтор
            // $arResult['FIELDS_STYLE'] .= 'div[data-cid="' . $arField['name'] . '"]{display:block !important;}';
        // }
    // }
// }
// else{
    // foreach($arResult['ENTITY_FIELDS'] as $key => &$arField) {
        // if ($arField['name'] == 'UF_REG_IN_BINGO') //Зарегистрирован в Бинго
        // {
            // скрываем поле, если карточка контакта с типом Клиент
            // $arResult['FIELDS_STYLE'] .= 'div[data-cid="' . $arField['name'] . '"]{display:none !important;}';
        // }
    // }
// }
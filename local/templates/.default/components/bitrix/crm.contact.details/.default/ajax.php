<?php
/*
 * кастомизация ajax-обработчика компонента редактирования контакт
 * основная логика остается в файле /bitrix/components/bitrix/crm.contact.details/ajax.php и ее мы не трогаем
 * вмешиваемся уже на этапе отдачи ответа и добавляем свои данные
*/

/*

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
    return;
}

if(!function_exists('__CrmContactDetailsEndJsonResonse'))
{
    function __CrmContactDetailsEndJsonResonse($result)
    {
        if(isset($result['ENTITY_DATA'])) {
            //поле канал - источник
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.contact.channel',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'MODE' => 'VIEW'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_CHANNEL_VIEW'] = ob_get_clean();

            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.contact.channel',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'MODE' => 'EDIT'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_CHANNEL_EDIT'] = ob_get_clean();

            //поле канал - статус
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.contact.status',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'MODE' => 'VIEW'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_STATUS_VIEW'] = ob_get_clean();

            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.contact.status',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'MODE' => 'EDIT'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_STATUS_EDIT'] = ob_get_clean();

            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.contact.deals',
                '',
                [
                    'ENTITY_TYPE' => 'CONTACT',
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['DEALS'] = ob_get_clean();

            //поле Специалист по трейдин
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.user',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'FIELD_NAME' => 'UF_CRM_TD_USER',
                    'ROLE' => 'Трейд ин',
                    'MODE' => 'VIEW'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_TD_USER_VIEW'] = ob_get_clean();

            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.user',
                '',
                [
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'ENTITY_TYPE' => 'CONTACT',
                    'FIELD_NAME' => 'UF_CRM_TD_USER',
                    'ROLE' => 'Трейд ин',
                    'MODE' => 'EDIT'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['UF_CRM_TD_USER_EDIT'] = ob_get_clean();

            //добавляем свойство Задача на оценку
            $arFieldTradeInBtn = $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.button.tradein_task',
                '',
                [
                    'ENTITY_TYPE' => 'CONTACT',
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                    'MODE' => 'BUFFER'
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['BUTTON_TRADE_IN'] = $arFieldTradeInBtn['HTML'];

            //добавляем свойство Создать Объект
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.button.create_tradein_product',
                '',
                [
                    'ENTITY_TYPE' => 'CONTACT',
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['ADD_OBJECT_TRADE_IN'] = ob_get_clean();

            //добавляем свойство Отмена моратория
            ob_start();
            $GLOBALS['APPLICATION']->IncludeComponent(
                'studiobit.project:field.button.cancel_moratorium_task',
                '',
                [
                    'ENTITY_TYPE' => 'CONTACT',
                    'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
	                'COUNT_DAY_LIMIT' => 3
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $result['ENTITY_DATA']['CANCEL_MORATORIUM'] = ob_get_clean();

	        // комментарий БИНГО
	        ob_start();
	        $GLOBALS['APPLICATION']->IncludeComponent(
		        'studiobit.project:field.comment.table',
		        '',
		        [
			        'ENTITY_TYPE' => 'CONTACT',
			        'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
			        'FIELD_NAME' => 'UF_COMMENT_BINGO',
			        'MODE' => 'SHOW',
			        'RESPONSIBLE' => []
		        ],
		        false,
		        ['HIDE_ICONS' => 'Y']
	        );
	        $result['ENTITY_DATA']['UF_COMMENT_BINGO_VIEW_HTML'] = ob_get_clean();

	        ob_start();
	        $GLOBALS['APPLICATION']->IncludeComponent(
		        'studiobit.project:field.comment.table',
		        '',
		        [
			        'ENTITY_TYPE' => 'CONTACT',
			        'ENTITY_ID' => $result['ENTITY_DATA']['ID'],
			        'FIELD_NAME' => 'UF_COMMENT_BINGO',
			        'MODE' => 'EDIT',
			        'RESPONSIBLE' => [
				        $result['ENTITY_DATA']['ASSIGNED_BY_ID'],
				        $result['ENTITY_DATA']['CREATED_BY_ID']
			        ]
		        ],
		        false,
		        ['HIDE_ICONS' => 'Y']
	        );
	        $result['ENTITY_DATA']['UF_COMMENT_BINGO_EDIT_HTML'] = ob_get_clean();
        }

        $GLOBALS['APPLICATION']->RestartBuffer();
        header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
        if(!empty($result))
        {
            echo CUtil::PhpToJSObject($result);
        }
        if(!defined('PUBLIC_AJAX_MODE'))
        {
            define('PUBLIC_AJAX_MODE', true);
        }
        require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
        die();
    }
}
*/

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact.details/ajax.php');

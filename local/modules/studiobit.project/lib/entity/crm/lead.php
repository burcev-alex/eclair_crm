<?php
namespace Studiobit\Project\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Base\Tools;
use Studiobit\Project as Project;
use Studiobit\Matrix\Entity\Object;

Main\Loader::includeModule('crm');

class LeadTable extends Crm\LeadTable
{
    /**
     * @return array
     */
    public static function getMap()
    {
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER;

        $map = parent::getMap();

        $userFields = $USER_FIELD_MANAGER->GetUserFields(static::getUFId());
        foreach ($userFields as $field) {
            if ($field['USER_TYPE_ID'] === 'enumeration') {
                $name = 'ENUM_' . substr($field['FIELD_NAME'], 3);
                $map[] = new Entity\ReferenceField($name,
                    '\Studiobit\Base\Entity\UserFieldEnumTable',
                    [
                        '=this.' . $field['FIELD_NAME'] => 'ref.ID',
                        'ref.USER_FIELD_ID' => new DB\SqlExpression('?i', $field['ID']),
                    ],
                    ['join_type' => 'left']
                );
            }
        }
        
        $map['CONTACT'] = new Entity\ReferenceField(
            'CONTACT',
            '\Studiobit\Project\Entity\Crm\ContactTable',
            [
                '=this.CONTACT_ID' => 'ref.ID'
            ]
        );

        $map['COMPANY'] = new Entity\ReferenceField(
            'CONTACT',
            '\Studiobit\Project\Entity\Crm\CompanyTable',
            [
                '=this.COMPANY_ID' => 'ref.ID'
            ]
        );

        return $map;
    }

    public static function processAdd($id){
        $key_log = 'lead_'.(\ConvertTimeStamp());
        \p2log('-----------processAdd---------------', $key_log);
        \p2log('user_id=', $GLOBALS['USER']->GetID(), $key_log);

        $rsLead = self::getList([
            'filter' => ['ID' => $id, '!STATUS_ID' => 'CONVERTED'],
            'select' => [
                '*',
                'UF_*',
                'PHONE',
                'ASSIGNED_BY_ID',
                'CONTACT_ID', 
                'CONTACT_STATUS' => 'CONTACT.ENUM_CRM_STATUS.XML_ID',
                'CONTACT_CHANNEL' => 'CONTACT.CHANNEL.NAME',
                'CONTACT_ASSIGNED_BY_ID' => 'CONTACT.ASSIGNED_BY_ID',
                'CONTACT_CRM_ROISTAT' => 'CONTACT.UF_CRM_ROISTAT',
                'CONTACT_ROISTAT_TITLE' => 'CONTACT.UF_ROISTAT_TITLE'
            ]
        ]);

        if($arLead = $rsLead->fetch()) 
        {
            \p2log($arLead, $key_log);

            if(!$arLead['CONTACT_ID'] && !empty($arLead['PHONE'])) {
                $arContact = ContactTable::getByPhone(
                    $arLead['PHONE'],
                    [
                        'CONTACT_ID' => 'ID',
                        'CONTACT_STATUS' => 'ENUM_CRM_STATUS.XML_ID',
                        'CONTACT_CHANNEL' => 'CHANNEL.NAME',
                        'CONTACT_ASSIGNED_BY_ID' => 'ASSIGNED_BY_ID',
                        'CONTACT_CRM_ROISTAT' => 'UF_CRM_ROISTAT',
                        'CONTACT_ROISTAT_TITLE' => 'UF_ROISTAT_TITLE'
                    ]
                );

                if(is_array($arContact)){
                    $arLead = array_merge($arLead, $arContact);
                }
            }

            \p2log($arLead, $key_log);

            $obContact = new \CCrmContact(false);
            
            //если уже найден контакт в базе
            if($arLead['CONTACT_ID'] > 0) {
                $fields = []; //поля контакта
                //Если статус контакта один из проигранных т.е. «Снята Бронь», «Прозвон Баз», «Не выходит на связь»,
                // «Отложили покупку», «Купили в другом месте», «Нецелевой», и при этом “канал” не Агентство недвижимости
                if (
                    in_array(
                        $arLead['CONTACT_STATUS'], 
                        ['BOOKING_ABORT', 'CALL_BASE', 'OFFLINE', 'HOLD', 'SELL_OTHERS', 'NO_TARGET']
                    ) && $arLead['CONTACT_CHANNEL'] !== 'Агентство недвижимости'
                ) {
                    // то меняем статус на – «В работе» и устанавливаем “канал” - Интернет, “источник” - страница захвата.
                    $fields['UF_CRM_STATUS'] = Tools::getIDInUFPropEnumByXml(
                        'UF_CRM_STATUS',
                        'WORK',
                        0,
                        'CRM_CONTACT'
                    );

                    if($channel = ContactTable::getChannelByName('Интернет'))
                        $fields['UF_CRM_CHANNEL'] = $channel;

                    if($source = ContactTable::getSourceByName('Страница захвата'))
                        $fields['UF_CRM_SOURCE'] = $source;
                }

                if(empty($arContact['CONTACT_CRM_ROISTAT'])) {
                    $fields['UF_CRM_ROISTAT'] = $arLead['UF_CRM_5C80612AA1'];
                }

                if(empty($arContact['CONTACT_CRM_PAGE'])) {
                    //Берем «комментарий roistat» из лида и переносим значение в поле «комментарий roistat» у карточки контакта.
                    $fields['UF_CRM_PAGE'] = $arLead['COMMENTS'];
                }

                if(empty($arContact['CONTACT_ROISTAT_TITLE'])) {
                    //Бназвание лида в свойство в свойство "Заголовок роистат"
                    $fields['UF_ROISTAT_TITLE'] = $arLead['TITLE'];
                }

                \p2log('update contact', $key_log);

                \p2log($fields, $key_log);

                $obContact->Update($arLead['CONTACT_ID'], $fields);
                
                //Берем «комментарий roistat» из лида и добавляем содержимое в ленту взаимодействия с клиентом в виде комментария (в карточке контакта).
                if(strlen($arLead['COMMENTS'])) {
                    Tools::addTimelineComment(
                        \CCrmOwnerType::Contact,
                        $arLead['CONTACT_ID'],
                        $arLead['COMMENTS'],
                        $arLead['CONTACT_ASSIGNED_BY_ID']
                    );
                }

                //Создаем в карточке контакта дело «Звонок»

                /** @var \Bitrix\Crm\Activity\Provider\Base $provider */
                $provider = \Bitrix\Crm\Activity\Provider\Call::className();

                $datetime = new \DateTime();
                $datetime->setTimestamp(time() + \CTimeZone::GetOffset());

                if($datetime->format('H') >= 23){
                    $datetime->add(new \DateInterval('P1D'));
                    $datetime->setTime(9, 0);
                }
                elseif($datetime->format('H') < 9){
                    $datetime->setTime(9, 0);
                }

                $activityFields = array(
                    'AUTHOR_ID' => $arLead['CONTACT_ASSIGNED_BY_ID'],
                    'START_TIME' => \ConvertTimeStamp($datetime->getTimestamp(), 'FULL'),
                    'END_TIME' => \ConvertTimeStamp($datetime->getTimestamp() + 30*60, 'FULL'),
                    'TYPE_ID' =>  \CCrmActivityType::Call,
                    'SUBJECT' => 'Заявка из интернета',
                    'PRIORITY' => \CCrmActivityPriority::Medium,
                    'PROVIDER_ID' => $provider::getId(),
                    'PROVIDER_TYPE_ID' => $provider::getTypeId(array()),
                    'DIRECTION' => \CCrmActivityDirection::Outgoing,
                    'RESPONSIBLE_ID' => $arLead['CONTACT_ASSIGNED_BY_ID'],
                    'AUTOCOMPLETE_RULE' => \Bitrix\Crm\Activity\AutocompleteRule::NONE
                );

                $provider::fillDefaultActivityFields($activityFields);

                $defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array(), $arLead['CONTACT_ASSIGNED_BY_ID']);
                if (isset($defaults['notify']) && isset($defaults['notify'][$provider::getId()]))
                {
                    $activityFields['NOTIFY_VALUE'] = (int)$defaults['notify'][$provider::getId()]['value'];
                    $activityFields['NOTIFY_TYPE'] = (int)$defaults['notify'][$provider::getId()]['type'];
                }

                $activityFields['COMMUNICATIONS'] = self::getCommunicationsFromFM(\CCrmOwnerType::Contact, $arLead['CONTACT_ID']);
                $activityFields['BINDINGS'] =[['OWNER_TYPE_ID' => \CCrmOwnerType::Contact, 'OWNER_ID' => $arLead['CONTACT_ID']]];

                if($callId = \CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true)))
                {
                    \Bitrix\Crm\Automation\Factory::registerActivity($callId);
                }
            }
            else {
                //если нет
                //Берем «комментарий roistat» из сделки и переносим значение в поле «комментарий roistat» у карточки контакта.
                //Ставим в поле «ответственный» в карточке контакта группу – Менеджеры.

                $fields = [
                    'ASSIGNED_BY_ID' => $arLead['ASSIGNED_BY_ID'], //Project\MANAGERS,
                    'NAME' => $arLead['NAME'],
                    'LAST_NAME' => $arLead['LAST_NAME'],
                    'SECOND_NAME' => $arLead['SECOND_NAME'],
                    'FULL_NAME' => implode(' ', [$arLead['LAST_NAME'], $arLead['NAME'], $arLead['SECOND_NAME']]),
                    'UF_CRM_ROISTAT' => $arLead['UF_CRM_5C80612AA1'],
                    'UF_CRM_PAGE' => $arLead['COMMENTS'],
                    'UF_ROISTAT_TITLE' => $arLead['TITLE'],
                    'TYPE_ID' => 'CLIENT',
                    'OPENED' => 'Y',
                    'LEAD_ID' => $arLead['ID'],
                    'FM' => []
                ];
                \p2log($arLead, $key_log);
                \p2log($fields, $key_log);

                if(empty($fields['NAME']) && empty($fields['LAST_NAME']))
                    $fields['NAME'] = $arLead['TITLE'];

                $rsFm = \CCrmFieldMulti::GetListEx([], ['ENTITY_ID' => \CCrmOwnerType::LeadName, 'ELEMENT_ID' => $arLead['ID']]);
                $key = 0;
                while($arFm = $rsFm->Fetch()){
                    $fields['FM'][$arFm['TYPE_ID']]['n'.$key] = [
                        'VALUE_TYPE' => $arFm['VALUE_TYPE'],
                        'VALUE' => $arFm['VALUE'],
                    ];

                    $key++;
                }

                //Статус контакта = «Не обработан»
                $fields['UF_CRM_STATUS'] = Tools::getIDInUFPropEnumByXml(
                    'UF_CRM_STATUS',
                    'NEW',
                    0,
                    'CRM_CONTACT'
                );

                if($channel = ContactTable::getChannelByName('Самостоятельно'))
                    $fields['UF_CRM_CHANNEL'] = $channel;

                if($source = ContactTable::getSourceByName('Входящий поток'))
                    $fields['UF_CRM_SOURCE'] = $source;

                if($arLead['CONTACT_ID'] = $obContact->Add($fields))
                {
                    \p2log($arLead, $key_log);
                    if(\Bitrix\Main\Loader::includeModule('bizproc')) {
                        \p2log('bizproc', $key_log);

                        //Определяем канал и источник (используем для этого бизнес процесс, который реализован в текущей системе)
                        \CCrmBizProcHelper::AutoStartWorkflows(
                            \CCrmOwnerType::Contact,
                            $arLead['CONTACT_ID'],
                            \CCrmBizProcEventType::Create,
                            $errors = []
                        );

                        \p2log($errors, $key_log);
                    }

                    if(strlen($arLead['COMMENTS'])) {
                        //Берем «комментарий roistat» из сделки и добавляем содержимое в ленту взаимодействия с клиентом в виде комментария (в карточке контакта).
                        Tools::addTimelineComment(
                            \CCrmOwnerType::Contact,
                            $arLead['CONTACT_ID'],
                            $arLead['COMMENTS'],
                            $arLead['ASSIGNED_BY_ID']
                        );
                    }
                }
                else{
                    \p2log($obContact->LAST_ERROR, $key_log);
                }
            }

            if($arLead['CONTACT_ID'])
            {
                self::update($arLead['ID'], ['CONTACT_ID' => $arLead['CONTACT_ID']]);
            }
        }

        \p2log('---------------------------', $key_log);
    }

    private static function getCommunicationsFromFM($entityTypeId, $entityId)
    {
        $entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
        $communications = array();

        $iterator = \CCrmFieldMulti::GetList(
            array('ID' => 'asc'),
            array('ENTITY_ID' => $entityTypeName,
                  'ELEMENT_ID' => $entityId,
                  'TYPE_ID' => 'PHONE'
            )
        );

        while ($row = $iterator->Fetch())
        {
            if (empty($row['VALUE']))
                continue;

            $communications[] = array(
                'ENTITY_ID' => $entityId,
                'ENTITY_TYPE_ID' => $entityTypeId,
                'ENTITY_TYPE' => $entityTypeName,
                'TYPE' => 'PHONE',
                'VALUE' => $row['VALUE'],
                'VALUE_TYPE' => $row['VALUE_TYPE']
            );
        }

        return $communications;
    }

    public static function updateStatus($contactId)
    {
        $rsContact = ContactTable::getList([
            'filter' => ['ID' => $contactId],
            'select' => ['ID', 'STATUS_CODE' => 'ENUM_CRM_STATUS.VALUE', 'LEAD_ID']
        ]);

        if($arContact = $rsContact->fetch()){
            $statusId = 0;

            foreach(\CCrmLead::GetStatusNames() as $id => $name){
                if(strtolower($name) == strtolower($arContact['STATUS_CODE'])){
                    $statusId = $id;
                }
            }

            if($statusId) {
                $obLead = new \CCrmLead(false);
                $filter = ['CONTACT_ID' => $arContact['ID']];
                if ($arContact['LEAD_ID'] > 0) {
                    $filter = [
                        'LOGIC' => 'OR',
                        $filter,
                        ['=ID' => $arContact['LEAD_ID']]
                    ];
                }
                $rsLead = self::getList([
                    'filter' => $filter,
                    'select' => ['ID']
                ]);

                while ($arLead = $rsLead->fetch()) {
                    $obLead->Update($arLead['ID'], $arFields = ['STATUS_ID' => $statusId]);
                }
            }
        }
    }
}
?>
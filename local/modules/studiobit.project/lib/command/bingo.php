<?php

namespace Studiobit\Project\Command;

use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Studiobit\Base\Tools;
use const Studiobit\Project\CALL_CENTER_DEPARTMENT;
use Studiobit\Project\Entity\Crm\ContactTable;
use Studiobit\Project\Entity\Crm\DealTable;
use Studiobit\Project\Entity\KeysTable;
use Bitrix\Main\Entity;
use Studiobit\Project as Project;
use const Studiobit\Project\CALL_CENTER;
use const Studiobit\Project\MANAGERS;

/**
 * команды для Бинго
 */

class Bingo
{
    /*
    Если принято уведомление из БИНГО (создан новый контакт, либо в уже созданном контакте канал установился «Агентство недвижимости»),
    прошло два часа, и нет активити типа «Звонок» ИЛИ нет запланированного дела типа «Звонок»,
    отправляем письмо на почту taxi@gk-strizhi.ru с темой «Администратору: нет звонка или запланированных дел по новому уведомлению».
    В теле письма пишем «По уведомлению <ссылка>, полученному 2 часа назад, нет звонка клиенту».
    */
    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function checkNotifyWithoutActivity()
    {
        $start = new \DateTime(); //с какого времени будет работать скрипт
        $start->setDate(2019, 4, 19)->setTime(0, 0, 0);

        $datetime = new \DateTime();
        $datetime->setTimestamp(time() - 2*3600);

        $rs = ContactTable::getList([
            'filter' => [
                '!=TYPE_ID' => 'PARTNER',
                '<=UF_BINGO_DATE_SET' => DateTime::createFromPhp($datetime),
                '>=UF_BINGO_DATE_SET' => DateTime::createFromPhp($start),
                'UF_CRM_CHANNEL' => ContactTable::getChannelByName('Агентство недвижимости'),
                '=STATUS.UF_GROUP' => 'WORK',
                '!ASSIGNED_BY_ID' => CALL_CENTER,
                'SEND.ID' => [0, false]
            ],
            'runtime' => [
                new Entity\ReferenceField(
                    'CALL',
                    '\Bitrix\Crm\ActivityTable',
                    [
                        '=this.ASSIGNED_BY_ID' => 'ref.RESPONSIBLE_ID',
                        '=ref.TYPE_ID' => ['?', \CCrmActivityType::Call],
                        '=this.ID' => 'ref.OWNER_ID',
                        '=ref.OWNER_TYPE_ID' => ['?', \CCrmOwnerType::Contact]
                    ]
                ),
                new Entity\ExpressionField(
                    'HAVE_CALL',
                    'CASE WHEN (%s = \'N\' OR %s>=%s) THEN 1 ELSE 0 END',
                    ['CALL.COMPLETED', 'CALL.START_TIME', 'UF_BINGO_DATE_SET']
                ),
                new Entity\ExpressionField(
                    'BINGO_DATE_SET_TIMESTAMP',
                    "UNIX_TIMESTAMP(%s)",
                    ['UF_BINGO_DATE_SET']
                ),
                new Entity\ExpressionField(
                    'KEY',
                    "CONCAT('CONTACT_', %s, '_CHECK_BINGO_NOTIFY')",
                    ['ID']
                ),
                new Entity\ReferenceField(
                    'SEND',
                    KeysTable::getEntity(),
                    [
                        '=this.BINGO_DATE_SET_TIMESTAMP' => 'ref.UF_VALUE',
                        '=this.KEY' => 'ref.UF_KEY',
                    ]
                ),
            ],
            'select' => ['ID', 'FULL_NAME', 'HAVE_CALL', 'UF_BINGO_DATE_SET', 'KEY', 'BINGO_DATE_SET_TIMESTAMP']
        ]);

        $contacts = [];
        while($ar = $rs->fetch()){
            if(!isset($contacts[$ar['ID']])){
                $contacts[$ar['ID']] = $ar;
            }

            if($ar['HAVE_CALL']){
                $contacts[$ar['ID']]['HAVE_CALL'] = 1;
            }
        }

        foreach($contacts as $arContact){
            if(!$arContact['HAVE_CALL']){
                $url = 'https://crm.gk-strizhi.ru'.ContactTable::getUrl($arContact['ID']);
                $eventFields = [
                    'EMAIL_TO' => 'taxi@gk-strizhi.ru',
                    'SUBJECT' => 'Администратору: нет звонка или запланированных дел по новому уведомлению',
                    'MESSAGE' => '<p>По <a href="'.$url.'">уведомлению</a>, полученному 2 часа назад, нет звонка клиенту<p>'
                ];

                \CEvent::Send(
                    'MESSAGE',
                    SITE_ID !== LANGUAGE_ID ? SITE_ID : 's1',
                    $eventFields
                );

                //запоминаем факт отправки письма для данного уведомления
                KeysTable::set($arContact['KEY'], $arContact['BINGO_DATE_SET_TIMESTAMP']);
            }
        }
    }

    /*
    Раз в неделю (по понедельникам) запускаем проверку по контактам типа «риэлтор», у которых ответственный не равен «Менеджеры»,
    не равен «Колл-центр» и не равен сотруднику из подразделения «Колл-центр» и не равен admin.
    Проверяем, сколько времени прошло с момента закрепления за риэлтором текущего ответственного.
    Если прошло более 3 месяцев, то проверяем сделки с не проигрышными статусами, где поле «риэлтор» = текущий риэлтор.
    Смотрим, когда была заведена последняя.
    Если более, чем три месяца назад, то отправляем письмо на почту taxi@gk-strizhi.ru с темой «Администратору:
    нет сделок с риэлтором в течение 3 месяцев». В теле письма пишем «По риэлтору <ссылка> нет сделок в течение 3 месяцев».
    */
    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function checkRealtorsWithoutDeals()
    {
        //проверям по понедельникам
        if(intval(date("w")) !== 1){
            return;
        }

        $assigneds = [MANAGERS, CALL_CENTER];

        foreach(Tools::getUsersByGroupCode('ADMIN') as $id){
            $assigneds[] = $id;
        }
        foreach(Tools::getUsersByDepartment(CALL_CENTER_DEPARTMENT) as $id){
            $assigneds[] = $id;
        }

        $datetime = new \DateTime();
        $datetime->sub(new \DateInterval('P3M'));

        $rs = ContactTable::getList([
            'filter' => [
                'TYPE_ID' => 'PARTNER', //отбираем только риелторов
                '<=UF_ASSIGNED_DATE' => DateTime::createFromPhp($datetime), //ответственный не менялся 3 месяца
                '!UF_ASSIGNED_DATE' => false,
                '=STATUS.UF_GROUP' => 'WORK', //риелтор в работе
                '!ASSIGNED_BY_ID' => $assigneds, //не учитываем опред. пользователей (см. описание метода) и админов
            ],
            'runtime' => [
                new Entity\ExpressionField(
                    'KEY',
                    "CONCAT('CONTACT_', %s, '_CHECK_REALTOR_WITHOUT_DEALS')",
                    ['ID']
                )
            ],
            'select' => ['ID', 'FULL_NAME', 'KEY']
        ]);

        $realtors = [];

        while($arContact = $rs->fetch()){
            $rsDeal = DealTable::getList([
                'order' => ['DATE_CREATE' => 'DESC'],
                'filter' => [
                    '=UF_CRM_REALTOR' => $arContact['ID'],
                    'IS_LOSE' => 0, //смотрим непроигранныее сделки
                    '<=DATE_CREATE' => DateTime::createFromPhp($datetime), //сделка заведена более 3 месяцев назад
                    'SEND.ID' => [0, false] //уведомление еще не отправляли
                ],
                'runtime' => [
                    new Entity\ExpressionField(
                        'DEAL_DATE_CREATE_TIMESTAMP',
                        "UNIX_TIMESTAMP(%s)",
                        'DATE_CREATE'
                    ),
                    new Entity\ReferenceField(
                        'SEND',
                        KeysTable::getEntity(),
                        [
                            '=this.DEAL_DATE_CREATE_TIMESTAMP' => 'ref.UF_VALUE',
                            '=ref.UF_KEY' => ['?', $arContact['KEY']],
                        ]
                    )
                ],
                'select' => ['ID', 'DATE_CREATE', 'DEAL_DATE_CREATE_TIMESTAMP'],
                'limit' => 1

            ]);

            if($arDeal = $rsDeal->fetch()){
                $url = 'https://crm.gk-strizhi.ru'.ContactTable::getUrl($arContact['ID']);
                $realtors[] = '<p><a href="'.$url.'">'.$arContact['FULL_NAME'].'</a><p>';

                //запоминаем факт отправки письма для данного уведомления
                KeysTable::set($arContact['KEY'], $arDeal['DEAL_DATE_CREATE_TIMESTAMP']);
            }
        }

        if(!empty($realtors)){
            $eventFields = [
                'EMAIL_TO' => 'taxi@gk-strizhi.ru',
                'SUBJECT' => 'Администратору: нет сделок с риэлтором в течение 3 месяцев',
                'MESSAGE' => '<p><b>По следующим риелторам, нет сделок в течение 3 месяцев:</b><p>'.implode('', $realtors)
            ];
            \CEvent::Send(
                'MESSAGE',
                SITE_ID !== LANGUAGE_ID ? SITE_ID : 's1',
                $eventFields
            );
        }
    }

    /*
    Проверка по контактам типа «риэлтор», у которых ответственный не равен «Менеджеры», не равен «Колл-центр» и
    не равен сотруднику из подразделения «Колл-центр» и не равен admin:

    Если тип события НЕ «Отсутствует КТ», и Если по контакту НЕТ невыполненного дела типа «звонок»,
    где ответственный = ответственный за контакт, то отправляем письмо на почту taxi@gk-strizhi.ru с темой
    «Администратору: нет назначенного дела». В теле письма пишем «По риэлтору <ссылка> не назначено дело».
    В поле «Событие» устанавливаем значение «Отсутствует КТ», дата события <текущая дата + 3 часа>.

    Если тип события НЕ «Просрочена КТ», и если по контакту ЕСТЬ просроченные более чем на сутки невыполненные дела типа «звонок»,
    где ответственный = ответственный за контакт, то отправляем письмо на почту taxi@gk-strizhi.ru с темой
    «Администратору: просрочена КТ более чем на сутки». В теле письма пишем «По риэлтору <ссылка> дело просрочено более, чем на сутки».
    В поле «Событие» устанавливаем значение «Просрочена КТ», дата события <текущая дата + 1 сутки>.
    */
    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function checkRealtorsWithoutActivity()
    {
        Loader::includeModule('crm');

        $obContact = new \CCrmContact(false);

        $assigneds = [MANAGERS, CALL_CENTER];

        foreach(Tools::getUsersByGroupCode('ADMIN') as $id){
            $assigneds[] = $id;
        }
        foreach(Tools::getUsersByDepartment(CALL_CENTER_DEPARTMENT) as $id){
            $assigneds[] = $id;
        }

        $datetime = new \DateTime();
        $datetime->sub(new \DateInterval('P3M'));

        $rs = ContactTable::getList([
            'filter' => [
                'TYPE_ID' => 'PARTNER', //отбираем только риелторов
                '=STATUS.UF_GROUP' => 'WORK', //риелтор в работе
                '!ASSIGNED_BY_ID' => $assigneds, //не учитываем опред. пользователей (см. описание метода) и админов
            ],
            'select' => ['ID', 'FULL_NAME', 'ASSIGNED_BY_ID', 'EVENT_TYPE' => 'ENUM_CRM_1555466110.XML_ID']
        ]);

        $cnt = 0;

        $realtorsWithOverdueActivity = [];
        $realtorsWithoutActivity = [];

        while($arContact = $rs->fetch())
        {
            if($arContact['EVENT_TYPE'] !== 'KT_EMPTY'){
                //ищем невыполненные дела типа «звонок», где ответственный = ответственный за контакт
                $rsActivity = ActivityTable::getList([
                    'filter' => [
                        'RESPONSIBLE_ID' => $arContact['ASSIGNED_BY_ID'],
                        'COMPLETED' => 'N',
                        'TYPE_ID' => \CCrmActivityType::Call,
                        'OWNER_ID' => $arContact['ID'],
                        'OWNER_TYPE_ID' => \CCrmOwnerType::Contact
                    ],
                    'select' => ['ID'],
                    'limit' => 1
                ]);

                if(!$rsActivity->fetch()){
                    //если таких дел нет, то отправляем письмо на почту taxi@gk-strizhi.ru с темой
                    // «Администратору: нет назначенного дела». В теле письма пишем «По риэлтору <ссылка> не назначено дело».

                    $url = 'https://crm.gk-strizhi.ru'.ContactTable::getUrl($arContact['ID']);
                    $realtorsWithoutActivity[] = '<p><a href="'.$url.'">'.$arContact['FULL_NAME'].'</a><p>';

                    //В поле «Событие» устанавливаем значение «Отсутствует КТ», дата события <текущая дата + 3 часа>.
                    $obContact->Update(
                        $arContact['ID'],
                        $fields = [
                            'UF_CRM_1555466110' => Tools::getIDInUFPropEnumByXml(
                                'UF_CRM_1555466110',
                                'KT_EMPTY',
                                0,
                                'CRM_CONTACT'
                            ),
                            'UF_CRM_1555466151' => \ConvertTimeStamp(time() + 3*3600, 'FULL')
                        ]
                    );
                    $cnt++;
                    continue; //дальше не проверяем
                }
            }

            if($arContact['EVENT_TYPE'] !== 'KT_OVERDUE'){
                //ищем просроченные более чем на сутки невыполненные дела типа «звонок», где ответственный = ответственный за контакт
                $datetime = new \DateTime();
                $datetime->sub(new \DateInterval('P1D'));

                $rsActivity = ActivityTable::getList([
                    'filter' => [
                        'RESPONSIBLE_ID' => $arContact['ASSIGNED_BY_ID'],
                        'COMPLETED' => 'N',
                        '<START_TIME' => DateTime::createFromPhp($datetime), //просрочены более чем на сутки
                        'TYPE_ID' => \CCrmActivityType::Call,
                        'OWNER_ID' => $arContact['ID'],
                        'OWNER_TYPE_ID' => \CCrmOwnerType::Contact
                    ],
                    'select' => ['ID'],
                    'limit' => 1
                ]);

                if($rsActivity->fetch()){
                    //если такие дела нашли, то отправляем письмо на почту taxi@gk-strizhi.ru с темой
                    //«Администратору: просрочена КТ более чем на сутки». В теле письма пишем «По риэлтору <ссылка> дело просрочено более, чем на сутки».

                    $url = 'https://crm.gk-strizhi.ru'.ContactTable::getUrl($arContact['ID']);
                    $realtorsWithOverdueActivity[] = '<p><a href="'.$url.'">'.$arContact['FULL_NAME'].'</a><p>';

                    //В поле «Событие» устанавливаем значение «Просрочена КТ», дата события <текущая дата + 1 сутки>.
                    $obContact->Update(
                        $arContact['ID'],
                        $fields = [
                            'UF_CRM_1555466110' => Tools::getIDInUFPropEnumByXml(
                                'UF_CRM_1555466110',
                                'KT_OVERDUE',
                                0,
                                'CRM_CONTACT'
                            ),
                            'UF_CRM_1555466151' => \ConvertTimeStamp(time() + 24*3600, 'FULL')
                        ]
                    );
                    $cnt++;
                    continue; //дальше не проверяем
                }
            }
        }

        if($cnt > 0){
            $eventFields = [
                'EMAIL_TO' => 'taxi@gk-strizhi.ru',
                'SUBJECT' => 'Администратору: результаты проверки',
                'MESSAGE' => '<p><b>По следующим риелторам, не назначено дело:</b><p>'.implode('', $realtorsWithoutActivity).
                             '<br /><br /><p><b>По следующим риелторам, дело просрочено более, чем на сутки:</b><p>'.implode('', $realtorsWithOverdueActivity)
            ];
            \CEvent::Send(
                'MESSAGE',
                SITE_ID !== LANGUAGE_ID ? SITE_ID : 's1',
                $eventFields
            );
        }
    }

    /**
     * Отправка статуса контакта на сайт
     * @param $contactId
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function sendContactStage($contactId){
        $rsContact = ContactTable::getList([
            'filter' => ['ID' => $contactId, 'UF_IS_BINGO' => 1, '>UF_CRM_REALTOR' => 0, '!=TYPE_ID' => 'PARTNER'],
            'select' => ['ID', 'UF_CRM_REALTOR', 'STATUS_CODE' => 'ENUM_CRM_STATUS.XML_ID', 'STATUS_NAME' => 'ENUM_CRM_STATUS.VALUE', 'FULL_NAME']
        ]);

        if($arContact = $rsContact->fetch()){
            //отправляем изменения на сайт
            $siteClient = new Project\Integration\SiteClient();
            $siteClient->change_contact_stage([
                'realtor_id' => $arContact['UF_CRM_REALTOR'],
                'client_full_name' => $arContact['FULL_NAME'],
                'stage' => $arContact['STATUS_NAME'],
                'stage_code' => $arContact['STATUS_CODE']
            ]);
        }
    }

    /**
     * Отправка статуса сделки на сайт
     * @param $contactId
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function sendDealStage($dealId){
        $rsDeal = DealTable::getList([
            'filter' => ['ID' => $dealId, '>UF_CRM_REALTOR' => 0],
            'select' => ['ID', 'FULL_NAME' => 'CONTACT.FULL_NAME', 'STAGE_ID', 'UF_CRM_REALTOR', 'CATEGORY_ID']
        ]);

        if($arDeal = $rsDeal->fetch()){
            //отправляем изменения на сайт
            $siteClient = new Project\Integration\SiteClient();
            $siteClient->change_stage([
                'realtor_id' => $arDeal['UF_CRM_REALTOR'],
                'client_full_name' => $arDeal['FULL_NAME'],
                'deal_stage' => \CCrmDeal::GetStageName($arDeal['STAGE_ID'], $arDeal['CATEGORY_ID'])
            ]);
        }
    }

    /*За три дня до окончания действия уведомления по клиенту отправляем пуш-уведомление:
	«Уведомление по клиенту <ФИО> истекает через 3 дня».*/
    public static function checkNotifyExpireSoon()
    {
        $start = new \DateTime(); //с какого времени будет работать скрипт
        $start->sub(new \DateInterval('P11D'));
        $end = new \DateTime();
        $end->add(new \DateInterval('P3D'));

        $rs = ContactTable::getList([
            'filter' => [
                '!=TYPE_ID' => 'PARTNER',
                '<=UF_BINGO_DATE_SET' => DateTime::createFromPhp($end),
                '>=UF_BINGO_DATE_SET' => DateTime::createFromPhp($start),
                'UF_CRM_CHANNEL' => ContactTable::getChannelByName('Агентство недвижимости'),
                '=STATUS.UF_GROUP' => 'WORK',
                '>UF_CRM_REALTOR' => 0,
                'SEND.ID' => [0, false]
            ],
            'runtime' => [
                new Entity\ExpressionField(
                    'BINGO_DATE_SET_TIMESTAMP',
                    "UNIX_TIMESTAMP(%s)",
                    ['UF_BINGO_DATE_SET']
                ),
                new Entity\ExpressionField(
                    'KEY',
                    "CONCAT('CONTACT_', %s, '_CHECK_BINGO_NOTIFY_EXPIRE')",
                    ['ID']
                ),
                new Entity\ReferenceField(
                    'SEND',
                    KeysTable::getEntity(),
                    [
                        '=this.BINGO_DATE_SET_TIMESTAMP' => 'ref.UF_VALUE',
                        '=this.KEY' => 'ref.UF_KEY',
                    ]
                ),
            ],
            'select' => ['ID', 'FULL_NAME', 'UF_BINGO_DATE_SET', 'KEY', 'BINGO_DATE_SET_TIMESTAMP']
        ]);

        $siteClient = new Project\Integration\SiteClient();

        while($arContact = $rs->fetch()){
            //запоминаем факт отправки письма для данного уведомления
            KeysTable::set($arContact['KEY'], $arContact['BINGO_DATE_SET_TIMESTAMP']);

            //отправляем изменения на сайт
            $siteClient->notice_expire_soon([
                'realtor_id' => $arContact['UF_CRM_REALTOR'],
                'client_full_name' => $arContact['FULL_NAME']
            ]);
        }
    }
}
?>
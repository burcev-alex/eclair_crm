<?php
namespace Studiobit\Project\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Crm\ActivityTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Base\CommandManager;
use Studiobit\Base\Entity\CommandTable;
use Studiobit\Base\Tools;
use Studiobit\Project;

Main\Loader::includeModule('crm');

class ContactTable extends Crm\ContactTable
{
    private static $lastError;
    /**
     * @return array
     */
    public static function getMap()
    {
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER, $DB;

        $map = parent::getMap();

        $map['ORIGIN_ID'] = array(
            'data_type' => 'string'
        );

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

        $map['REQUISITES'] = new Entity\ReferenceField(
            'REQUISITES',
            '\Bitrix\Crm\RequisiteTable',
            [
                '=this.ID' => 'ref.ENTITY_ID',
                '=ref.ENTITY_TYPE_ID' => ['?', \CCrmOwnerType::Contact]
            ]
        );

        $map['FM'] = new Entity\ReferenceField(
            'FM',
            '\Bitrix\Crm\FieldMultiTable',
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'CONTACT']
            ]
        );

        $map['HEAD_ASSIGNED_BY'] = new Entity\ReferenceField(
            'HEAD_ASSIGNED_BY',
            '\Bitrix\Main\User',
            [
                '=this.ASSIGNED_BY.UF_HEAD' => 'ref.ID'
            ]
        );

        $map['COMMANDER_ASSIGNED_BY'] = new Entity\ReferenceField(
            'COMMANDER_ASSIGNED_BY',
            '\Bitrix\Main\User',
            [
                '=this.ASSIGNED_BY.UF_COMMANDER' => 'ref.ID'
            ]
        );

        $map['CHANNEL'] = new Entity\ReferenceField(
            'CHANNEL',
            '\Bitrix\Iblock\Section',
            [
                '=this.UF_CRM_CHANNEL' => 'ref.ID'
            ]
        );

        $statement = '%s';
        $simbols = [' ', '\(', '\)', '-', '*', '+'];

        foreach($simbols as $symbol){
            $statement = "REPLACE($statement, '$symbol', '')";
        }

        $map['FM_VALUE'] = new Main\Entity\ExpressionField(
            'FM_VALUE',
            $statement,
            'FM.VALUE'
        );

        $statement = '%s';
        $statement = "(CASE WHEN SUBSTRING($statement, 1, 1) = '8' THEN CONCAT('7', SUBSTRING($statement, 2))ELSE $statement END)";

        $map['FM_CLEAR_VALUE'] = new Main\Entity\ExpressionField(
            'FM_CLEAR_VALUE',
            $statement,
            ['FM_VALUE', 'FM_VALUE', 'FM_VALUE']
        );


        // $map['STATUS'] = new Entity\ReferenceField(
            // 'STATUS',
            // Project\Entity\StatusContactTable::getEntity(),
            // [
                // '=this.ENUM_CRM_STATUS.XML_ID' => 'ref.UF_CODE'
            // ]
        // );

        return $map;
    }

    public static function getDefaultStatusCode(){
        return 'NEW';
    }

    public static function getDefaultStatusId(){
        foreach(self::getStatusList() as $arEnum){
            if($arEnum['XML_ID'] == self::getDefaultStatusCode()){
                return $arEnum['ID'];
            }
        }

        return false;
    }

    public static function getStatusName($code)
    {
        foreach(self::getStatusList() as $arEnum){
            if($arEnum['XML_ID'] == $code){
                return $arEnum['VALUE'];
            }
        }

        return false;
    }

    public static function getStatusList()
    {
        static $enums = [];

        if(empty($enums)) {
            $rsField = \CAllUserTypeEntity::GetList([], ['ENTITY_ID' => 'CRM_CONTACT', 'FIELD_NAME' => 'UF_CRM_STATUS']);

            if ($arField = $rsField->Fetch()) {

                $enum = new \CUserFieldEnum();
                $rsEnum = $enum->GetList(['sort' => 'asc'], ['USER_FIELD_ID' => $arField['ID']]);

                while ($arEnum = $rsEnum->Fetch()) {
                    $arEnum['DISABLED'] = false;
                    $enums[] = $arEnum;
                }
            }
        }

        return $enums;
    }

    public static function getGroupsList()
    {
        $enums = [];
        $rsField = \CAllUserTypeEntity::GetList([], ['ENTITY_ID' => 'CRM_CONTACT', 'FIELD_NAME' => 'UF_CRM_STATUS_GROUP']);

        if ($arField = $rsField->Fetch()) {

            $enum = new \CUserFieldEnum();
            $rsEnum = $enum->GetList(['sort' => 'asc'], ['USER_FIELD_ID' => $arField['ID']]);

            while ($arEnum = $rsEnum->Fetch()) {
                $enums[] = $arEnum;
            }
        }

        return $enums;
    }

    /**
     * Список статусов для контакта
     * @param $contactId
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\SystemException
     */
    public static function getStatusListInfo($contactId)
    {
        global $USER;

        $bAdmin = $USER->IsAdmin();

        $list = [];

        $enums = self::getStatusList();

        if($contactId > 0) {
            $rsContact = self::getList([
                'filter' => ['ID' => $contactId],
                'select' => ['ID', 'TYPE_ID', 'UF_CRM_STATUS']
            ]);

            if ($arContact = $rsContact->fetch()) {
                foreach($enums as $arEnum){
                    if ($arContact['TYPE_ID'] == 'PARTNER') {
                        if (strpos($arEnum['XML_ID'], 'REALTOR_') !== false || $arEnum['XML_ID'] == 'NEW') {
                            $arEnum['DISABLED'] = false;
                            $list[$arEnum['XML_ID']] = $arEnum;
                        }
                    } else {
                        if (strpos($arEnum['XML_ID'], 'REALTOR_') === false) {
                            $list[$arEnum['XML_ID']] = $arEnum;
                        }
                    }
                }
            }

            if ($arContact['TYPE_ID'] !== 'PARTNER') {
                $available = self::getAvailableStatus($contactId);

                foreach($list as $code => &$item){
                    if(in_array($code, $available) || $bAdmin){
                        $item['DISABLED'] = false;
                    }
                    else{
                        $item['DISABLED'] = true;
                    }
                }
                unset($item);
            }
        }
        else{
            $list[] = $enums[0];
        }

        return $list;
    }

    /**
     * Доступные для данного контакта статусы
     * @param $contactId
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\SystemException
     */
    public static function getAvailableStatus($contactId)
    {
        $list = [];

        $rsContact = self::getList([
            'filter' => ['ID' => $contactId],
            'select' => ['ID', 'TYPE_ID', 'STATUS_CODE' => 'ENUM_CRM_STATUS.XML_ID']
        ]);

        if ($arContact = $rsContact->fetch()) {
            if ($arContact['TYPE_ID'] !== 'PARTNER') {
                if(empty($arContact['STATUS_CODE']))
                    $arContact['STATUS_CODE'] = 'NEW';

                if (in_array($arContact['STATUS_CODE'], ['NEW', 'CALL', 'CALL_BASE', 'OFFLINE', 'HOLD', 'SELL_OTHERS', 'NO_TARGET', 'SECOND', 'BOOKING_ABORT']))
                    $list[] = 'WORK';

                $list[] = 'OFFLINE';

                if (in_array($arContact['STATUS_CODE'], ['NEW', 'CALL', 'WORK'])) {
                    $list[] = 'CALL_BASE';
                    $list[] = 'NO_TARGET';
                    $list[] = 'SECOND';
                }

                if (in_array($arContact['STATUS_CODE'],['NEW', 'CALL', 'WORK'])){
                    //проверяем наличие активных сделок у контакта
                    $rsDeal = \Studiobit\Matrix\Entity\DealTable::getList([
                        'filter' => ['IS_WORK' => 1, 'CONTACT_ID' => $contactId],
                        'select' => ['ID']
                    ]);

                    if(!$rsDeal->fetch()) {
                        //если сделок в работе нет, то разрешаем статусы "Отложили покупку" и "Купили в другом месте"
                        $list[] = 'HOLD';
                        $list[] = 'SELL_OTHERS';
                    }
                }
            }
            else{
                $list = ['REALTOR_READY', 'REALTOR_NOT_WORK', 'REALTOR_NOT_READY', 'REALTOR_WORK'];
            }
        }

        return $list;
    }

    private static function dealStageToContactStatus($stageId, $categoryId){
        $name = \CCrmDeal::GetStageName($stageId, $categoryId);
        switch($name){
            case 'Бронь':
                return 'BOOKING';
            case 'ПДКП':
                return 'BOOKING';
            case 'Согласование ипотеки':
                return 'BOOKING';
            case 'Оформление документов':
                return 'BOOKING';
            case 'Регистрация договора':
                return 'BOOKING';
            case 'Дебиторка':
                return 'DEBT';
            case 'Сделка успешна':
                return 'SELL';
            case 'Не получили ипотеку':
                return 'NO_TARGET';
            case 'Реинвестирование':
                return 'NO_TARGET';
            case 'Расторжение':
                return 'NO_TARGET';
            case 'Отложили покупку':
                return 'HOLD';
            case 'Купили в другом месте':
                return 'SELL_OTHERS';
            case 'Выбор другого объекта ГК Стрижи':
                return 'BOOKING_ABORT';
            case 'Истечение срока бронирования':
                return 'BOOKING_ABORT';
        }

        return false;
    }

    private static function GetStatusPriority($status)
    {
        $arStatus = Project\Entity\StatusContactTable::getList(['filter' => ['UF_CODE' => $status]])->fetch();
        return (int)$arStatus['UF_PRIORITY'];
    }

    /**
     * Обновление статуса контакта
     * @param $contactId
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function updateStatus($contactId)
    {
        //определяем текущий статус
        $rsContact = self::getList([
            'filter' => ['ID' => $contactId],
            'select' => ['ID', 'TYPE_ID', 'STATUS_CODE' => 'ENUM_CRM_STATUS.XML_ID', 'UF_CRM_DIALING']
        ]);

        if ($arContact = $rsContact->fetch())
        {
            if($arContact['TYPE_ID'] !== 'PARTNER')
            {
                //текущий статус контакта
                $contactStatus = $arContact['STATUS_CODE'];

                //если ставится галочка "Нужно дозвониться", то статус - На дозвоне
                if($arContact['UF_CRM_DIALING'] == 1){
                    $contactStatus = 'CALL';
                }
                else
                {
                    $statusPriority = 0;//self::GetStatusPriority($contactStatus);

                    //ищем сделки в работе
                    //если нашли, то определяем статус
                    //статус контакта = статусу из всех сделок с максимальным приоритетом
                    //
                    $rsDeal = DealTable::getList([
                        'filter' => [
                            'CONTACT_ID' => $contactId,
                            'IS_WORK' => 1
                        ],
                        'select' => ['ID', 'STAGE_ID', 'CATEGORY_ID']
                    ]);

                    $cnt = 0;

                    while ($arDeal = $rsDeal->fetch()) {
                        $cnt++;
                        $status = self::dealStageToContactStatus($arDeal['STAGE_ID'], $arDeal['CATEGORY_ID']);

                        if (!empty($status)) {
                            $priority = self::GetStatusPriority($status);

                            if ($priority < $statusPriority || $statusPriority == 0) {
                                $contactStatus = $status;
                                $statusPriority = $priority;
                            }
                        }
                    }

                    if($cnt == 0){
                        //если нет сделок в работе, то статус контакта "В работе"
                        $contactStatus = 'WORK';
                    }
                }

                if($contactStatus !== $arContact['STATUS_CODE']){
                    self::setStatus($arContact['ID'], $contactStatus);
                }
            }
        }
    }
    
    public static function setStatus($contactId, $code){
        $fields = [];

        $enums = self::getStatusList();

        foreach($enums as $enum) {
            if($enum['XML_ID'] == $code) {
                $fields['UF_CRM_STATUS'] = $enum['ID'];
            }
        }

        if(!empty($fields)){
            self::update($contactId, $fields);
            self::sendStatusToSite($contactId);
        }
    }

    /**
     * @param $contactId
     * @param $code
     * @throws \Exception
     */
    public static function sendStatusToSite($contactId){
        $function = '\\Studiobit\\Project\\Command\\Bingo::sendContactStage(' . $contactId . ');';
        if(!CommandManager::have(['UF_COMMAND' => $function])) {
            $datetime = new \DateTime();
            $datetime->setTimestamp(time() + 30);
            CommandManager::add()->date($datetime)->func($function)->module(Project\MODULE_ID)->save();
        }
    }

    public static function checkActiveDeals($contactId){
        $contact = new \CCrmContact();

        $rsCount = DealTable::getList([
            'filter' => [
                'CONTACT_ID' => $contactId,
                'IS_WORK' => true
            ],
            'select' => ['CNT'],
            'runtime' => [
                new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
            ]
        ]);

        $arCount = $rsCount->fetch();

        if ((int)$arCount['CNT'] == 0) {
            $contact->Update($contactId, $fields = ['UF_CRM_ACTIVE_DEAL' => 0]);
        }
        else{
            $contact->Update($contactId, $fields = ['UF_CRM_ACTIVE_DEAL' => 1]);
        }
    }

    public static function checkActiveActivities($contactId){
        $contact = new \CCrmContact(false);
        $activity = new \Bitrix\Crm\ActivityTable();

        $rsCount = $activity->getList(
            [
                'filter' => [
                    '!=TYPE_ID' => \CCrmActivityType::Email,
                    '=OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
                    '=OWNER_ID' => $contactId,
                    '=COMPLETED' => 'N'
                ],
                'select' => ['CNT'],
                'runtime' => [
                    new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')
                ]
            ]
        );

        $arCount = $rsCount->fetch();

        if ((int)$arCount['CNT'] == 0) {
            //если у контакта нет открытых дел
            $contact->Update($contactId, $fields = ['UF_CRM_ACTIVE_ACT' => 0]);
        }
        else{
            $contact->Update($contactId, $fields = ['UF_CRM_ACTIVE_ACT' => 1]);
        }
    }

    /*
    Если в контакте типа Риэлтор ответственный за этого риэлтора менеджер ставит новое дело типа «звонок»,
    и при этом в контакте НЕТ невыполненных дел типа «звонок» этого же сотрудника,
    то в поле «Событие» ставим значение «КТ менеджера», в поле «Дата события» ставим <дата начала поставленного дела + сутки>.
    */
    public static function checkManagerActivities($contactId, $activity){
        if($activity['TYPE_ID'] !== \CCrmActivityType::Call)
            return;

        if($activity['COMPLETED'] !== 'N')
            return;

        $rsContact = self::getList([
            'filter' => [
                'TYPE_ID' => 'PARTNER', //отбираем только риелторов
                'ID' => $contactId
            ],
            'select' => ['ID', 'ASSIGNED_BY_ID']
        ]);

        if($arContact = $rsContact->fetch()) {
            if($arContact['ASSIGNED_BY_ID'] == $activity['RESPONSIBLE_ID']){
                $rsActivity = ActivityTable::getList([
                    'filter' => [
                        'RESPONSIBLE_ID' => $arContact['ASSIGNED_BY_ID'],
                        'COMPLETED' => 'N',
                        'TYPE_ID' => \CCrmActivityType::Call,
                        'OWNER_ID' => $arContact['ID'],
                        'OWNER_TYPE_ID' => \CCrmOwnerType::Contact,
                        '!ID' => $activity['ID']
                    ],
                    'select' => ['ID', 'START_TIME'],
                    'limit' => 1
                ]);

                if(!$rsActivity->fetch()){
                    $obContact = new \CCrmContact(false);

                    $datetime = time();

                    if(!empty($activity['START_TIME'])){
                        $datetime = \MakeTimeStamp($activity['START_TIME']);
                    }

                    $obContact->Update(
                        $arContact['ID'],
                        $fields = [
                            'UF_CRM_1555466110' => Tools::getIDInUFPropEnumByXml(
                                'UF_CRM_1555466110',
                                'KT_MANAGER',
                                0,
                                'CRM_CONTACT'
                            ),
                            'UF_CRM_1555466151' => \ConvertTimeStamp($datetime + 24*3600, 'FULL')
                        ]
                    );
                }
            }
        }
    }
    
    public static function validateFields(&$fields)
    {
        $errors = [];
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');

        //запрещаем указывать номера телефонов, которые уже есть у других контактов
        if(isset($fields['FM']['PHONE'])) {
            $phoneCount = 0;
            foreach ($fields['FM']['PHONE'] as $id => $arPhone) {
                if (!empty($arPhone['VALUE'])) {
                    $phoneCount++;

                    $sql = self::getSqlForSearchByPhone($arPhone['VALUE'], ['ID']);
                    $sql->whereNot('ID', '=', $fields['ID']);
                    $rsContact = $sql->exec();

                    if ($arContact = $rsContact->fetch()) {
                        $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arContact['ID']]);
                        $errors[] = 'Клиент с номером телефона ' . $arPhone['VALUE'] . ' уже существует - <a href="' . $url . '" target="_blank">показать</a>';
                    }
                }
            }

            if(!$phoneCount){
                $errors[] = 'Не заполнено обязательное поле "Телефон"';
            }
        }

        if(!empty($errors)){
            $fields['RESULT_MESSAGE'] = implode('<br />', $errors);
        }
        
        return empty($errors);
    }

    public static function validatePassportData($id)
    {
        $rs = self::getList([
            'filter' => ['ID' => $id],
            'select' => [
                'ID', 'FULL_NAME',
                'RQ_IDENT_DOC_SER' => 'REQUISITES.RQ_IDENT_DOC_SER',
                'RQ_IDENT_DOC_NUM' => 'REQUISITES.RQ_IDENT_DOC_NUM',
                'RQ_IDENT_DOC_ISSUED_BY' => 'REQUISITES.RQ_IDENT_DOC_ISSUED_BY',
                'RQ_IDENT_DOC_DATE' => 'REQUISITES.RQ_IDENT_DOC_DATE',
                'RQ_IDENT_DOC_DEP_CODE' => 'REQUISITES.RQ_IDENT_DOC_DEP_CODE'
            ]
        ]);

        if($ar = $rs->fetch()){
            foreach($ar as $k => $v){
                $ar[$k] = trim($v);
            }

            if(empty($ar['RQ_IDENT_DOC_SER']) ||
                empty($ar['RQ_IDENT_DOC_NUM']) ||
                empty($ar['RQ_IDENT_DOC_ISSUED_BY']) ||
                empty($ar['RQ_IDENT_DOC_DATE']) ||
                empty($ar['RQ_IDENT_DOC_DEP_CODE']))
            {
                self::$lastError = 'У клиента '.$ar['FULL_NAME'].': не заполнены паспортные данные';
                return false;
            }
        }

        return true;
    }

    public static function getLastError(){
        return self::$lastError;
    }

    public static function updateStock()
    {
        if (\Bitrix\Main\Loader::includeModule('pull')) {
            $users = Tools::getUsersByRoleName('Биржа');
            if(!empty($users)) {
                foreach($users as $id){
                    $timeOff = \CUserOptions::GetOption(\Studiobit\Project\MODULE_ID, 'stock_off_time', time(), $id);
                    \Bitrix\Pull\Event::add(
                        $id,
                        [
                            'module_id' => Project\MODULE_ID,
                            'command'   => 'stock_update',
                            'params'    => [
                                'ITEMS' => self::getStock(),
                                'ENABLED' => time() >= $timeOff || empty($timeOff)
                            ]
                        ]
                    );
                }
            }
        }
    }

    public static function getStock(){
        $return = [];

        $rs = self::getList([
            'filter' => [
                'ENUM_CRM_STATUS.XML_ID' => 'NEW',
                'ASSIGNED_BY_ID'      => Project\MANAGERS
            ],
            'select' => ['ID', 'FULL_NAME', 'DATE_CREATE']
        ]);

        $url = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');
        while ($ar = $rs->fetch()) {
            $ar['TITLE'] = $ar['FULL_NAME'];
            $ar['DATE']= '';
            if($ar['DATE_CREATE'] instanceof Main\Type\DateTime){
                $ar['DATE'] = $ar['DATE_CREATE']->toString();
            }
            $ar['URL'] = \CComponentEngine::makePathFromTemplate($url, ['contact_id' => $ar['ID']]);
            $return[$ar['ID']] = $ar;
        }

        return $return;
    }

    public static function isStock($id){
        $rs = self::getList([
            'filter' => [
                'ENUM_CRM_STATUS.XML_ID' => 'NEW',
                'ASSIGNED_BY_ID'      => Project\MANAGERS,
                'ID' => $id
            ],
            'select' => ['ID']
        ]);

        return is_array($rs->fetch());
    }

    public static function getChannelByName($name){
        if(Main\Loader::includeModule('iblock')){
            $rs = SectionTable::getList([
                'filter' => ['NAME' => $name, 'IBLOCK_ID' => Project\IBLOCK_ID_CHANNELS],
                'select' => ['ID']
            ]);

            if($ar = $rs->fetch())
                return $ar['ID'];
        }

        return false;
    }
    
    public static function getSourceByName($name){
        if(Main\Loader::includeModule('iblock')){
            $rs = ElementTable::getList([
                'filter' => ['NAME' => $name, 'IBLOCK_ID' => Project\IBLOCK_ID_CHANNELS],
                'select' => ['ID']
            ]);
            
            if($ar = $rs->fetch())
                return $ar['ID'];
        }
        
        return false;
    }

    /**
     * @param $phone
     * @param $select
     * @return Entity\Query
     */
    public static function getSqlForSearchByPhone($phone, $select = false){
        $sql = self::query();

        $sql->where('FM.TYPE_ID', '=', 'PHONE');

        $statement = '%s';
        $simbols = [' ', '\(', '\)', '-', '*', '+'];

        $query = str_replace(array_merge($simbols, ['(', ')']), '', $phone);
        if(substr($query, 0, 1) == '8')
            $query = '7'.substr($query, 1);

        foreach($simbols as $symbol){
            $statement = "REPLACE($statement, '$symbol', '')";
        }

        $sql->registerRuntimeField(
            'CONTACT_FM_VALUE',
            new Main\Entity\ExpressionField(
                'CONTACT_FM_VALUE',
                $statement,
                'FM.VALUE'
            )
        );

        $statement = '%s';
        $statement = "(CASE WHEN SUBSTRING($statement, 1, 1) = '8' THEN CONCAT('7', SUBSTRING($statement, 2))ELSE $statement END)";

        $sql->registerRuntimeField(
            'CONTACT_FM_CLEAR_VALUE',
            new Main\Entity\ExpressionField(
                'CONTACT_FM_CLEAR_VALUE',
                $statement,
                ['CONTACT_FM_VALUE', 'CONTACT_FM_VALUE', 'CONTACT_FM_VALUE']
            )
        );

        $sql->whereLike('CONTACT_FM_CLEAR_VALUE', $query.'%');

        if(is_array($select))
            $sql->setSelect($select);

        return $sql;
    }
    
    public static function getByPhone($phone, $select = false)
    {
        $sql = self::getSqlForSearchByPhone($phone, $select);
        $rs= $sql->exec();

        return $rs->fetch();
    }

    public static function getUrl($id){
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');
        return\CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $id]);
    }
}
?>
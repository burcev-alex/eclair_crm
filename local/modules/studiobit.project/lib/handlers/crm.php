<?
namespace Studiobit\Project\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Studiobit\Base as Base;
use Studiobit\Base\Tools;
use Studiobit\Matrix\Entity\Object;
use Studiobit\Project;
use Bitrix\Main\Entity;
use const Studiobit\Project\CALL_CENTER;
use const Studiobit\Project\CALL_CENTER_DEPARTMENT;
use Studiobit\Project\Entity\UserTable;
use const Studiobit\Project\MANAGERS;

Loc::loadMessages(__FILE__);

class Crm
{
    private static $prevDeal;
    private static $prevContact;

    public static function onAfterCrmControlPanelBuild(&$items)
    {

        //скрываем лишние вкладки из меню раздела crm
        $removeItems = ['START', 'EVENT', 'QUOTE', 'INVOICE', 'FACETRACKER'];

        if(!$GLOBALS['USER']->IsAdmin()){
            $removeItems[] = 'LEAD';
        }

        $items[] = array(
            "NAME" => "Договоры",
            "TITLE" => "Договоры",
            "ID" => "AGREEMENT",
            "URL" => "/crm/agreement/",
            "ICON" => "deal",
            "SORT" => 700
        );

        $items[] = array(
            "NAME" => "Рабочий стол ипотечного специалиста",
            "TITLE" => "Рабочий стол ипотечного специалиста",
            "ID" => "MORTAGE",
            "URL" => "/crm/mortage/",
            "ICON" => "mortage",
            "SORT" => 800
        );

        // сортировка для стандартных элементов
        $sortItems = [
            'MY_ACTIVITY' => 50,
            'CONTACT' => 100,
            'COMPANY' => 150,
            'DEAL' => 200,
            'PRODUCT' => 500,
            'DEAL_FUNNEL' => 600,
            'AGREEMENT' => 700,
            'LEAD' => 800,
        ];

        // установка сортировки
        foreach($items as &$item){
            if(intval($item['SORT']) != 0) continue;

            if(intval($sortItems[$item['ID']]) > 0)
                $item['SORT'] = $sortItems[$item['ID']];
            else
                $item['SORT'] = 1000;

            /*if($item['ID'] == 'COMPANY'){
                $item['NAME'] = 'Агенства';
                p($item);
            }*/
        }
        unset($item);

        // сортировка
        $ar_sort = array();

        foreach($items as $key => $item){
            if(in_array($item['ID'], $removeItems))
                unset($items[$key]);
            else
                $ar_sort[] = $item['SORT'];
        }

        array_multisort($ar_sort, SORT_ASC, $items);
    }

    public static function OnBeforeCrmContactAdd(&$fields)
    {
        if($fields['UF_CRM_CHANNEL'] == 1 && $fields['UF_CRM_SOURCE'] == 1){
            if($channel = Project\Entity\Crm\ContactTable::getChannelByName('Самостоятельно'))
                $fields['UF_CRM_CHANNEL'] = $channel;

            if($source = Project\Entity\Crm\ContactTable::getSourceByName('Входящий поток'))
                $fields['UF_CRM_SOURCE'] = $source;
        }

        $fields['UF_ASSIGNED_DATE'] = \ConvertTimeStamp(false, 'FULL');

        if(!Project\Entity\Crm\ContactTable::validateFields($fields)){
            $GLOBALS['APPLICATION']->ThrowException($fields['RESULT_MESSAGE']);
            return false;
        }

        return true;
    }

    public static function OnAfterCrmContactAdd($fields)
    {
        \p2log($fields, 'contact_add_'.\ConvertTimeStamp());

    }

    public static function OnBeforeCrmContactUpdate(&$fields)
    {
    	global $USER;
	    Loader::includeModule("intranet");

        $bAdmin = $GLOBALS['USER']->IsAdmin();

        if(!Project\Entity\Crm\ContactTable::validateFields($fields)){
            $GLOBALS['APPLICATION']->ThrowException($fields['RESULT_MESSAGE']);
            return false;
        }

        self::$prevContact = Project\Entity\Crm\ContactTable::getList([
            'filter' => ['ID' => $fields['ID']],
            'select' => [
                'ID',
	            'ASSIGNED_BY_ID',
	            'TYPE_ID',
            ]
        ])->fetch();

        //помечаем пользовательские поля на сохранение в истории
        Base\History\CrmHistory::getInstance()->before($fields, 'CONTACT');

        return true;
    }

    public static function OnAfterCrmContactUpdate($fields)
    {
        if(isset($fields['ASSIGNED_BY_ID']) && $fields['ASSIGNED_BY_ID'] !== self::$prevContact['ASSIGNED_BY_ID']) {
            $prevName = UserTable::getFullName(self::$prevContact['ASSIGNED_BY_ID']);
            if(empty($prevName))
                $prevName = 'Пусто';
            else{
                $prevName = '<a href="'.UserTable::getUrl(self::$prevContact['ASSIGNED_BY_ID']).'" target="_blank">'.$prevName.'</a>';
            }

            $currentName = UserTable::getFullName($fields['ASSIGNED_BY_ID']);
            if(empty($currentName))
                $currentName = 'Пусто';
            else
                $currentName = '<a href="'.UserTable::getUrl($fields['ASSIGNED_BY_ID']).'" target="_blank">'.$currentName.'</a>';

            Base\Tools::addTimelineComment(
                \CCrmOwnerType::Contact,
                $fields['ID'],
                '<b>Ответственный</b>: '.$prevName.' → '.$currentName
            );

            //отправляем изменения в бинго
            $siteClient = new Project\Integration\SiteClient();
            $siteClient->realtor_change_manager(['realtor_id' => $fields['ID'], 'manager_id' => $fields['ASSIGNED_BY_ID']]);
        }

        //сохраняем пользовательскте поля в истории
        Base\History\CrmHistory::getInstance()->after();
    }

    public static function OnBeforeCrmCompanyAdd(&$fields)
    {
        if(!Project\Entity\Crm\CompanyTable::validateFields($fields)){
            $GLOBALS['APPLICATION']->ThrowException($fields['RESULT_MESSAGE']);
            return false;
        }
        //помечаем пользовательские поля на сохранение в истории
        Base\History\CrmHistory::getInstance()->before($fields, 'COMPANY');

        return true;
    }

    public static function OnBeforeCrmCompanyUpdate(&$fields)
    {
        if(!Project\Entity\Crm\CompanyTable::validateFields($fields)){
            $GLOBALS['APPLICATION']->ThrowException($fields['RESULT_MESSAGE']);
            return false;
        }
        //помечаем пользовательские поля на сохранение в истории
        Base\History\CrmHistory::getInstance()->before($fields, 'COMPANY');

        return true;
    }

    public static function OnAfterCrmCompanyUpdate(&$fields)
    {
        //сохраняем пользовательскте поля в истории
        Base\History\CrmHistory::getInstance()->after();
    }

    public static function OnBeforeCrmDealUpdate(&$fields)
    {
        //помечаем сделку как измененную для 1с
        if (!isset($fields['UF_UPDATED_1C'])) {
            $fields['UF_UPDATED_1C'] = 0;
        }

        self::$prevDeal = \CCrmDeal::GetListEx([], ['ID' => $fields['ID']], false, false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                'CATEGORY_ID',
                'CLOSED',
                'UF_MINUSK_KM_U',
                'UF_MINUSK_K_U',
                'ASSIGNED_BY_ID',
                'UF_CRM_RESPONSIBLE',
                'UF_MINUSK_K',
                'UF_MINUSK_KM',
                'UF_CRM_ATTRACTED',
                'UF_CRM_ATTRACTED_2'
            ]
        )->Fetch();

        if(!isset($fields['SYSTEM'])) {
            if (!Project\Entity\Crm\DealTable::validateFields($fields)) {
                $GLOBALS['APPLICATION']->ThrowException($fields['RESULT_MESSAGE']);
                return false;
            }
        }
        unset($fields['SYSTEM']);

        //помечаем пользовательские поля на сохранение в истории
        Base\History\CrmHistory::getInstance()->before($fields, 'DEAL');

        return true;
    }

    public static function OnAfterCrmDealUpdate($fields)
    {
        //обновление статуса контакта
        if(isset($fields['STAGE_ID']) && $fields['STAGE_ID'] !== self::$prevDeal['STAGE_ID']) {
            $rsDeal = Project\Entity\Crm\DealTable::getList([
                'filter' => ['ID' => $fields['ID']],
                'select' => ['ID', 'CONTACT_ID']
            ]);

            if ($arDeal = $rsDeal->fetch()) {
                Project\Entity\Crm\ContactTable::updateStatus($arDeal['CONTACT_ID']);
                Project\Entity\Crm\ContactTable::checkActiveDeals($arDeal['CONTACT_ID']);
            }

            if($fields['STAGE_ID'] == Base\Tools::getDealStageIDByName('Регистрация договора', $fields['ID']))//статус сделки = регистрация договора
            {
                $object = Project\Entity\Crm\DealTable::getObject($fields['ID']);
                if(is_object($object)) {
                    $object->setStatus(\Studiobit\Matrix\Entity\ObjectStatus::Registrate);
                }
            }
            elseif($fields['STAGE_ID'] == \CCrmDeal::GetFinalStageID(self::$prevDeal['CATEGORY_ID'])) //статус сделки = продана
            {
                $object = Project\Entity\Crm\DealTable::getObject($fields['ID']);
                if(is_object($object)) {
                    $object->setStatus(\Studiobit\Matrix\Entity\ObjectStatus::Sell);
                }
            }
            elseif($fields['CLOSED'] == 'Y')
            {
                $object = Project\Entity\Crm\DealTable::getObject($fields['ID']);
                if(is_object($object)) {
                    $object->setStatus(\Studiobit\Matrix\Entity\ObjectStatus::Open);
                }

                if(static::$prevDeal['CATEGORY_ID'] == Project\CATEGORY_ID_TRADE_IN) {
                    /*
                     Если сделка из направления "Вторичка" была проиграна, и при этом статус ее был "регистрация договора" либо "дебиторка" либо "сделка успешна", то должно отправляться письмо:
                    Командиру ответственного менеджера из этой сделки. Ярославу Белокобыльскому, Светлане Снопковой, Марине Каракуловой.
                     */

                    if(in_array(static::$prevDeal['STAGE_ID'], [
                        Base\Tools::getDealStageIDByName('Регистрация договора', $fields['ID']),
                        Base\Tools::getDealStageIDByName('Дебиторка', $fields['ID']),
                        \CCrmDeal::GetFinalStageID(self::$prevDeal['CATEGORY_ID'])
                    ])){
                        $eventFields = array_merge($fields, static::$prevDeal);
                        \CEvent::Send('TRADE_IN_DEAL_FAIL', SITE_ID, $eventFields);
                    }
                }
            }

            Project\Entity\Crm\DealTable::bizproc($fields['ID']);
            Project\Entity\Crm\DealTable::sendStageToSite($fields['ID']);
        }

        Project\Entity\Crm\DealTable::checkBonus($fields, self::$prevDeal);

        //сохраняем пользовательские поля в истории
        Base\History\CrmHistory::getInstance()->after();
    }

    public static function OnBeforeCrmDealAdd(&$fields)
    {
        if (!isset($fields['UF_UPDATED_1C'])) {
            $fields['UF_UPDATED_1C'] = 0;
        }
    }

    public static function OnAfterCrmDealAdd($fields)
    {
        \p2log($fields, 'deal_add_'.\ConvertTimeStamp());

        //обновление статуса контакта
        $rsDeal = Project\Entity\Crm\DealTable::getList([
            'filter' => ['ID' => $fields['ID']],
            'select' => ['ID', 'CONTACT_ID']
        ]);

        if($arDeal = $rsDeal->fetch()) {
            Project\Entity\Crm\ContactTable::updateStatus($arDeal['CONTACT_ID']);
            Project\Entity\Crm\ContactTable::checkActiveDeals($arDeal['CONTACT_ID']);
        }

	    if(\Bitrix\Main\Loader::includeModule('bizproc')) {
		    // TODO магическое число можно перенести в опции
		    // запуск БП по БИНГО
		    \CBPDocument::StartWorkflow(27, ['crm', "CCrmDocumentDeal", 'DEAL_' . $fields['ID']], [], $errors = []);
	    }
    }

    public static function OnActivityAdd($ID, $fields)
    {
        $arBindings = \CCrmActivity::GetBindings($ID);

        foreach($arBindings as $arBinding){
            if($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Contact)
            {
                Project\Entity\Crm\ContactTable::checkActiveActivities($arBinding['OWNER_ID']);
                Project\Entity\Crm\ContactTable::checkManagerActivities($arBinding['OWNER_ID'], $fields);
            }
        }
    }

    public static function OnActivityUpdate($ID, $fields)
    {
        $arBindings = \CCrmActivity::GetBindings($ID);

        foreach($arBindings as $arBinding){
            if($arBinding['OWNER_TYPE_ID'] == \CCrmOwnerType::Contact)
            {
                Project\Entity\Crm\ContactTable::checkActiveActivities($arBinding['OWNER_ID']);
            }
        }
    }

    public static function OnBeforeCrmAddEvent($fields)
    {
        if($fields['EVENT_NAME'] == 'Добавлен товар' || $fields['EVENT_NAME'] == 'Удален товар'){
            return false;
        }

        return $fields;
    }

    public static function OnGetActivityProviders()
    {
        return [
            Project\Custom\Activities\Meeting::getId() => Project\Custom\Activities\Meeting::className(),
            Project\Custom\Activities\Call::getId() => Project\Custom\Activities\Call::className()
        ];
    }

    public static function RequisiteOnBeforeAdd(Entity\Event $event)
    {
        $result = new Entity\EventResult;

        $data = $event->getParameter("fields");

        if($data['ENTITY_TYPE_ID'] == \CCrmOwnerType::Company){
            return Project\Entity\Crm\CompanyTable::validateRequisites($event);
        }

        return $result;
    }

    public static function RequisiteOnBeforeUpdate(Entity\Event $event)
    {
        $result = new Entity\EventResult;

        $data = $event->getParameter("fields");

        if($data['ENTITY_TYPE_ID'] == \CCrmOwnerType::Company){
            return Project\Entity\Crm\CompanyTable::validateRequisites($event);
        }

        return $result;
    }

    public static function OnBeforeCrmLeadAdd(&$fields)
    {
        $fields['UF_CRM_ASSIGNED_BY_ID'] = (int)$fields['UF_CRM_ASSIGNED_BY_ID'];
        if($fields['UF_CRM_ASSIGNED_BY_ID'] > 0){
            $fields['ASSIGNED_BY_ID'] = $fields['UF_CRM_ASSIGNED_BY_ID'];
        }

        $fields['UF_ROISTAT_REFERENCE'] = $fields['UF_CRM_1497611410'];
        if(empty($fields['UF_ROISTAT_REFERENCE'])){
            $fields['UF_ROISTAT_REFERENCE'] = $fields['UF_CRM_1510220474'];
        }
        unset($fields['UF_CRM_1497611410']);
        unset($fields['UF_CRM_1510220474']);
        unset($fields['UF_CRM_ASSIGNED_BY_ID']);
    }

    public static function OnAfterCrmLeadAdd($fields)
    {
        Project\Entity\Crm\LeadTable::processAdd($fields['ID']);
    }

    public static function OnAfterCrmLeadUpdate($fields)
    {
        //Project\Entity\Crm\LeadTable::processAdd($fields['ID']);
    }
}

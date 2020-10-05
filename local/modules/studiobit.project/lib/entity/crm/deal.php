<?php
namespace Studiobit\Project\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Studiobit\Base\CommandManager;
use Studiobit\Base\Tools;
use Studiobit\Project as Project;
use Studiobit\Matrix\Entity\Object;

Main\Loader::includeModule('crm');

class DealTable extends Crm\DealTable
{
    /**
     * @return array
     */
    public static function getMap()
    {
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER;

        $map = parent::getMap();

        $userFields = $USER_FIELD_MANAGER->GetUserFields(static::getUfId());
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

        $map['PRODUCT_ROW_CUSTOM'] = new Entity\ReferenceField(
            'PRODUCT_ROW_CUSTOM',
            '\Bitrix\Crm\ProductRowTable',
            [
                '=this.ID' => 'ref.OWNER_ID',
                '=ref.OWNER_TYPE' => ['?', 'D']
            ]
        );

        if(Main\Loader::includeModule('studiobit.matrix'))
        {
            $map['PRODUCT'] = new Entity\ReferenceField(
                'PRODUCT',
                '\Bitrix\IBlock\ElementTable',
                [
                    '=this.PRODUCT_ROW_CUSTOM.PRODUCT_ID' => 'ref.ID',
                    '=ref.IBLOCK_ID' => ['?', Object::getIBlockID()]
                ]
            );
        }
        
        $map['CONTACT'] = new Entity\ReferenceField(
            'CONTACT',
            \Studiobit\Project\Entity\Crm\ContactTable::getEntity(),
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

    /**
     * Дополнительный фильтр ADDITIONAL_FILTER для компонента списка сделок srm.deal.list
     * @param array $arParams
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\LoaderException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public static function prepareExternalFilter($arParams = []){
        $return = [];

        if ($arParams['IS_RECURRING'] === 'Y')
        {
            $grid_id = 'CRM_DEAL_RECUR_LIST_V12'.(!empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
        }
        else
        {
            $grid_id = 'CRM_DEAL_LIST_V12'.(!empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
        }

        $gridFilter = new \Bitrix\Main\UI\Filter\Options($grid_id);
        $arFilter = $gridFilter->getFilter();

        $query = self::query();

        //поиск по телефону клиента
        if(!empty($arFilter['UF_CRM_CLIENT_PHONE'])) {
            $phone = str_replace(['-', ' ', '(', ')'], '', $arFilter['UF_CRM_CLIENT_PHONE']);

            $return['UF_CRM_CLIENT_PHONE'] = '';

            $query->where('CONTACT.FM.TYPE_ID', '=', 'PHONE');
            $query->whereLike(new Entity\ExpressionField('CONTACT_FM_VALUE', 'REPLACE(%s, \' \', \'\')', 'CONTACT.FM.VALUE'), $phone.'%');

        }

        //номер договора
        if(!empty($arFilter['UF_CRM_AGREEMENT'])) {
            $return['UF_CRM_AGREEMENT'] = '';

            $query->whereLike('AGREEMENT.UF_NUMBER', $arFilter['UF_CRM_AGREEMENT'].'%');
        }

        //Сумма договора
        if(!empty($arFilter['UF_CRM_AGREEMENT_SUM'])) {
            $return['UF_CRM_AGREEMENT'] = '';

            $query->whereLike('AGREEMENT.UF_NUMBER', $arFilter['UF_CRM_AGREEMENT'].'%');
        }

        //название ЖК
        if(!empty($arFilter['UF_CRM_PRODUCT_SECT'])) {
            if(Main\Loader::includeModule('iblock')){
                $sections = $arFilter['UF_CRM_PRODUCT_SECT'];

                foreach($arFilter['UF_CRM_PRODUCT_SECT'] as $sectionId) {
                    $rsSection = \CIBlockSection::GetList(
                        [],
                        ['IBLOCK_ID' => Object::getIBlockID(), 'ID' => $sectionId],
                        false,
                        ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN']
                    );

                    if($arSection = $rsSection->Fetch())
                    {
                        $rsChilds = \CIBlockSection::GetList(
                            [],
                            [
                                'IBLOCK_ID' => Object::getIBlockID(),
                                '>=LEFT_MARGIN' => $arSection['LEFT_MARGIN'],
                                '<=RIGHT_MARGIN' => $arSection['RIGHT_MARGIN']
                            ],
                            false,
                            ['ID']
                        );

                        while($arChild = $rsChilds->Fetch()){
                            $sections[] = $arChild['ID'];
                        }
                    }
                }

                if(!empty($sections)){
                    $query->whereIn('PRODUCT.IBLOCK_SECTION_ID', $sections);
                }
            }

            $return['UF_CRM_PRODUCT_SECT'] = '';
        }
        
        if(!empty($return))
        {
            if(!isset($return['ID']) || empty($return['ID']))
                $return['ID'] = [-1];

            $rs = $query->setSelect(['ID'])->exec();
            while ($ar = $rs->fetch()) {
                $return['ID'][] = $ar['ID'];
            }
        }

        return $return;

    }

    /**
     * запуск основого процесса сделки
     * @param $id
     * @return string
     * @throws Main\ArgumentNullException
     * @throws Main\ArgumentOutOfRangeException
     * @throws Main\LoaderException
     */
    public static function bizproc($id){
        if(\Bitrix\Main\Loader::includeModule('bizproc')) {
            $templateId = Main\Config\Option::get(Project\MODULE_ID, 'bp_main_deal', 26);
            return \CBPDocument::StartWorkflow($templateId, ['crm', "CCrmDocumentDeal", 'DEAL_' . $id], [], $errors = []);
        }

        return false;
    }

    public static function checkBonus($fields, $prev)
    {
        global $USER;

        $updateFields = [];

        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_deal_show');
        $dealUrl = \CComponentEngine::makePathFromTemplate($urlTemplate, ['deal_id' => $prev['ID']]);

        $dealUrl = \CHTTP::URN2URI($dealUrl);

        if(isset($fields['UF_MINUSK_KM']) && $fields['UF_MINUSK_KM'] != $prev['UF_MINUSK_KM'])
        {
            if($fields['UF_MINUSK_KM'])
            {
                $fields['UF_MINUSK_KM_U'] = $updateFields['UF_MINUSK_KM_U'] = $USER->GetID();

                $ids = $fields["UF_MINUSK_KM_U"] . ' | ' . $prev['ASSIGNED_BY_ID'];
                if (!empty($prev['UF_CRM_RESPONSIBLE'])) $ids .= ' | ' . $prev['UF_CRM_RESPONSIBLE'];

                $rsUsers = \CUser::GetList(
                    $by = '',
                    $order = '',
                    ['ID' => $ids],
                    ['FIELDS' => ['ID', 'EMAIL', 'NAME', 'LAST_NAME', 'SECOND_NAME']]
                );

                $mails = [];
                $FIO = '';

                while ($curUser = $rsUsers->Fetch())
                {
                    if ($curUser['ID'] == $fields['UF_MINUSK_KM_U'])
                    {
                        $FIO = $curUser['LAST_NAME'] . ' ' . $curUser['NAME'] . ' ' . $curUser['SECOND_NAME'];
                    }
                    else
                    {
                        $mails[] .= $curUser['EMAIL'];
                    }
                }

                if (!empty($mails))
                {
                    $arEventFields = array(
                        "MAIL" => implode(',', $mails),
                        "HTML" => 'В сделке <a href="'.$dealUrl.'">' . $prev['TITLE'] . '</a> применен понижающий коэффициент (КМ) пользователем ' . $FIO,
                    );
                    
                    \CEvent::Send('STUDIOBIT_BONUS_MINUS', SITE_ID, $arEventFields);
                }
            }
            else{
                $updateFields['UF_MINUSK_KM_U'] = 0;
            }
        }

        if(isset($fields['UF_MINUSK_K']) && $fields['UF_MINUSK_K'] != $prev['UF_MINUSK_K'])
        {
            if($fields['UF_MINUSK_K'])
            {
                $fields['UF_MINUSK_K_U'] = $updateFields['UF_MINUSK_K_U'] = $USER->GetID();

                $ids = $fields["UF_MINUSK_K_U"];

                if (!empty($prev['UF_CRM_ATTRACTED'])) $ids .= ' | ' . $prev['UF_CRM_ATTRACTED'];
                if (!empty($prev['UF_CRM_ATTRACTED_2'])) $ids .= ' | ' . $prev['UF_CRM_ATTRACTED_2'];

                $rsUsers = \CUser::GetList(
                    $by = '',
                    $order = '',
                    ['ID' => $ids],
                    ['FIELDS' => ['ID', 'EMAIL', 'NAME', 'LAST_NAME', 'SECOND_NAME']]
                );

                $mails = [];
                $FIO = '';

                while ($curUser = $rsUsers->Fetch())
                {
                    if ($curUser['ID'] == $fields['UF_MINUSK_K_U'])
                    {
                        $FIO = $curUser['LAST_NAME'] . ' ' . $curUser['NAME'] . ' ' . $curUser['SECOND_NAME'];
                    }
                    else
                    {
                        $mails[] .= $curUser['EMAIL'];
                    }
                }

                if (!empty($mails))
                {
                    $arEventFields = array(
                        "MAIL" => implode(',', $mails),
                        "HTML" => 'В сделке <a href="'.$dealUrl.'">' . $prev['TITLE'] . '</a> применен понижающий коэффициент (куратор) пользователем ' . $FIO,
                    );

                    \CEvent::Send('STUDIOBIT_BONUS_MINUS', SITE_ID, $arEventFields);
                }
            }
            else{
                $updateFields['UF_MINUSK_K_U'] = 0;
            }
        }

        if(!empty($updateFields)){
            self::update($prev['ID'], $updateFields);
        }
    }

    public static function validateFields(&$fields, $bSendErrorToChat = true)
    {
        global $USER;
        $errors = [];
        
        $bAdmin = $USER->IsAdmin();

        $deal = \CCrmDeal::GetListEx([], ['ID' => $fields['ID']], false, false,
            [
                'ID',
                'TITLE',
                'STAGE_ID',
                'CATEGORY_ID',
                'CLOSED',
                'ASSIGNED_BY_ID',
                'UF_CRM_LAYER',
                'UF_CRM_LAYER_COMMENT',
                'UF_CRM_OWNERS'
            ]
        )->Fetch();
        
        if (isset($fields['STAGE_ID']) && $fields['STAGE_ID'] !== $deal['STAGE_ID'] && !$bAdmin) {

            if($bSendErrorToChat)
                Main\Loader::includeModule('im');
            
            /*Запрещено возвращать сделку назад из проигранных*/
            if ($deal['CLOSED'] == 'Y' && $fields['STAGE_ID'] !== $deal['STAGE_ID']) {
                //стадии проигрыша, их можно менять
                $arLoseStages = Tools::getLoseStages($fields['ID']);

                if (!isset($arLoseStages[$fields["STAGE_ID"]])) {
                    if($bSendErrorToChat) {
                        Tools::addNoteUser(
                            $USER->GetID(),
                            $USER->GetID(),
                            "Нельзя изменять стадию у закрытой сделки",
                            IM_NOTIFY_SYSTEM
                        );
                    }
                    $errors[] = 'Нельзя изменять стадию у закрытой сделки';
                }
            } /*Запрещено двигать стадию сделки назад*/
            elseif ($fields["STAGE_ID"] !== $deal['STAGE_ID']) {
                $prevSort = Tools::getStatusSort($deal['STAGE_ID'], $fields['ID']);
                $newSort = Tools::getStatusSort($fields["STAGE_ID"], $fields['ID']);

                if ($newSort < $prevSort) {
                    if($bSendErrorToChat) {
                        Tools::addNoteUser(
                            $USER->GetID(),
                            $USER->GetID(),
                            "Нельзя изменять стадию сделки на предыдущую",
                            IM_NOTIFY_SYSTEM
                        );
                    }
                    $errors[] = 'Нельзя изменять стадию сделки на предыдущую';
                }
            }

            /*
                Для неадминов запрещено менять статус сделки на "Регистрация договора", "Купили у нас", "Дебиторка".
            */
            if ($fields['SYSTEM'] != true && !defined('1C_EXCHANGE') && empty($errors)) {
                $stageName = \CCrmDeal::GetStageName($fields['STAGE_ID'], $deal['CATEGORY_ID']);
                $finalStage = \CCrmDeal::GetFinalStageID($deal['CATEGORY_ID']);
                if(in_array($stageName, ['Регистрация договора', 'Дебиторка']) || $finalStage == $fields['STAGE_ID']){
                    $error = 'Статус "'.$stageName.'" не доступен для ручного перехода. Информация по статусу приходит из 1С.';
                    if($bSendErrorToChat) {
                        Tools::addNoteUser(
                            $USER->GetID(),
                            $USER->GetID(),
                            $error,
                            IM_NOTIFY_SYSTEM
                        );
                    }
                    $errors[] = $error;
                }
                elseif($stageName == 'Оформление документов'){
                    /*Происходит проверка на заполнение полей:
                        •	Комментарий юристу;
                        •	Юрист;
                        •	Паспортные данные дольщиков;
                    */

                    if(empty($deal['UF_CRM_LAYER']) && empty($deal['UF_CRM_LAYER'])){
                        $errors[] = 'Для перехода на стадию сделки "Оформление документов" обязательно заполните поле "Юрист"';
                    }

                    if(empty($deal['UF_CRM_LAYER_COMMENT']) && empty($deal['UF_CRM_LAYER_COMMENT'])){
                        $errors[] = 'Для перехода на стадию сделки "Оформление документов" обязательно заполните поле "Комментарий юристу"';
                    }

                    if(empty($errors)){
                        $owners = $fields['UF_CRM_OWNERS'];
                        if(empty($owners)){
                            $owners = $deal['UF_CRM_OWNERS'];
                        }

                        if(empty($owners)){
                            $errors[] = 'Для перехода на стадию сделки "Оформление документов" обязательно заполните поле "Дольщики"';
                        }
                        else{
                            foreach($owners as $ownerId){
                                if(!ContactTable::validatePassportData($ownerId)){
                                    $errors[] = ContactTable::getLastError();
                                }
                            }
                        }
                    }

                    if(!empty($errors))
                    {
                        if($bSendErrorToChat) {
                            Tools::addNoteUser(
                                $USER->GetID(),
                                $USER->GetID(),
                                \HTMLToTxt(implode('<br />', $errors)),
                                IM_NOTIFY_SYSTEM
                            );
                        }
                    }
                }
            }
        }
        

        if(!empty($errors)){
            $fields['RESULT_MESSAGE'] = implode('<br />', $errors);
        }
        
        return empty($errors);
    }

    /**
     * @param $id
     * @return bool|\Studiobit\Matrix\Entity\Object
     * @throws Main\LoaderException
     */
    public static function getObject($id){
        if(Loader::includeModule('studiobit.matrix')){
            return Object::getObjectByEntity('DEAL', $id);
        }

        return false;
    }

    /**
     * @param $id
     * @return string
     * @throws Main\ArgumentNullException
     * @throws Main\ArgumentOutOfRangeException
     */
    public static function getUrl($id){
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_deal_show');
        return\CComponentEngine::makePathFromTemplate($urlTemplate, ['deal_id' => $id]);
    }

    /**
     * @param $dealId
     * @throws \Exception
     */
    public static function sendStageToSite($dealId){
        $function = '\\Studiobit\\Project\\Command\\Bingo::sendDealStage(' . $dealId . ');';
        if(!CommandManager::have(['UF_COMMAND' => $function])) {
            $datetime = new \DateTime();
            $datetime->setTimestamp(time() + 30);
            CommandManager::add()->date($datetime)->func($function)->module(Project\MODULE_ID)->save();
        }
    }
}
?>
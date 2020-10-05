<?php
namespace Studiobit\Project\Report;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Project;

Main\Loader::includeModule('crm');
Main\Loader::includeModule('report');

class Contact extends \CCrmReportHelperBase
{
    protected static function prepareUFInfo()
    {

        if (is_array(self::$arUFId))
            return;

        self::$arUFId = array('CRM_CONTACT');

        if (!is_array(self::$arUFId) || count(self::$arUFId) <= 0 || is_array(self::$ufInfo))
            return;

        /** @global string $DBType */
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $DBType, $USER_FIELD_MANAGER;

        $dbType = ToUpper(strval($DBType));

        $allowedUserTypes = array('string', 'date', 'datetime', 'enumeration', 'double', 'integer', 'boolean', 'file',
            'employee', 'crm', 'crm_status', 'iblock_element', 'iblock_section');

        self::$ufInfo = array();
        self::$ufEnumerations = array();

        foreach(self::$arUFId as $ufId)
        {
            $arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufId, 0, LANGUAGE_ID);

            if (is_array($arUserFields) && count($arUserFields) > 0)
            {
                foreach ($arUserFields as $field)
                {
                    if (isset($field['FIELD_NAME']) && substr($field['FIELD_NAME'], 0, 3) === 'UF_'
                        /*&& (!isset($field['MULTIPLE']) || $field['MULTIPLE'] !== 'Y')*/
                        && isset($field['USER_TYPE_ID']) && in_array($field['USER_TYPE_ID'], $allowedUserTypes, true))
                    {
                        self::$ufInfo[$ufId][$field['FIELD_NAME']] = $field;

                        if ($field['USER_TYPE_ID'] === 'datetime' && $field['MULTIPLE'] !== 'Y') {
                            self::$ufInfo[$ufId][$field['FIELD_NAME']]['EDIT_FORM_LABEL'] =
                            self::$ufInfo[$ufId][$field['FIELD_NAME']]['LIST_COLUMN_LABEL'] =
                            self::$ufInfo[$ufId][$field['FIELD_NAME']]['LIST_FILTER_LABEL'] = $field['EDIT_FORM_LABEL'].' (дата и время)';

                            self::$ufInfo[$ufId][$field['FIELD_NAME'] . self::UF_DATETIME_SHORT_POSTFIX] = $field;

                            self::$ufInfo[$ufId][$field['FIELD_NAME'] . self::UF_DATETIME_SHORT_POSTFIX]['EDIT_FORM_LABEL'] =
                            self::$ufInfo[$ufId][$field['FIELD_NAME'] . self::UF_DATETIME_SHORT_POSTFIX]['LIST_COLUMN_LABEL'] =
                            self::$ufInfo[$ufId][$field['FIELD_NAME'] . self::UF_DATETIME_SHORT_POSTFIX]['LIST_FILTER_LABEL'] = $field['EDIT_FORM_LABEL'].' (дата)';
                        }

                        $blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
                        if ($field['USER_TYPE_ID'] === 'boolean' && $field['MULTIPLE'] !== 'Y')
                            self::$ufInfo[$ufId][$field['FIELD_NAME'].$blPostfix] = $field;

                        if (($dbType === 'ORACLE' || $dbType === 'MSSQL') && $field['MULTIPLE'] === 'Y')
                            self::$ufInfo[$ufId][$field['FIELD_NAME'] . self::UF_TEXT_TRIM_POSTFIX] = $field;
                    }
                }
            }
        }
    }

    public static function GetReportCurrencyID()
    {
        return \CCrmReportManager::GetReportCurrencyID();
    }

    public static function SetReportCurrencyID($currencyID)
    {
        \CCrmReportManager::SetReportCurrencyID($currencyID);
    }

    public static function getEntityName()
    {
        return 'Bitrix\Crm\Contact';
    }
    public static function getOwnerId()
    {
        return 'crm_contact';
    }
    public static function getColumnList()
    {
        IncludeModuleLangFile(__FILE__);

        $columnList = array(
            'ID',
            'NAME',
            'LAST_NAME',
            'SECOND_NAME',
            'POST',
            'ADDRESS',
            'TYPE_BY.STATUS_ID',
            'COMMENTS',
            'SOURCE_BY.STATUS_ID',
            'SOURCE_DESCRIPTION',
            'DATE_CREATE',
            'DATE_MODIFY',
            'BIRTHDATE',
            'FULL_NAME',
            'EMAIL',
            'PHONE',
            'ASSIGNED_BY' => array(
                'ID',
                'SHORT_NAME',
                'NAME',
                'LAST_NAME',
                'WORK_POSITION'
            ),
            'HEAD_ASSIGNED_BY' => array(
                'ID',
                'SHORT_NAME',
                'NAME',
                'LAST_NAME',
                'WORK_POSITION'
            ),
            'COMMANDER_ASSIGNED_BY' => array(
                'ID',
                'SHORT_NAME',
                'NAME',
                'LAST_NAME',
                'WORK_POSITION'
            ),
            'CREATED_BY' => array(
                'ID',
                'SHORT_NAME',
                'NAME',
                'LAST_NAME',
                'WORK_POSITION'
            ),
            'MODIFY_BY' => array(
                'ID',
                'SHORT_NAME',
                'NAME',
                'LAST_NAME',
                'WORK_POSITION'
            )
        );

        // Append user fields
        $blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
        self::prepareUFInfo();

        if (is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
        {
            if (isset(self::$ufInfo['CRM_CONTACT']) && is_array(self::$ufInfo['CRM_CONTACT'])
                && count(self::$ufInfo['CRM_CONTACT']) > 0)
            {
                foreach (self::$ufInfo['CRM_CONTACT'] as $ufKey => $uf)
                {
                    if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
                        || $uf['MULTIPLE'] === 'Y'
                        || substr($ufKey, -strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
                        || substr($ufKey, -strlen($blPostfix)) === $blPostfix)
                    {
                        $columnList[] = $ufKey;
                    }
                    elseif($uf['USER_TYPE_ID'] == 'datetime'){
                        $columnList[] = $ufKey;
                    }
                }
            }
        }

        return $columnList;
    }

    public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
    {
        self::appendBooleanUserFieldsIfNull($entity);
        self::appendDateTimeUserFieldsAsShort($entity);
        self::appendTextUserFieldsAsTrimmed($entity);

        $entity->addField(new Entity\ReferenceField(
            'HEAD_ASSIGNED_BY',
            '\Bitrix\Main\User',
            [
                '=this.ASSIGNED_BY.UF_HEAD' => 'ref.ID'
            ]
        ));

        $entity->addField(new Entity\ReferenceField(
            'COMMANDER_ASSIGNED_BY',
            '\Bitrix\Main\User',
            [
                '=this.ASSIGNED_BY.UF_COMMANDER' => 'ref.ID'
            ]
        ));
    }

    public static function getCustomSelectFields($select, $fList)
    {
        global $DBType;

        $customFields = array();

        $bAggr = false;
        foreach ($select as $elem)
        {
            if (isset($elem['aggr']) && !empty($elem['aggr']))
            {
                $bAggr = true;
                break;
            }
        }

        if ($bAggr)
        {
            $dbType = ToUpper(strval($DBType));

            if ($dbType === 'ORACLE' || $dbType === 'MSSQL')
            {
                foreach ($select as $k => $elem)
                {
                    $fName = $elem['name'];
                    $field = $fList[$fName];
                    $arUF = self::detectUserField($field);
                    if ($arUF['isUF'])
                    {
                        if ($arUF['ufInfo']['MULTIPLE'] === 'Y')
                        {
                            $customField = $elem;
                            $customField['name'] .= self::UF_TEXT_TRIM_POSTFIX;
                            $customFields[$k] = $customField;
                        }
                    }
                }
            }
        }

        return $customFields;
    }

    public static function getCustomColumnTypes()
    {
        return array(
            /*'OPPORTUNITY' => 'float',
            'OPPORTUNITY_ACCOUNT' => 'float',
            'RECEIVED_AMOUNT' => 'float',
            'LOST_AMOUNT' => 'float',
            'CATEGORY_ID' => 'string',
            'ProductRow:DEAL_OWNER.SUM_ACCOUNT' => 'float',
            'ProductRow:DEAL_OWNER.PRICE_ACCOUNT' => 'float',
            'COMPANY_BY.REVENUE' => 'float'*/
        );
    }

    public static function getDefaultColumns()
    {
        return array(
            array('name' => 'NAME'),
            array('name' => 'LAST_NAME'),
            array('name' => 'SECOND_NAME'),
            array('name' => 'ASSIGNED_BY.SHORT_NAME'),
            array('name' => 'ADDRESS')
        );
    }
    public static function getCalcVariations()
    {
        return array_merge(
            parent::getCalcVariations(),
            array(
            )
        );
    }
    public static function getCompareVariations()
    {
        return array_merge(
            parent::getCompareVariations(),
            array(
                'TYPE_BY.STATUS_ID' => array(
                    'EQUAL',
                    'NOT_EQUAL'
                ),
                'SOURCE_BY.STATUS_ID' => array(
                    'EQUAL',
                    'NOT_EQUAL'
                )
            )
        );
    }
    public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
    {
        // permission
        $addClause = \CCrmContact::BuildPermSql('crm_contact');
        if($addClause === false)
        {
            // access dinied
            $filter = array($filter, '=ID' => '0');
        }
    }

    public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
    {
        // HACK: detect if 'report.view' component is rendering excel spreadsheet
        $isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

        $field = $cInfo['field'];
        $fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
        $aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');
        $dataType = self::getFieldDataType($field);

        if(!$aggr && ($v === '' || trim($v) === '.'))
        {
            if(strpos($fieldName, 'TYPE_BY') !== 0)
            {
                $v = GetMessage('CRM_DEAL_CONTACT_NOT_ASSIGNED');
            }
        }
        elseif(!$aggr && $fieldName === 'TYPE_BY.STATUS_ID')
        {
            if($v !== '')
            {
                $v = self::getStatusName($v, 'CONTACT_TYPE', $isHtml);
            }
        }
        elseif(!$aggr && ($fieldName === 'NAME'
                || $fieldName === 'LAST_NAME'
                || $fieldName === 'SECOND_NAME'
                || $fieldName === 'ADDRESS'
                || $fieldName === 'FULL_NAME'))
        {
            if($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['ID']))
            {
                $v = self::prepareContactTitleHtml(self::$CURRENT_RESULT_ROW['ID'], $v);
            }
        }
        elseif ($dataType == 'datetime' && !empty($v)
            && (empty($cInfo['aggr']) || $cInfo['aggr'] !== 'COUNT_DISTINCT')
            && !strlen($cInfo['prcnt'])
        ) {
            if (strpos($fieldName, self::UF_DATETIME_SHORT_POSTFIX) !== false) {
                $v = ($v instanceof \Bitrix\Main\Type\DateTime || $v instanceof \Bitrix\Main\Type\Date) ? ConvertTimeStamp($v->getTimestamp(), 'SHORT') : '';
            }
            else{
                $v = ($v instanceof \Bitrix\Main\Type\DateTime || $v instanceof \Bitrix\Main\Type\Date) ? ConvertTimeStamp($v->getTimestamp(), 'FULL') : '';
            }
        }
        elseif($fieldName !== 'COMMENTS')
        {
            parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
        }
    }

    public static function getPeriodFilter($date_from, $date_to)
    {
        if(is_null($date_from) && is_null($date_to))
        {
            return array(); // Empty filter for empty time interval.
        }

        $filter = array('LOGIC' => 'AND');
        if(!is_null($date_to))
        {
            $filter['<=DATE_CREATE'] = $date_to;
        }

        if(!is_null($date_from))
        {
            $filter['>=DATE_CREATE'] = $date_from;
        }

        return $filter;
    }

    public static function clearMenuCache()
    {
        CrmClearMenuCache();
    }

    public static function getDefaultReports()
    {
        IncludeModuleLangFile(__FILE__);

        return [
            '11.0.6' => [
                [
                    'title' => "Все компании",
                    'description' => "Стандартный отчет по всем компаниям",
                    'mark_default' => 1,
                    'settings' => unserialize('a:10:{s:6:"entity";s:18:"Bitrix\Crm\Contact";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:7;a:1:{s:4:"name";s:9:"FULL_NAME";}i:3;a:1:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";}i:12;a:1:{s:4:"name";s:11:"DATE_CREATE";}i:13;a:1:{s:4:"name";s:5:"PHONE";}i:14;a:1:{s:4:"name";s:14:"UF_CRM_CHANNEL";}i:15;a:1:{s:4:"name";s:13:"UF_CRM_SOURCE";}i:16;a:1:{s:4:"name";s:13:"UF_CRM_STATUS";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:14:"UF_CRM_CHANNEL";s:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"UF_CRM_SOURCE";s:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"UF_CRM_STATUS";s:7:"compare";s:5:"EQUAL";s:5:"value";a:1:{i:0;s:0:"";}s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:3;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";N;}')
                ]
            ]
        ];
    }

    public static function getFirstVersion()
    {
        return '11.0.6';
    }
}
?>
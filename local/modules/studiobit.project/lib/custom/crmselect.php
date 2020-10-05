<?

namespace Studiobit\Project\Custom;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
class CrmSelect
{
    protected static function endResonse($result)
    {
        header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
        if(!empty($result))
        {
            echo \CUtil::PhpToJSObject($result);
        }
        die();
    }

    public static function searchDeal()
    {
        if (!\Bitrix\Main\Loader::includeModule('crm'))
        {
            return;
        }

        global $APPLICATION;

        $userPerms = \CCrmPerms::GetCurrentUserPermissions();
        if(!\CCrmPerms::IsAuthorized())
        {
            return;
        }

        if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
        {
            \Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

            if(!\CCrmDeal::CheckReadPermission(0, $userPerms))
            {
                self::endResonse(array('ERROR' => 'Access denied.'));
            }

            \CUtil::JSPostUnescape();
            $APPLICATION->RestartBuffer();

            // Limit count of items to be found
            $nPageTop = 50;		// 50 items by default
            if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
            {
                $rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
                if ($rawNPageTop === 0)
                    $nPageTop = false;		// don't limit
                elseif ($rawNPageTop > 0)
                    $nPageTop = $rawNPageTop;
            }

            $arData = array();
            $search = trim($_REQUEST['VALUE']);
            if (!empty($search))
            {
                $multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y' ? true : false;
                $arFilter = array();
                if (is_numeric($search))
                {
                    $arFilter['ID'] = (int)$search;
                    $arFilter['%TITLE'] = $search;
                    $arFilter['LOGIC'] = 'OR';
                }
                else if (preg_match('/(.*)\[(\d+?)\]/i' . BX_UTF_PCRE_MODIFIER, $search, $arMatches))
                {
                    $arFilter['ID'] = (int)$arMatches[2];
                    $arFilter['%TITLE'] = trim($arMatches[1]);
                    $arFilter['LOGIC'] = 'OR';
                }
                else
                    $arFilter['%TITLE'] = $search;

                $settings = unserialize(\COption::GetOptionString('crm', 'CONFIG_STATUS_DEAL_STAGE'));
                $arDealStageList = \CCrmStatus::GetStatusListEx('DEAL_STAGE');
                $arSelect = array('ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
                $arOrder = array('TITLE' => 'ASC');
                $obRes = \CCrmDeal::GetList($arOrder, $arFilter, $arSelect, $nPageTop);

                while ($arRes = $obRes->Fetch())
                {
                    $clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
                    $clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '') . $arRes['CONTACT_FULL_NAME'].'<br />';

                    $background = \Bitrix\Crm\Color\DealStageColorScheme::getDefaultColorByStage($arRes['STAGE_ID'], (int)$arRes['CATEGORY_ID']);

                    if(!empty($settings[$arRes['STAGE_ID']]))
                    {
                        $background = $settings[$arRes['STAGE_ID']]['COLOR'];
                    }

                    $color = '#000';
                    if(!empty($background)) {
                        //цвет шрифта
                        $color = \Studiobit\Base\Tools::getColorByBackground($background);
                    }

                    $stage = '<span class="crm-deal-stage" style="background:'.$background.';color:'.$color.'">'.$arDealStageList[$arRes['STAGE_ID']].'</span>';

                    $arData[] =
                        array(
                            'id' => $multi ? 'D_' . $arRes['ID'] : $arRes['ID'],
                            'url' => \CComponentEngine::makePathFromTemplate(\COption::GetOptionString('crm', 'path_to_deal_show'),
                                array(
                                    'deal_id' => $arRes['ID']
                                )
                            ),
                            'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
                            'desc' => $clientTitle.$stage,
                            'type' => 'deal'
                        );
                }
            }

            self::endResonse($arData);
        }
    }

    public static function searchFriend()
    {
        if (!\Bitrix\Main\Loader::includeModule('crm'))
        {
            return;
        }

        global $APPLICATION;

        $userPerms = \CCrmPerms::GetCurrentUserPermissions();
        if(!\CCrmPerms::IsAuthorized())
        {
            return;
        }

        if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
        {
            if(!\CCrmContact::CheckReadPermission(0, $userPerms))
            {
                self::endResonse(array('ERROR' => 'Access denied.'));
            }

            \CUtil::JSPostUnescape();

            $APPLICATION->RestartBuffer();

            // Limit count of items to be found
            $nPageTop = 50;		// 50 items by default
            if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
            {
                $rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
                if ($rawNPageTop === 0)
                    $nPageTop = false;		// don't limit
                elseif ($rawNPageTop > 0)
                    $nPageTop = $rawNPageTop;
            }

            $requireRequisiteData = (
                is_array($_REQUEST['OPTIONS']) && isset($_REQUEST['OPTIONS']['REQUIRE_REQUISITE_DATA'])
                && $_REQUEST['OPTIONS']['REQUIRE_REQUISITE_DATA'] === 'Y'
            );

            $arData = array();
            $search = trim($_REQUEST['VALUE']);
            if (!empty($search))
            {
                $multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
                $arFilter = array(
                    'UF_CRM_IS_FRIEND' => 1
                );
                if (is_numeric($search))
                {
                    $arFilter['ID'] = (int)$search;
                }
                else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
                {
                    $arFilter['ID'] = (int) $arMatches[2];
                    $arFilter['%FULL_NAME'] = trim($arMatches[1]);
                    $arFilter['LOGIC'] = 'OR';
                }
                else
                {
                    $searchParts = preg_split('/[\s]+/', $search, 2, PREG_SPLIT_NO_EMPTY);
                    if(count($searchParts) < 2)
                    {
                        $arFilter['%FULL_NAME'] = $search;
                    }
                    else
                    {
                        $arFilter['LOGIC'] = 'AND';
                        for($i = 0; $i < 2; $i++)
                        {
                            $arFilter["__INNER_FILTER_NAME_{$i}"] = array('%FULL_NAME' => $searchParts[$i]);
                        }
                    }
                }

                $arSelect = array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID');
                $arOrder = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
                $obRes = \CCrmContact::GetListEx($arOrder, $arFilter, false, array('nTopCount' => $nPageTop), $arSelect);

                $i = 0;
                $contactIndex = array();
                $contactTypes = \CCrmStatus::GetStatusList('CONTACT_TYPE');
                while ($arRes = $obRes->Fetch())
                {
                    $arData[$i] =
                        array(
                            'id' => $multi? 'C_'.$arRes['ID']: $arRes['ID'],
                            'url' => \CComponentEngine::makePathFromTemplate(\COption::GetOptionString('crm', 'path_to_contact_show'),
                                array(
                                    'contact_id' => $arRes['ID']
                                )
                            ),
                            'title' => \CCrmContact::PrepareFormattedName(
                                array(
                                    'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
                                    'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
                                    'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
                                    'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
                                )
                            ),
                            'desc' => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
                            'image' => '',
                            'largeImage' => '',
                            'type' => 'contact'
                        );

                    // requisites
                    if ($requireRequisiteData)
                        $arData[$i]['advancedInfo']['requisiteData'] = \CCrmEntitySelectorHelper::PrepareRequisiteData(
                            \CCrmOwnerType::Contact, $arRes['ID'], array('VIEW_DATA_ONLY' => true)
                        );

                    $contactIndex[$arRes['ID']] = &$arData[$i];
                    $i++;
                }

                // advanced info - phone number, e-mail
                $obRes = \CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => array_keys($contactIndex)));
                while($arRes = $obRes->Fetch())
                {
                    if (isset($contactIndex[$arRes['ELEMENT_ID']])
                        && ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
                    {
                        $item = &$contactIndex[$arRes['ELEMENT_ID']];
                        if (!is_array($item['advancedInfo']))
                            $item['advancedInfo'] = array();
                        if (!is_array($item['advancedInfo']['multiFields']))
                            $item['advancedInfo']['multiFields'] = array();
                        $item['advancedInfo']['multiFields'][] = array(
                            'ID' => $arRes['ID'],
                            'TYPE_ID' => $arRes['TYPE_ID'],
                            'VALUE_TYPE' => $arRes['VALUE_TYPE'],
                            'VALUE' => $arRes['VALUE']
                        );
                        unset($item);
                    }
                }
                unset($contactIndex);
            }

            self::endResonse($arData);
        }
    }
}
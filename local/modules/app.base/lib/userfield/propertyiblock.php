<?
namespace App\Base\UserField;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PropertyIblock
{
	function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "S",
			"USER_TYPE" =>"LinkPropertyEnum",
			"DESCRIPTION" =>"Привязка к вариантам свойства список",
			"GetPublicViewHTML" =>array(__CLASS__, "GetPublicViewHTML"),
			"GetAdminListViewHTML" =>array(__CLASS__,"GetAdminListViewHTML"),
			"GetSettingsHTML" =>array(__CLASS__,"GetSettingsHTML"),
			"GetPropertyFieldHtml" =>array(__CLASS__,"GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" =>array(__CLASS__,"GetPropertyFieldHtmlMulty"),
			"GetAdminFilterHTML" => array(__CLASS__,"GetAdminFilterHTML"),
			"PrepareSettings" => array(__CLASS__,"PrepareSettings"),
			"AddFilterFields" => array(__CLASS__,"AddFilterFields"),
		);
	}

	protected function GetLinkElement($sectionID,$iblockID)
	{
		static $cache = array();

		$iblockID = intval($iblockID);
		if (0 >= $iblockID)
			$iblockID = 0;
		$sectionID = intval($sectionID);
		if (0 >= $sectionID)
			return false;
		if (!isset($cache[$sectionID]))
		{
			$arFilter = array();
			if (0 < $iblockID)
				$arFilter['IBLOCK_ID'] = $iblockID;
			$arFilter['ID'] = $sectionID;
			$sectionRes = \CIBlockSection::GetList(array(),$arFilter,false,array('IBLOCK_ID','ID','NAME'));
			if ($section = $sectionRes->GetNext(true,true))
			{
				$result = array(
					'ID' => $section['ID'],
					'NAME' => $section['NAME'],
					'~NAME' => $section['~NAME'],
					'IBLOCK_ID' => $section['IBLOCK_ID'],
				);
				$cache[$sectionID] = $result;
			}
			else
			{
				$cache[$sectionID] = false;
			}
		}
		return $cache[$sectionID];
	}

	protected function GetPropertyValue($arProperty,$arValue)
	{

		$arProperty["LINK_IBLOCK_ID"]=\COption::GetOptionInt('app.shop', 'iblock_catalog', 0);
		$mxResult = false;
		if (0 < intval($arValue['VALUE']))
		{
			$mxResult = self::GetLinkElement($arValue['VALUE'],$arProperty['LINK_IBLOCK_ID']);
			if (is_array($mxResult))
			{
				$mxResult['PROPERTY_ID'] = $arProperty['ID'];
				if (isset($arProperty['PROPERTY_VALUE_ID']))
				{
					$mxResult['PROPERTY_VALUE_ID'] = $arProperty['PROPERTY_VALUE_ID'];
				}
				else
				{
					$mxResult['PROPERTY_VALUE_ID'] = false;
				}
			}
		}
		return $mxResult;
	}

	protected function GetPropertyViewsList($boolFull)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				'REFERENCE' => array(
					Loc::getMessage('BT_UT_SAUTOCOMPLETE_VIEW_AUTO'),
					Loc::getMessage('BT_UT_SAUTOCOMPLETE_VIEW_ELEMENT'),
				),
				'REFERENCE_ID' => array(
					'A','E'
				),
			);
		}
		return array('A','E');
	}

	protected function GetReplaceSymList($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				'REFERENCE' => array(
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_SPACE'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_GRID'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_STAR'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_UNDERLINE'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_OTHER'),

				),
				'REFERENCE_ID' => array(
					' ',
					'#',
					'*',
					'_',
					BT_UT_AUTOCOMPLETE_REP_SYM_OTHER,
				),
			);
		}
		return array(' ', '#', '*','_');
	}

	public function GetValueForAutoComplete($arProperty,$arValue,$arBanSym="",$arRepSym="")
	{
		$arProperty["LINK_IBLOCK_ID"] = \COption::GetOptionInt('app.shop', 'iblock_catalog', 0);
		$strResult = '';
		$mxResult = self::GetPropertyValue($arProperty,$arValue);
		if (is_array($mxResult))
		{
			$strResult = htmlspecialcharsbx(str_replace($arBanSym,$arRepSym,$mxResult['~NAME'])).' ['.$mxResult['ID'].']';
		}
		return $strResult;
	}

	//Need to rewrite
	public function GetValueForAutoCompleteMulti($arProperty,$arValues,$arBanSym="",$arRepSym="")
	{
		$arResult = false;
		$arProperty["LINK_IBLOCK_ID"]=\COption::GetOptionInt('app.shop', 'iblock_catalog', 0);
		if (is_array($arValues))
		{
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				if (!is_array($arOneValue))
				{
					$strTmp = $arOneValue;
					$arOneValue = array(
						'VALUE' => $strTmp,
					);
				}
				$mxResult = self::GetPropertyValue($arProperty,$arOneValue);
				if (is_array($mxResult))
				{
					$arResult[$intPropertyValueID] = htmlspecialcharsbx(str_replace($arBanSym,$arRepSym,$mxResult['~NAME'])).' ['.$mxResult['ID'].']';
				}
			}
		}
		return $arResult;
	}

	protected function GetSymbols($arSettings)
	{
		$strBanSym = $arSettings['BAN_SYM'];
		$strRepSym = (BT_UT_AUTOCOMPLETE_REP_SYM_OTHER == $arSettings['REP_SYM'] ? $arSettings['OTHER_REP_SYM'] : $arSettings['REP_SYM']);
		$arBanSym = str_split($strBanSym,1);
		$arRepSym = array_fill(0,sizeof($arBanSym),$strRepSym);
		$arResult = array(
			'BAN_SYM' => $arBanSym,
			'REP_SYM' => array_fill(0,sizeof($arBanSym),$strRepSym),
			'BAN_SYM_STRING' => $strBanSym,
			'REP_SYM_STRING' => $strRepSym,
		);
		return $arResult;
	}

	//for eding in element
	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$res = \CIBlockProperty::GetByID($arProperty["USER_TYPE_SETTINGS"]["PROP_ID"]);
		if($ar_res = $res->GetNext())
		{
			$property_enums = \CIBlockPropertyEnum::GetList(Array("value"=>"ASC", "SORT"=>"ASC"), Array("CODE"=>$ar_res["CODE"]));
			while($enum_fields = $property_enums->GetNext())
			{
				$ENUM_ARRAY[$enum_fields["ID"]]=$enum_fields["VALUE"];
			}
		}

		$html = '<select name="'.$strHTMLControlName["VALUE"].'">';
		$html .= '<option value="">'.GetMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
		if(!empty($ENUM_ARRAY))
		{
			foreach($ENUM_ARRAY as $id=>$html_value)
			{
				$bSelectet=$value["VALUE"]==$id ? true : false;
				$html .= '<option value="'.$id.'"'.($bSelectet? ' selected': '').'>'.$html_value.'</option>';
			}
		}

		$html .= '</select>';
		return  $html;
	}

	public function GetPropertyFieldHtmlMulty($arProperty, $arValues, $strHTMLControlName)
	{

		global $APPLICATION;

		$arProperty["LINK_IBLOCK_ID"]=\COption::GetOptionInt('app.shop', 'iblock_catalog', 0);

		$arSettings = self::PrepareSettings($arProperty);
		$arSymbols = self::GetSymbols($arSettings);

		$strResult = '';
		if (isset($strHTMLControlName['MODE']) && ('iblock_element_admin' == $strHTMLControlName['MODE']))
		{
			$arResult = false;
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				$mxElement = self::GetPropertyValue($arProperty,$arOneValue);
				if (is_array($mxElement))
				{
					$arResult[] = '<input type="text" name="'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" id="'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" value="'.$arOneValue['VALUE'].'" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_section_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&amp;n='.urlencode($strHTMLControlName["VALUE"].'['.$intPropertyValueID.']').'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" >'.$mxElement['NAME'].'</span>';
				}
			}

			if (0 < intval($arProperty['MULTIPLE_CNT']))
			{
				for ($i = 0; $i < $arProperty['MULTIPLE_CNT']; $i++)
				{
					$arResult[] = '<input type="text" name="'.$strHTMLControlName["VALUE"].'[n'.$i.']" id="'.$strHTMLControlName["VALUE"].'[n'.$i.']" value="" size="5">'.
						'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_section_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&amp;n='.urlencode($strHTMLControlName["VALUE"].'[n'.$i.']').'\', 600, 500);">'.
						'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'[n'.$i.']" ></span>';
				}
			}

			$strResult = implode('<br />',$arResult);
		}
		else
		{
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strResultValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');

			ob_start();
			?><?
			$strRandControlID = $strHTMLControlName["VALUE"].'_'.mt_rand(0, 10000);
			$control_id = $APPLICATION->IncludeComponent(
				"bitrix:main.lookup.input",
				"iblockedit",
				array(
					"CONTROL_ID" => preg_replace("/[^a-zA-Z0-9_]/i", "x", $strRandControlID),
					"INPUT_NAME" => $strHTMLControlName['VALUE'].'[]',
					"INPUT_NAME_STRING" => "inp_".$strHTMLControlName['VALUE'],
					"INPUT_VALUE_STRING" => $strResultValue,
					"START_TEXT" => Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_INVITE'),
					"MULTIPLE" => $arProperty["MULTIPLE"],
					"MAX_WIDTH" => $arSettings['MAX_WIDTH'],
					"MIN_HEIGHT" => $arSettings['MIN_HEIGHT'],
					"MAX_HEIGHT" => $arSettings['MAX_HEIGHT'],
					"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
					'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
					'REP_SYM' => $arSymbols['REP_SYM_STRING'],
					'FILTER' => 'Y',
					'TYPE' => 'SECTION',
				), null, array("HIDE_ICONS" => "Y")
			);
			?><?
			if ('E' == $arSettings['VIEW'])
			{
				?><input
				style="float: left; margin-right: 10px; margin-top: 5px;"
				type="button" value="..."
				title="<? echo Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_SEARCH_ELEMENT_MULTI_DESCR'); ?>"
				onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_section_search.php?lang=<? echo LANGUAGE_ID; ?>&IBLOCK_ID=<? echo $arProperty["LINK_IBLOCK_ID"]; ?>&m=Y&n=&k=&lookup=<? echo 'jsMLI_'.$control_id; ?>', 900, 600);"><?
			}
			if ('Y' == $arProperty['USER_TYPE_SETTINGS']['SHOW_ADD'])
			{
				if ('Y' == $arSettings['IBLOCK_MESS'])
				{
					$arLangMess = \CIBlock::GetMessages($arProperty["LINK_IBLOCK_ID"]);
					$strButtonCaption = $arLangMess['ELEMENT_ADD'];
					if ('' != $strButtonCaption)
					{
						$strButtonCaption = Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_NEW_ELEMENT');
					}
				}
				else
				{
					$strButtonCaption = Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_NEW_ELEMENT');
				}
				?><input
				type="button"
				style="margin-top: 5px;"
				value="<? echo htmlspecialcharsbx($strButtonCaption); ?>"
				title="<? echo Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_NEW_ELEMENT_DESCR'); ?>"
				onclick="jsUtils.OpenWindow('<? echo '/bitrix/admin/'.\CIBlock::GetAdminSectionEditLink(
						$arProperty["LINK_IBLOCK_ID"],
						null,
						array(
							'menu' => null,
							'IBLOCK_SECTION_ID' => -1,
							'find_section_section' => -1,
							'lookup' => 'jsMLI_'.$control_id
						)); ?>', 900, 600);"
				><?
			}
			$strResult = ob_get_contents();
			ob_end_clean();
		}
		return $strResult;
	}

	public function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		$strResult = '';
		$mxResult = self::GetPropertyValue($arProperty,$arValue);

		if (is_array($mxResult))
		{
			$strResult = $mxResult['NAME'].' [<a href="/bitrix/admin/'.
				\CIBlock::GetAdminSectionEditLink(
					$mxResult['IBLOCK_ID'],
					$mxResult['ID'],
					array(
						'WF' => 'Y'
					)
				).'" title="'.Loc::getMessage("BT_UT_SAUTOCOMPLETE_MESS_ELEMENT_EDIT").'">'.$mxResult['ID'].'</a>]';
		}
		return $strResult;
	}

	public function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		static $cache = array();

		$strResult = '';
		$arValue['VALUE'] = intval($arValue['VALUE']);
		if (0 < $arValue['VALUE'])
		{
			if (strlen($cache[$arValue['VALUE']]) == 0)
			{
				$arFilter = array();
				$intIBlockID = intval($arProperty['LINK_IBLOCK_ID']);
				if (0 < $intIBlockID) $arFilter['IBLOCK_ID'] = $intIBlockID;
				$arFilter['ID'] = $arValue['VALUE'];
				$arFilter["ACTIVE"] = "Y";
				$rsElements = \CIBlockPropertyEnum::GetList(array(), $arFilter, false, array("ID","VALUE","NAME"));
				$cache[$arValue['VALUE']] = $rsElements->GetNext(true,true);
			}
			if (is_array($cache[$arValue['VALUE']]))
			{
				if (isset($strHTMLControlName['MODE']) && 'CSV_EXPORT' == $strHTMLControlName['MODE'])
				{
					$strResult = $cache[$arValue['VALUE']]['ID'];
				}
				elseif (isset($strHTMLControlName['MODE']) && ('SIMPLE_TEXT' == $strHTMLControlName['MODE'] || 'ELEMENT_TEMPLATE' == $strHTMLControlName['MODE']))
				{
					$strResult = $cache[$arValue['VALUE']]["VALUE"];
				}
				else
				{
					$strResult = '<a href="'.$cache[$arValue['VALUE']]["SECTION_PAGE_URL"].'">'.$cache[$arValue['VALUE']]["VALUE"].'</a>';
				}
			}
		}
		return $strResult;
	}

	//save data
	public function PrepareSettings($arFields)
	{
		/*
		 * VIEW				- view type
		 * SHOW_ADD			- show button for add new values in linked iblock
		 * MAX_WIDTH		- max width textarea and input in pixels
		 * MIN_HEIGHT		- min height textarea in pixels
		 * MAX_HEIGHT		- max height textarea in pixels
		 * BAN_SYM			- banned symbols string
		 * REP_SYM			- replace symbol
		 * OTHER_REP_SYM	- non standart replace symbol
		 * IBLOCK_MESS		- get lang mess from linked iblock
		 */


		$arViewsList = self::GetPropertyViewsList(false);
		$strView = '';
		$strView = (isset($arFields['USER_TYPE_SETTINGS']['VIEW']) && in_array($arFields['USER_TYPE_SETTINGS']['VIEW'],$arViewsList) ? $arFields['USER_TYPE_SETTINGS']['VIEW'] : current($arViewsList));

		$strShowAdd = (isset($arFields['USER_TYPE_SETTINGS']['SHOW_ADD']) ? $arFields['USER_TYPE_SETTINGS']['SHOW_ADD'] : '');
		$strShowAdd = ('Y' == $strShowAdd ? 'Y' : 'N');

		$intMaxWidth = intval(isset($arFields['USER_TYPE_SETTINGS']['MAX_WIDTH']) ? $arFields['USER_TYPE_SETTINGS']['MAX_WIDTH'] : 0);
		if (0 >= $intMaxWidth) $intMaxWidth = 0;

		$intMinHeight = intval(isset($arFields['USER_TYPE_SETTINGS']['MIN_HEIGHT']) ? $arFields['USER_TYPE_SETTINGS']['MIN_HEIGHT'] : 0);
		if (0 >= $intMinHeight) $intMinHeight = 24;

		$intMaxHeight = intval(isset($arFields['USER_TYPE_SETTINGS']['MAX_HEIGHT']) ? $arFields['USER_TYPE_SETTINGS']['MAX_HEIGHT'] : 0);
		if (0 >= $intMaxHeight) $intMaxHeight = 1000;

		$strBannedSymbols = trim(isset($arFields['USER_TYPE_SETTINGS']['BAN_SYM']) ? $arFields['USER_TYPE_SETTINGS']['BAN_SYM'] : ',;');
		$strBannedSymbols = str_replace(' ','',$strBannedSymbols);
		if (false === strpos($strBannedSymbols,','))
			$strBannedSymbols .= ',';
		if (false === strpos($strBannedSymbols,';'))
			$strBannedSymbols .= ';';

		$strOtherReplaceSymbol = '';
		$strReplaceSymbol = (isset($arFields['USER_TYPE_SETTINGS']['REP_SYM']) ? $arFields['USER_TYPE_SETTINGS']['REP_SYM'] : ' ');
		if (BT_UT_AUTOCOMPLETE_REP_SYM_OTHER == $strReplaceSymbol)
		{
			$strOtherReplaceSymbol = (isset($arFields['USER_TYPE_SETTINGS']['OTHER_REP_SYM']) ? substr($arFields['USER_TYPE_SETTINGS']['OTHER_REP_SYM'],0,1) : '');
			if ((',' == $strOtherReplaceSymbol) || (';' == $strOtherReplaceSymbol))
				$strOtherReplaceSymbol = '';
			if (('' == $strOtherReplaceSymbol) || in_array($strOtherReplaceSymbol,self::GetReplaceSymList()))
			{
				$strReplaceSymbol = $strOtherReplaceSymbol;
				$strOtherReplaceSymbol = '';
			}
		}
		if ('' == $strReplaceSymbol)
		{
			$strReplaceSymbol = ' ';
			$strOtherReplaceSymbol = '';
		}

		$strIBlockMess = (isset($arFields['USER_TYPE_SETTINGS']['IBLOCK_MESS']) ? $arFields['USER_TYPE_SETTINGS']['IBLOCK_MESS'] : '');
		if ('Y' != $strIBlockMess) $strIBlockMess = 'N';

		return array(
			'VIEW' => $strView,
			'SHOW_ADD' => $strShowAdd,
			'MAX_WIDTH' => $intMaxWidth,
			'MIN_HEIGHT' => $intMinHeight,
			'MAX_HEIGHT' => $intMaxHeight,
			'BAN_SYM' => $strBannedSymbols,
			'REP_SYM' => $strReplaceSymbol,
			'OTHER_REP_SYM' => $strOtherReplaceSymbol,
			'IBLOCK_MESS' => $strIBlockMess,
			'PROP_ID'=>$arFields['USER_TYPE_SETTINGS']['PROP_ID']
		);
	}

	//set property
	public function GetSettingsHTML($arFields,$strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT","MULTIPLE_CNT"),
			'USER_TYPE_SETTINGS_TITLE' => Loc::getMessage('BT_UT_SAUTOCOMPLETE_SETTING_TITLE'),
		);



		$setings = self::GetProplistDropDownListExEE($arFields['USER_TYPE_SETTINGS']['PROP_ID'], $strHTMLControlName["NAME"].'[IBLOCK_TYPE_ID]', $strHTMLControlName["NAME"].'[IBLOCK_ID]', $strHTMLControlName["NAME"].'[PROP_ID]',false, '', '',  'class="adm-detail-iblock-types"', 'class="adm-detail-iblock-list"');

		$arSettings = self::PrepareSettings($arFields);

		return '
               <tr>
                    <td>Свойство:</td>
                    <td>'.$setings.'</td>
               </tr>
             ';
	}

	public function GetProplistDropDownListExEE($PROP_ID, $strTypeName, $strIBlockName, $strPropertyName, $arFilter = false, $onChangeType = '', $onChangeIBlock = '', $strAddType = '', $strAddIBlock = '')
	{
		$html = '';

		static $arTypesAll = array();
		static $arTypes = array();
		static $arIBlocks = array();

		if(!is_array($arFilter))
			$arFilter = array();
		if (!array_key_exists('MIN_PERMISSION',$arFilter) || trim($arFilter['MIN_PERMISSION']) == '')
			$arFilter["MIN_PERMISSION"] = "W";
		$filterId = md5(serialize($arFilter));


		if(!isset($arTypes[$filterId]))
		{
			$arTypes[$filterId] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK_TYPE"));
			$arIBlocks[$filterId] = array(0 => array(''=>GetMessage("IBLOCK_CHOOSE_IBLOCK")));


			$rsIBlocks = \CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), $arFilter);
			while($arIBlock = $rsIBlocks->Fetch())
			{
				$tmpIBLOCK_TYPE_ID = $arIBlock["IBLOCK_TYPE_ID"];
				if(!array_key_exists($tmpIBLOCK_TYPE_ID, $arTypesAll))
				{
					$arType = \CIBlockType::GetByIDLang($tmpIBLOCK_TYPE_ID, LANG);
					$arTypesAll[$arType["~ID"]] = $arType["~NAME"]." [".$arType["~ID"]."]";
				}
				if(!array_key_exists($tmpIBLOCK_TYPE_ID, $arTypes[$filterId]))
				{
					$arTypes[$filterId][$tmpIBLOCK_TYPE_ID] = $arTypesAll[$tmpIBLOCK_TYPE_ID];
					$arIBlocks[$filterId][$tmpIBLOCK_TYPE_ID] = array(0 => GetMessage("IBLOCK_CHOOSE_IBLOCK"));
				}
				$arIBlocks[$filterId][$tmpIBLOCK_TYPE_ID][$arIBlock["ID"]] = $arIBlock["NAME"]." [".$arIBlock["ID"]."]";

				$arProperties[$filterId][$arIBlock["ID"]][] = "Выберите свойство";
				$properties = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>$arIBlock["ID"], "PROPERTY_TYPE"=>"L"));
				while ($prop_fields = $properties->GetNext())
				{
					$arProperties[$filterId][$arIBlock["ID"]][$prop_fields['ID']]='['.$prop_fields['ID'].'] '.$prop_fields["NAME"];
				}
			}

			$html .= '
        <script type="text/javascript">
               function OnType_'.$filterId.'_Changed(typeSelect, iblockSelectID)
               {
                   var arIBlocks = '.\CUtil::PhpToJSObject($arIBlocks[$filterId]).';
                   var iblockSelect = BX(iblockSelectID);
                   if(!!iblockSelect)
                   {
                       for(var i=iblockSelect.length-1; i >= 0; i--)
                           iblockSelect.remove(i);
                       for(var j in arIBlocks[typeSelect.value])
                       {
                           var newOption = new Option(arIBlocks[typeSelect.value][j], j, false, false);
                           iblockSelect.options.add(newOption);
                       }
                   }
               }
        
          function OnIBlock_'.$filterId.'_Changed(iblockSelect, sectionSelectID)
          {
             var arSections = '.\CUtil::PhpToJSObject($arProperties[$filterId]).';
             var SectionSelect = BX(sectionSelectID);
             console.log(arSections);
             if(SectionSelect)
             {
                var sel=getSelectedIndexes(iblockSelect);
                for(var i=SectionSelect.length-1; i >= 0; i--)
                   SectionSelect.remove(i);
                for(var k=sel.length-1;k>=0;k--)
                {
                   for(var j in arSections[sel[k]])
                   {
                      var newOption = new Option(arSections[sel[k]][j], j, false, false);
                      SectionSelect.options.add(newOption);
                   }
                }
             }
          }
          function getSelectedIndexes (oListbox)
          {
             var arrIndexes = new Array;
             for (var i=0; i < oListbox.options.length; i++)
             {
                if (oListbox.options[i].selected) arrIndexes.push(oListbox.options[i].value);
             }
             return arrIndexes;
          };
        </script>
        ';
		}

		$IBLOCK_TYPE = false;
		if($PROP_ID > 0)
		{
			foreach($arProperties[$filterId] as $iblock_id => $props)
			{
				if(array_key_exists($PROP_ID, $props))
				{
					$IBLOCK_ID = $iblock_id;
					break;
				}
			}
			foreach($arIBlocks[$filterId] as $iblock_type_id => $iblocks)
			{
				if(array_key_exists($IBLOCK_ID, $iblocks))
				{
					$IBLOCK_TYPE = $iblock_type_id;
					break;
				}
			}
		}

		$htmlTypeName = htmlspecialcharsbx($strTypeName);
		$htmlIBlockName = htmlspecialcharsbx($strIBlockName);
		$htmlPropertyName = htmlspecialcharsbx($strPropertyName);
		$onChangeType = 'OnType_'.$filterId.'_Changed(this, \''.\CUtil::JSEscape($strIBlockName).'\');'.$onChangeType.';';
		//$onChangeIBlock = trim($onChangeIBlock);
		$onChangeIBlock = 'OnIBlock_'.$filterId.'_Changed(this, \''.\CUtil::JSEscape($strPropertyName).'\');'.$onChangeIBlock.';';

		$html .= '<select name="'.$htmlTypeName.'" id="'.$htmlTypeName.'" onchange="'.htmlspecialcharsbx($onChangeType).'" '.$strAddType.'>'."\n";
		foreach($arTypes[$filterId] as $key => $value)
		{
			if($IBLOCK_TYPE === false)
				$IBLOCK_TYPE = $key;
			$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_TYPE===$key? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
		}
		$html .= "</select>\n";
		$html .= "&nbsp;\n";
		$html .= '<select name="'.$htmlIBlockName.'" id="'.$htmlIBlockName.'"'.($onChangeIBlock != ''? ' onchange="'.htmlspecialcharsbx($onChangeIBlock).'"': '').' '.$strAddIBlock.'>'."\n";
		foreach($arIBlocks[$filterId][$IBLOCK_TYPE] as $key => $value)
		{
			$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($IBLOCK_ID==$key? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
		}
		$html .= "</select>\n";

		$html .="&nbsp;\n";
		$html .= '<select '.$mltp.' name="'.$htmlPropertyName.$ml.'" id="'.$htmlPropertyName.'"'.($onChangeSection != ''? ' onchange="'.htmlspecialcharsbx($onChangeSection).'"': '').' '.$strAddSection.'>'."\n";
		if(is_array($IBLOCK_ID))
		{
			foreach($IBLOCK_ID as $iblock)
			{
				foreach($arProperties[$filterId][$iblock] as $key => $value)
				{
					$html .= '<option value="'.htmlspecialcharsbx($key).'"'.(in_array($key,$PROP_ID) ? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
				}
			}
		}
		else
		{
			if(!empty($arProperties[$filterId][$IBLOCK_ID]))
			{
				foreach($arProperties[$filterId][$IBLOCK_ID] as $key => $value)
				{
					$html .= '<option value="'.htmlspecialcharsbx($key).'"'.($PROP_ID==$key? ' selected': '').'>'.htmlspecialcharsEx($value).'</option>'."\n";
				}
			}
		}

		$html .= "</select>\n";

		return $html;
	}


	public function GetAdminFilterHTML($arProperty, $strHTMLControlName)
	{
		global $APPLICATION;

		$strResult = '';
		$arSettings = self::PrepareSettings($arProperty);
		$arSymbols = self::GetSymbols($arSettings);

		$strValue = '';

		if (isset($_REQUEST[$strHTMLControlName["VALUE"]]) && (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) || (0 < intval($_REQUEST[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arFilterValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');
		}
		elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]) && (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) || (0 < intval($GLOBALS[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arFilterValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');
		}
		ob_start();
		?><?
		$control_id = $APPLICATION->IncludeComponent(
			"bitrix:main.lookup.input",
			"iblockedit",
			array(
				"INPUT_NAME" => $strHTMLControlName['VALUE'].'[]',
				"INPUT_NAME_STRING" => "inp_".$strHTMLControlName['VALUE'],
				"INPUT_VALUE_STRING" => $strValue,
				"START_TEXT" => '',
				"MULTIPLE" => 'Y',
				'MAX_WIDTH' => '200',
				'MIN_HEIGHT' => '24',
				"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
				'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
				'REP_SYM' => $arSymbols['REP_SYM_STRING'],
				'FILTER' => 'Y',
				'TYPE' => 'SECTION',
			), null, array("HIDE_ICONS" => "Y")
		);
		?><input style="float: left; margin-right: 10px;" type="button" value="<? echo Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_SEARCH_ELEMENT'); ?>"
		         title="<? echo Loc::getMessage('BT_UT_SAUTOCOMPLETE_MESS_SEARCH_ELEMENT_MULTI_DESCR'); ?>"
		         onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_section_search.php?lang=<? echo LANGUAGE_ID; ?>&IBLOCK_ID=<? echo $arProperty["LINK_IBLOCK_ID"]; ?>&m=Y&n=&k=&lookup=<? echo 'jsMLI_'.$control_id; ?>', 900, 600);"
	>
		<script type="text/javascript">
			indClearHiddenFields = arClearHiddenFields.length;
			arClearHiddenFields[indClearHiddenFields] = 'jsMLI_<? echo $control_id; ?>';
		</script><?
		$strResult = ob_get_contents();
		ob_end_clean();

		return $strResult;
	}

	public function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
	{
		$filtered = false;

		$arFilterValues = array();

		if (isset($_REQUEST[$strHTMLControlName["VALUE"]]) && (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) || (0 < intval($_REQUEST[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
		}
		elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]) && (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) || (0 < intval($GLOBALS[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));
		}

		foreach ($arFilterValues as $key => $value)
		{
			if (0 >= intval($value))
				unset($arFilterValues[$key]);
		}

		if (!empty($arFilterValues))
		{
			$arFilter["=PROPERTY_".$arProperty["ID"]] = $arFilterValues;
			$filtered = true;
		}
	}
}

?>

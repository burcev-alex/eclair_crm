<?
global $USER, $APPLICATION;
$module_id = "app.integration";
$RIGHT = $APPLICATION->GetGroupRight($module_id);
if($RIGHT >= "W") :

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule($module_id);
CJSCore::Init(array("jquery"));

$arrSites = [
	's1' => 'ECLAIR.DELIVERY',
	's2' => 'СЧАСТЬЕПЕЧЬ.РФ',
	's3' => 'SWEET-ECLAIR.RU' 
];

$arAllOptions =
	array(
		array("debug", "debug", "N", array("text", 10)),
		array("server_rest_api", "server_rest_api", "", array("text", 10))
	);

foreach($arrSites as $siteCode=>$domain){
	$arAllOptions[] = array("site_url_".$siteCode, "site_url_".$siteCode, "", array("text", 10));
	$arAllOptions[] = array("site_host_".$siteCode, "site_host_".$siteCode, "", array("text", 10));
	$arAllOptions[] = array("site_token_".$siteCode, "site_token_".$siteCode, "", array("text", 10));
	$arAllOptions[] = array("site_format_".$siteCode, "site_format_".$siteCode, "", array("text", 10));
	$arAllOptions[] = array("iblock_external_id_".$siteCode, "iblock_external_id_".$siteCode, "", array("text", 10));
}

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("APP_INTEGRATION_TAB_OTHER"), "ICON" => "capp_settings", "TITLE" => GetMessage("APP_INTEGRATION_TAB_OTHER")),
	array("DIV" => "edit3", "TAB" => GetMessage("APP_INTEGRATION_TAB_CRM"), "ICON" => "capp_settings", "TITLE" => GetMessage("APP_INTEGRATION_TAB_CRM")),
);

$aTabs[] = array("DIV" => "edit200", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT=="W" && check_bitrix_sessid())
{
	require_once(__DIR__."/prolog.php");

	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("app.integration");
	}
	else
	{
		foreach($arAllOptions as $arOption)
		{
			$name=$arOption[0];
			$val=$_REQUEST[$name];
			if(is_array($val)) {
				foreach ($val as $keys => $v1) {
					if (strlen($v1) == 0) unset($val[$keys]);
				}
			}
			if(is_array($val)) $val = implode("|", $val);

			if($arOption[2][0]=="checkbox" && $val!="Y") $val="N";
			COption::SetOptionString("app.integration", $name, $val, $arOption[1]);
		}
	}

	ob_start();
	$Update = $Update.$Apply;
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
	ob_end_clean();

	if(strlen($_REQUEST["back_url_settings"]) > 0)
	{
		if((strlen($Apply) > 0) || (strlen($RestoreDefaults) > 0))
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		else
			LocalRedirect($_REQUEST["back_url_settings"]);
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(LANGUAGE_ID)."&".$tabControl->ActiveTabParam());
	}
}

?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
<?
$tabControl->Begin();
$tabControl->BeginNextTab();
?>
    <tr>
        <td valign="top" width="20%">
            Режим отладки:
		</td>
        <td valign="top" width="80%">
            <input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "debug");?>" name="debug">
        </td>
    </tr>
    <tr>
        <td valign="top" width="20%">
            Веб-сервер (TOKEN):
		</td>
        <td valign="top" width="80%">
            <input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "server_rest_api");?>" name="server_rest_api">
        </td>
    </tr>
	<?
	$tabControl->BeginNextTab();

	foreach($arrSites as $siteCode=>$domain){
	?>
	<tr class="heading">
		<td valign="top" colspan="2">
			<?=$domain;?>
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			Веб-сервис (URL):
		</td>
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "site_url_".$siteCode);?>" name="site_url_<?=$siteCode;?>">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			Веб-сервис (HOST):
		</td>
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "site_host_".$siteCode);?>" name="site_host_<?=$siteCode;?>">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			Веб-сервис (TOKEN):
		</td>
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "site_token_".$siteCode);?>" name="site_token_<?=$siteCode;?>">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			Веб-сервис (FORMAT):
		</td>
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "site_format_".$siteCode);?>" name="site_format_<?=$siteCode;?>">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			ID инфоблоков (CatalogId|SkuId):
		</td>
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.integration", "iblock_external_id_".$siteCode);?>" name="iblock_external_id_<?=$siteCode;?>">
		</td>
	</tr>
	<?}?>
	<?$tabControl->BeginNextTab();?>
        <?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
    <?$tabControl->Buttons();?>
	<input <?if ($RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
	<input <?if ($RIGHT<"W") echo "disabled" ?> type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input <?if ($RIGHT<"W") echo "disabled" ?> type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialchars(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialchars($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
	<?=bitrix_sessid_post();?>
<?$tabControl->End();?>
</form>
<?endif;?>

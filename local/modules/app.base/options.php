<?
global $USER, $APPLICATION;
$module_id = "app.base";
$RIGHT = $APPLICATION->GetGroupRight($module_id);
if($RIGHT >= "W") :

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

CModule::IncludeModule($module_id);
CJSCore::Init(array("jquery3"));

$arAllOptions =
	array(
		array("debug", "debug", "N", array("text", 10)),
        array("php_path", "php_path", "", array("text", 10)),
		array("cloudconvert_url", "cloudconvert_url", "", array("text", 10)),
		array("cloudconvert_api", "cloudconvert_api", "", array("text", 10)),
        array("dadatakey", "dadatakey", "", array("text", 10)),
		array("server_rest_api", "server_rest_api", "", array("text", 10)),
		array("server_name_rest_api", "server_name_rest_api", "", array("text", 10)),
		);

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("APP_BASE_OPTIONS_SET"), "ICON" => "capp_settings", "TITLE" => GetMessage("APP_BASE_OPTIONS_TITLE_SET")),
);

$aTabs[] = array("DIV" => "edit8", "TAB" => GetMessage("APP_BASE_OPTIONS_INTEGRATIONS"), "ICON" => "capp_inregrations", "TITLE" => GetMessage("APP_BASE_OPTIONS_INTEGRATIONS"));

$aTabs[] = array("DIV" => "edit7", "TAB" => GetMessage("APP_BASE_OPTIONS_RIGHTS"), "ICON" => "capp_settings", "TITLE" => GetMessage("APP_BASE_OPTIONS_TITLE_RIGHTS"));

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults) > 0 && $RIGHT=="W" && check_bitrix_sessid())
{
	require_once(__DIR__."/prolog.php");

	if(strlen($RestoreDefaults)>0)
	{
		COption::RemoveOption("app.base");
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
			COption::SetOptionString("app.base", $name, $val, $arOption[1]);
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
            <?=GetMessage('APP_BASE_OPTIONS_DEBUG_MODE')?>:
        <td valign="top" width="80%">
            <input type="text" size="60" value="<?=COption::GetOptionString("app.base", "debug");?>" name="debug">
        </td>
    </tr>
    <tr>
        <td valign="top" width="20%">
            <?=GetMessage('APP_BASE_OPTIONS_PHP_PATH')?>:
        <td valign="top" width="80%">
            <input type="text" size="60" value="<?=COption::GetOptionString("app.base", "php_path");?>" name="php_path">
        </td>
    </tr>


	<?$tabControl->BeginNextTab();?>
	<!--
    <tr class="heading">
        <td colspan="2"><b><?=GetMessage('APP_BASE_OPTIONS_CLOUDCONVERT')?></b></td>
    </tr>
	<tr>
		<td valign="top" width="20%">
			Url:
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.base", "cloudconvert_url");?>" name="cloudconvert_url">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			API KEY:
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.base", "cloudconvert_api");?>" name="cloudconvert_api">
		</td>
	</tr>
	-->
    <tr class="heading">
        <td colspan="2"><b><?=GetMessage('APP_BASE_OPTIONS_DADATA')?></b></td>
    </tr>
    <tr>
        <td valign="top" width="20%">
            Токен для сервиса dadata.ru:
        <td valign="top" width="80%">
            <input type="text" size="60" value="<?=COption::GetOptionString("app.base", "dadatakey");?>" name="dadatakey">
        </td>
    </tr>
	<!--
	<tr class="heading">
		<td colspan="2"><b><?=GetMessage('APP_BASE_OPTIONS_SERVER_API')?></b></td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			API SERVER:
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.base", "server_name_rest_api");?>" name="server_name_rest_api">
		</td>
	</tr>
	<tr>
		<td valign="top" width="20%">
			API KEY:
		<td valign="top" width="80%">
			<input type="text" size="60" value="<?=COption::GetOptionString("app.base", "server_rest_api");?>" name="server_rest_api">
		</td>
	</tr>
	-->
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

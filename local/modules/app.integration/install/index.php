<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('app_integration')) {
    return;
}

Class app_integration extends CModule
{
    var $MODULE_ID = "app.integration";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function app_integration()
    {
        $arModuleVersion = array();

        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        include($path."/version.php");


        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = Loc::getMessage('APP_INTEGRATION_MODULE_NAME');
        $this->MODULE_DESCRIPTION = "";
    }

    function InstallFiles($arParams = array())
    {
        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

	/**
	 * Install event
	 *
	 * @return bool
	 */
	public function RegisterEvent()
	{
		RegisterModuleDependences("main", "onPageStart", $this->MODULE_ID, "\\App\\Integration\\Event", "onPageStart");

		return true;
	}

	/**
	 * Uninstall event
	 *
	 * @return bool
	 */
	public function UnRegisterEvent()
	{
		UnRegisterModuleDependences("main", "onPageStart", $this->MODULE_ID, "\\App\\Integration\\Event", "onPageStart");

		return true;
	}

    function DoInstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            ModuleManager::registerModule($this->MODULE_ID);

	        $this->InstallFiles();
	        $this->RegisterEvent();

            $APPLICATION->IncludeAdminFile(Loc::getMessage("APP_BASE_MODULE_INSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/app.integration/install/step.php");
        }
    }

    function DoUninstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            ModuleManager::unregisterModule($this->MODULE_ID);

	        $this->UnInstallFiles();
            $this->UnRegisterEvent();

            $APPLICATION->IncludeAdminFile(Loc::getMessage("APP_BASE_MODULE_UNINSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/app.integration/install/unstep.php");
        }
    }
}
?>
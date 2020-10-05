<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('studiobit_base')) {
    return;
}

Class studiobit_base extends CModule
{
    var $MODULE_ID = "studiobit.base";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function studiobit_base()
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

        $this->MODULE_NAME = Loc::getMessage('STB_BASE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = "";
    }

    function InstallFiles($arParams = array())
    {
	    CopyDirFiles(str_replace("\\", "/", __DIR__)."/public/js", $_SERVER["DOCUMENT_ROOT"]."/local/js", true, true);

	    CopyDirFiles(str_replace("\\", "/", __DIR__)."/public/activities", $_SERVER["DOCUMENT_ROOT"]."/local/activities", true, true);

	    CopyDirFiles(str_replace("\\", "/", __DIR__)."/public/php_interface", $_SERVER["DOCUMENT_ROOT"]."/local/php_interface", true, true);

        return true;
    }

    function UnInstallFiles()
    {
        return true;
    }

    function DoInstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            $this->InstallFiles();
            ModuleManager::registerModule($this->MODULE_ID);

            RegisterModuleDependences("main", "onPageStart", $this->MODULE_ID, "\\Studiobit\\Base\\Event", "onPageStart");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("STB_BASE_MODULE_INSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/studiobit.base/install/step.php");
        }
    }

    function DoUninstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            $this->UnInstallFiles();
            ModuleManager::unregisterModule($this->MODULE_ID);

            UnRegisterModuleDependences("main", "onPageStart", $this->MODULE_ID, "\\Studiobit\\Base\\Event", "onPageStart");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("STB_BASE_MODULE_UNINSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/studiobit.base/install/unstep.php");
        }
    }
}
?>
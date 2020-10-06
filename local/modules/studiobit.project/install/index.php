<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('studiobit_project')) {
    return;
}

Class studiobit_project extends CModule
{
    var $MODULE_ID = "studiobit.project";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function studiobit_project()
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

        $this->MODULE_NAME = Loc::getMessage('STB_PROJECT_MODULE_NAME');
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

    function DoInstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            $this->InstallFiles();
            ModuleManager::registerModule($this->MODULE_ID);


            RegisterModuleDependences("studiobit.base", "setupEventHandlers", $this->MODULE_ID, "\\Studiobit\\Project\\Event", "setupEventHandlers");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("STB_PROJECT_MODULE_INSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/studiobit.project/install/step.php");
        }
    }

    function DoUninstall()
    {
        global $DB, $APPLICATION, $step, $USER;
        if($USER->IsAdmin())
        {
            $this->UnInstallFiles();
            ModuleManager::unRegisterModule($this->MODULE_ID);

            UnRegisterModuleDependences("studiobit.base", "setupEventHandlers", $this->MODULE_ID, "\\Studiobit\\Project\\Event", "setupEventHandlers");

            $APPLICATION->IncludeAdminFile(Loc::getMessage("STB_PROJECT_MODULE_UNINSTALL_DO"), $_SERVER["DOCUMENT_ROOT"]."/local/modules/studiobit.project/install/unstep.php");
        }
    }
}
?>
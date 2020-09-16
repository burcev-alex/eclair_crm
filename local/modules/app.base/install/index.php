<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

if (class_exists('app_base')) {
    return;
}

Class app_base extends CModule
{
    var $MODULE_ID = "app.base";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_PATH;
    var $MODULE_CSS;
    var $MODULE_GROUP_RIGHTS = "Y";

    function __construct()
    {
        $arModuleVersion = array();

        $this->MODULE_PATH = $this->getModulePath();

        include $this->MODULE_PATH.'/install/version.php';

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion[ "VERSION" ];
            $this->MODULE_VERSION_DATE = $arModuleVersion[ "VERSION_DATE" ];
        }

        $this->MODULE_NAME = Loc::getMessage('APP_BASE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = "";
        $this->PARTNER_NAME = Loc::getMessage('APP_BASE_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('APP_BASE_MODULE_PARTNER_URI');
    }

    function InstallFiles($arParams = array())
    {
        $path = $this->MODULE_PATH."/install";

        if ($this->isLocal()) {
            CUrlRewriter::Add(
                [
                    'CONDITION' => '#^/ajax/[A-Za-z0-9_-]+/\\??.*$#',
                    "RULE"      => "",
                    "ID"        => "app.base.router",
                    'PATH'      => '/local/modules/'.$this->MODULE_ID.'/ajax/index.php',
                ]
            );

            CopyDirFiles($path . "/static", $_SERVER["DOCUMENT_ROOT"] . "/local/static", true, true);
        }
        else {
            CUrlRewriter::Add(
                [
                    'CONDITION' => '#^/ajax/[A-Za-z0-9_-]+/\\??.*$#',
                    "RULE"      => "",
                    "ID"        => "app.base.router",
                    'PATH'      => '/bitrix/services/app/ajax.php',
                ]
            );

            CopyDirFiles($path . "/static", $_SERVER["DOCUMENT_ROOT"] . "/bitrix", true, true);
            CopyDirFiles($path . "/services", $_SERVER["DOCUMENT_ROOT"].'/bitrix/services', true, true);
        }
        return true;
    }

    function UnInstallFiles()
    {
        CUrlRewriter::Delete(
            array(
                "ID" => "app.base.router",
            )
        );

        if($this->isLocal()) {
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . "/local/static/js/" . $this->MODULE_ID);
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . "/local/static/css/" . $this->MODULE_ID);
        }
        else{
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . "/bitrix/js/" . $this->MODULE_ID);
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . "/bitrix/css/" . $this->MODULE_ID);
            \Bitrix\Main\IO\Directory::deleteDirectory($_SERVER["DOCUMENT_ROOT"] . "/bitrix/services/app");
        }

        return true;
    }

    function DoInstall($fromModule = false)
    {
        global $APPLICATION, $USER;
        if ($USER->IsAdmin()) {
            $this->InstallFiles();
            ModuleManager::registerModule($this->MODULE_ID);

            if(!$fromModule) {
                $APPLICATION->IncludeAdminFile(Loc::getMessage("APP_BASE_MODULE_INSTALL_DO"),
                    $this->MODULE_PATH . "/install/step.php");
            }
        }
    }

    function DoUninstall()
    {
        global $APPLICATION, $USER;
        if ($USER->IsAdmin()) {
            $this->UnInstallFiles();
            ModuleManager::unregisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(Loc::getMessage("APP_BASE_MODULE_UNINSTALL_DO"),
                $this->MODULE_PATH."/install/unstep.php");
        }
    }

    /**
     * Return path module
     *
     * @return string
     */
    protected function getModulePath()
    {
        $modulePath = explode('/', __FILE__);
        $modulePath = array_slice(
            $modulePath,
            0,
            array_search($this->MODULE_ID, $modulePath) + 1
        );

        return join('/', $modulePath);
    }

    /**
     * Return components path for install
     *
     * @param bool $absolute
     *
     * @return string
     */
    protected function getComponentsPath($absolute = true)
    {
        $documentRoot = getenv('DOCUMENT_ROOT');
        if ($this->isLocal()) {
            $componentsPath = '/local/components';
        } else {
            $componentsPath = '/bitrix/components';
        }

        if ($absolute) {
            $componentsPath = sprintf('%s%s', $documentRoot, $componentsPath);
        }

        return $componentsPath;
    }

    protected function isLocal()
    {
        return strpos($this->MODULE_PATH, 'local/modules') !== false;
    }
}

?>
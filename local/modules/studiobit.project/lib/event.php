<?
namespace Studiobit\Project;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Event
{
    /**
     * Добавляет обработчики событий
     *
     * @return void
     */
    public static function setupEventHandlers()
    {
        if(defined('STUDIOBIT_EVENT_HANDLERS_DISABLED'))
            return;

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        //studiobit.base
        $eventManager->addEventHandler('app.base', 'onRegisterNamespaceForRouter', ['\Studiobit\Project\Handlers\Base', 'onRegisterNamespaceForRouter']);

        //main
        $eventManager->addEventHandler('main', 'OnProlog', ['\Studiobit\Project\Handlers\Main', 'OnProlog']);
        $eventManager->addEventHandler('main', 'onPageStart', ['\Studiobit\Project\Handlers\Main', 'onPageStart']);
        $eventManager->addEventHandler('main', 'onBeforeUserAdd', ['\Studiobit\Project\Handlers\Main', 'onBeforeUserAdd']);
        $eventManager->addEventHandler('main', 'onBeforeUserUpdate', ['\Studiobit\Project\Handlers\Main', 'onBeforeUserUpdate']);
        $eventManager->addEventHandler('main', 'OnBeforeEventAdd', ['\Studiobit\Project\Handlers\Main', 'OnBeforeEventAdd']);

        //pull
        $eventManager->addEventHandler('pull', "OnGetDependentModule", ['\Studiobit\Project\Handlers\Pull', 'OnGetDependentModule']);

        // crm
//        $eventManager->addEventHandler('crm', 'OnAfterCrmControlPanelBuild', ['\Studiobit\Project\Handlers\Crm', 'onAfterCrmControlPanelBuild']);
//        $eventManager->addEventHandler('crm', 'OnAfterCrmLeadAdd', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmLeadAdd']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmLeadAdd', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmLeadAdd']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmLeadUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmLeadUpdate']);

//        $eventManager->addEventHandler('crm', 'OnAfterCrmContactAdd', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmContactAdd']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmContactAdd', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmContactAdd']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmContactUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmContactUpdate']);
//        $eventManager->addEventHandler('crm', 'OnAfterCrmContactUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmContactUpdate']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmDealUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmDealUpdate']);
//        $eventManager->addEventHandler('crm', 'OnAfterCrmDealUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmDealUpdate']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmDealAdd', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmDealAdd']);
//        $eventManager->addEventHandler('crm', 'OnAfterCrmDealAdd', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmDealAdd']);
//        $eventManager->addEventHandler('crm', 'OnActivityAdd', ['\Studiobit\Project\Handlers\Crm', 'OnActivityAdd']);
//        $eventManager->addEventHandler('crm', 'OnActivityUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnActivityUpdate']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmCompanyAdd', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmCompanyAdd']);
//        $eventManager->addEventHandler('crm', 'OnAfterCrmCompanyUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnAfterCrmCompanyUpdate']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmCompanyUpdate', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmCompanyUpdate']);
//        $eventManager->addEventHandler('crm', 'OnBeforeCrmAddEvent', ['\Studiobit\Project\Handlers\Crm', 'OnBeforeCrmAddEvent']);
//        $eventManager->addEventHandler('crm', 'OnGetActivityProviders', ['\Studiobit\Project\Handlers\Crm', 'OnGetActivityProviders']);
//        $eventManager->addEventHandler('crm', '\Bitrix\Crm\Requisite::OnBeforeUpdate', ['\Studiobit\Project\Handlers\Crm', 'RequisiteOnBeforeUpdate']);
//        $eventManager->addEventHandler('crm', '\Bitrix\Crm\Requisite::OnBeforeAdd', ['\Studiobit\Project\Handlers\Crm', 'RequisiteOnBeforeAdd']);

        //agreement
//        $eventManager->addEventHandler('', 'AgreementOnBeforeAdd', ['\Studiobit\Project\Handlers\Agreement', 'onBeforeAdd']);
//        $eventManager->addEventHandler('', 'AgreementOnAfterAdd', ['\Studiobit\Project\Handlers\Agreement', 'onAfterAdd']);
//        $eventManager->addEventHandler('', 'AgreementOnBeforeUpdate', ['\Studiobit\Project\Handlers\Agreement', 'onBeforeUpdate']);
//        $eventManager->addEventHandler('', 'AgreementOnAfterUpdate', ['\Studiobit\Project\Handlers\Agreement', 'onAfterUpdate']);

        //matrix
//        $eventManager->addEventHandler('studiobit.matrix', 'onObjectSetStatus', array('\Studiobit\Project\Handlers\Matrix', 'onObjectSetStatus'));
//        $eventManager->addEventHandler('studiobit.matrix', 'onBeforeObjectSetStatus', array('\Studiobit\Project\Handlers\Matrix', 'onBeforeObjectSetStatus'));
    }
}

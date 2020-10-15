<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Studiobit\Base as Base;

class CBPStudiobitBPCardRemove
	extends \CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array();

	}

	public function Execute()
	{
		if (!Loader::includeModule("studiobit.base"))
			return \CBPActivityExecutionStatus::Closed;

        $rootActivity = $this->GetRootActivity();

        $documentId = $rootActivity->GetDocumentId();
        $arDocumentId = explode('_', $documentId[2]);

		if(intval($arDocumentId[1]) > 0){

            $workflowTemplateId = $rootActivity->GetWorkflowTemplateId();

			$arFields = [
				'UF_ENTITY_TYPE' => $arDocumentId[0],
                'UF_ENTITY_ID' => $arDocumentId[1],
				'UF_TEMPLATE_ID' => $workflowTemplateId
			];

            $filter = $arFields;

            $arFields['UF_WORKFLOW_ID'] = $rootActivity->GetWorkflowInstanceId();
            
            /*if(Base\Entity\BPOptionsTable::getId())
                $filter['=ID'] = Base\Entity\BPOptionsTable::getId();*/

            $entity = new Base\Entity\BPOptionsTable();
            $rsOption = $entity->getList([
                'filter' => $filter,
                'select' => ['ID']
            ]);
            
            if($arOption = $rsOption->fetch()){
                $entity->delete($arOption['ID']);
            }

		}

		return \CBPActivityExecutionStatus::Closed;
	}

	public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
	{
		$arErrors = array();

		return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
	}

	public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
	{
		$runtime = \CBPRuntime::GetRuntime();

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => [],
				"formName" => $formName
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		return true;
	}
}
?>
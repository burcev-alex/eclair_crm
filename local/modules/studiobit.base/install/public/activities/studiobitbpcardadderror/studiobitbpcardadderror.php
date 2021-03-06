<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Studiobit\Base as Base;

class CBPStudiobitBPCardAddError
	extends \CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"error" => ""
		);

	}

	public function Execute()
	{
		if (!Loader::includeModule("studiobit.base"))
			return \CBPActivityExecutionStatus::Closed;

        $rootActivity = $this->GetRootActivity();

        $documentId = $rootActivity->GetDocumentId();
        $arDocumentId = explode('_', $documentId[2]);

		if(intval($arDocumentId[1]) > 0 && strlen($this->error)){

            $workflowTemplateId = $rootActivity->GetWorkflowTemplateId();

			$arFields = [
				'UF_ENTITY_TYPE' => $arDocumentId[0],
                'UF_ENTITY_ID' => $arDocumentId[1],
				'UF_TEMPLATE_ID' => $workflowTemplateId
			];

            $filter = $arFields;

            if(Base\Entity\BPOptionsTable::getId())
                $filter['=ID'] = Base\Entity\BPOptionsTable::getId();

            $entity = new Base\Entity\BPOptionsTable();
            $rsOption = $entity->getList([
                'filter' => $filter,
                'select' => ['ID']
            ]);

            $id = 0;
            if($arOption = $rsOption->fetch()){
                $id = $arOption['ID'];
            }
            else{
                $result = $entity->add($arFields);

                if($result->isSuccess())
                    $id = $result->getId();
            }

            if($id){
                Base\Entity\BPOptionsTable::setId($id);
                Base\Entity\BPOptionsTable::addError($this->error);
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

		if (!\CModule::IncludeModule("socialnetwork"))
			return;

		$arMap = array(
			"error" => "error",
		);

		if (!is_array($arCurrentValues))
		{
			$arCurrentActivity = &\CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
			if (is_array($arCurrentActivity["Properties"]))
			{
				foreach ($arMap as $k => $v)
				{
					if (array_key_exists($k, $arCurrentActivity["Properties"]))
					{
						$arCurrentValues[$arMap[$k]] = $arCurrentActivity["Properties"][$k];
					}
					elseif ($k == "TaskPriority")
					{
						$arCurrentValues[$arMap[$k]] = "1";
					}
					else
					{
						$arCurrentValues[$arMap[$k]] = "";
					}
				}
			}
			else
			{
				foreach ($arMap as $k => $v)
					$arCurrentValues[$arMap[$k]] = "";
			}

		}


		$arGroups = array(GetMessage("TASK_EMPTY_GROUP"));
		$db = \CSocNetGroup::GetList(array("NAME" => "ASC"), array("ACTIVE" => "Y"), false, false, array("ID", "NAME"));
		while ($ar = $db->GetNext())
			$arGroups[$ar["ID"]] = "[".$ar["ID"]."]".$ar["NAME"];

		return $runtime->ExecuteResourceFile(
			__FILE__,
			"properties_dialog.php",
			array(
				"arCurrentValues" => $arCurrentValues,
				"formName" => $formName,
				"arGroups" => $arGroups,
			)
		);
	}

	public static function GetPropertiesDialogValues($documentType, $activityName, &$arWorkflowTemplate, &$arWorkflowParameters, &$arWorkflowVariables, $arCurrentValues, &$arErrors)
	{
		$arErrors = array();

		$arMap = array(
			"error" => "error"
		);

		$arProperties = array();
		foreach ($arMap as $key => $value)
		{
			if ($key == "task_created_by" || $key == "task_assigned_to" || $key == "task_trackers")
				continue;
			$arProperties[$value] = $arCurrentValues[$key];
		}

		$arProperties["TaskCreatedBy"] = \CBPHelper::UsersStringToArray($arCurrentValues["task_created_by"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arProperties["TaskAssignedTo"] = \CBPHelper::UsersStringToArray($arCurrentValues["task_assigned_to"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arProperties["TaskTrackers"] = \CBPHelper::UsersStringToArray($arCurrentValues["task_trackers"], $documentType, $arErrors);
		if (count($arErrors) > 0)
			return false;

		$arErrors = self::ValidateProperties($arProperties, new \CBPWorkflowTemplateUser(\CBPWorkflowTemplateUser::CurrentUser));
		if (count($arErrors) > 0)
			return false;

		$arCurrentActivity = &\CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
		$arCurrentActivity["Properties"] = $arProperties;

		return true;
	}
}
?>
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Studiobit\Base as Base;

class CBPStudiobitHistory
	extends \CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
			"entityType" => "",
            "entityId" => "",
            "eventName" => "",
            "eventText1" => "",
            "eventText2" => "",
            "user" => ""
		);

	}

	public function Execute()
	{
		if (!Loader::includeModule("crm"))
			return \CBPActivityExecutionStatus::Closed;

        $rootActivity = $this->GetRootActivity();

        $documentId = $rootActivity->GetDocumentId();
        $arDocumentId = explode('_', $documentId[2]);

		if(strlen($this->entityType) && $this->entityId > 0 && strlen($this->eventName))
        {
            $crmEvent = new \CCrmEvent();
            $crmEvent->Add([
                'ENTITY_TYPE'=> $this->entityType,
                'ENTITY_ID' => $this->entityId,
                'EVENT_TYPE' => 1,
                'EVENT_NAME' => $this->eventName,
                'EVENT_TEXT_1' => $this->eventText1,
                'EVENT_TEXT_2' => $this->eventText2,
                'USER_ID' => (int)$this->user ? $this->user : $GLOBALS['USER']->GetID()
            ]);
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
            "entityType" => "entityType",
            "entityId" => "entityId",
            "eventName" => "eventName",
            "eventText1" => "eventText1",
            "eventText2" => "eventText2",
            "user" => "user"
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
            "entityType" => "entityType",
            "entityId" => "entityId",
            "eventName" => "eventName",
            "eventText1" => "eventText1",
            "eventText2" => "eventText2",
            "user" => "user"
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
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitValueArray
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
            "Title" => "",
            "Property" => "",
			"Key" => ""
        );
	}

	public function Execute()
	{
        if (!CModule::IncludeModule("studiobit.base"))
            return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

        CModule::IncludeModule("iblock");
        CModule::IncludeModule("crm");
        CModule::IncludeModule("tasks");
        global $USER, $DB;

        if(!is_array($this->Property) > 0){
        	if(substr_count($this->Property, ",") > 0){
				$this->Property = explode(",", $this->Property);
			}
			else if(substr_count($this->Property, ";") > 0){
				$this->Property = explode(";", $this->Property);
			}
			else if(substr_count($this->Property, "|") > 0){
				$this->Property = explode("|", $this->Property);
			}
			else{
				$this->Property = array($this->Property);
			}
        }

		$status = false;

        // найти элемент в массиве
		foreach($this->Property as $key=>$value){
        	if($key == $this->Key){
        		$status = true;
			}
		}

		if($status) {
			$string_status = 1;
		}
		else{
			$string_status = 0;
		}

		$rootActivity->SetVariable("IS_ACTIVE_PROCESS", $string_status);

		return CBPActivityExecutionStatus::Closed;
	}

    public static function ValidateProperties($arTestProperties = array(), CBPWorkflowTemplateUser $user = null)
    {
        $arErrors = array();

        return array_merge($arErrors, parent::ValidateProperties($arTestProperties, $user));
    }

    public static function GetPropertiesDialog($documentType, $activityName, $arWorkflowTemplate, $arWorkflowParameters, $arWorkflowVariables, $arCurrentValues = null, $formName = "")
    {
        $runtime = CBPRuntime::GetRuntime();

        if (!CModule::IncludeModule("socialnetwork"))
            return;

        $arMap = array(
            "Property" => "property",
			"Key" => "key",
        );

        if (!is_array($arWorkflowParameters))
            $arWorkflowParameters = array();
        if (!is_array($arWorkflowVariables))
            $arWorkflowVariables = array();

        if (!is_array($arCurrentValues))
        {
            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
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
        $db = CSocNetGroup::GetList(array("NAME" => "ASC"), array("ACTIVE" => "Y"), false, false, array("ID", "NAME"));
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

        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            "property" => "Property",
			"key" => "Key",
        );

        $arProperties = array();
        foreach ($arMap as $key => $value)
        {
            if ($key == "task_created_by" || $key == "task_assigned_to" || $key == "task_trackers")
                continue;
            $arProperties[$value] = $arCurrentValues[$key];
        }

        $arProperties["TaskCreatedBy"] = CBPHelper::UsersStringToArray($arCurrentValues["task_created_by"], $documentType, $arErrors);
        if (count($arErrors) > 0)
            return false;

        $arProperties["TaskAssignedTo"] = CBPHelper::UsersStringToArray($arCurrentValues["task_assigned_to"], $documentType, $arErrors);
        if (count($arErrors) > 0)
            return false;

        $arProperties["TaskTrackers"] = CBPHelper::UsersStringToArray($arCurrentValues["task_trackers"], $documentType, $arErrors);
        if (count($arErrors) > 0)
            return false;

        $arErrors = self::ValidateProperties($arProperties, new CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser));
        if (count($arErrors) > 0)
            return false;

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity["Properties"] = $arProperties;

        return true;
    }
}
?>
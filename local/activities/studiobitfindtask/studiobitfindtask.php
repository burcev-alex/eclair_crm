<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitFindTask
    extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "ObjectID" => "",
            "Type" => "",
            "TitlePart" => "",
			"Marker" => ""
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

		if($this->Marker != "ALL") {
			$status = 0;
		}
		else{
			$status = array();
		}

        if(IntVal($this->ObjectID) > 0){
            $arFilter = array();
            if($this->Type == "LEAD"){
                $arFilter = array("UF_CRM_TASK"=>array("L_".$this->ObjectID));
            }
            else if($this->Type == "DEAL"){
                $arFilter = array("UF_CRM_TASK"=>array("D_".IntVal($this->ObjectID)));
            }
            else if($this->Type == "STATEMENT"){
                $arFilter = array();
            }
            else if($this->Type == "COMPANY"){
                $arFilter = array("UF_CRM_TASK"=>array("CO_".IntVal($this->ObjectID)));
            }
            else if($this->Type == "CONTACT"){
                $arFilter = array("UF_CRM_TASK"=>array("C_".IntVal($this->ObjectID)));
            }

            $dbResultTask = CTasks::GetList(array("ID"=>"ASC"), $arFilter, array('ID', 'RESPONSIBLE_ID', 'TITLE', 'UF_CRM_TASK', 'CLOSED_BY', 'STATUS'));
            while($arTask = $dbResultTask->Fetch()):
                if(substr_count(htmlspecialchars_decode($arTask["TITLE"]), '"') > 0){
                    $arTask["TITLE"] = str_replace('"', '', htmlspecialchars_decode($arTask["TITLE"]));
                }
                if(substr_count(htmlspecialchars_decode($this->TitlePart), '"') > 0){
                    $this->TitlePart = str_replace('"', '', htmlspecialchars_decode($this->TitlePart));
                }

				if(substr_count(htmlspecialchars_decode($arTask["TITLE"]), trim(htmlspecialchars_decode($this->TitlePart))) > 0){
					if($this->Marker != "ALL"){
						$status = IntVal($arTask["ID"]);
						break;
					}
					else{
						$status[] = IntVal($arTask["ID"]);
					}

				}
            endwhile;
        }

		if($this->Marker == "ALL") {
			$status = implode("_", $status);
		}
		if(strlen($status) == 0) $status = 0;
        
        $rootActivity->SetVariable("FIND_TASK", $status);

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
            "ObjectID" => "object_id",
            "Type" => "type",
            "TitlePart" => "title_part",
			"Marker" => "marker"
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
            "object_id" => "ObjectID",
            "type" => "Type",
            "title_part" => "TitlePart",
			"marker" => "Marker"
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
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitFilesTask
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
            "Title" => "",
            "TaskID" => "",
            "Files" => "",
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

        if(strlen($this->TaskID) > 0){
            if(is_array($this->Files)){
                foreach($this->Files as $fileID){
                    $newFile = CFile::CopyFile($fileID);
                    $arFiles[] = $newFile;
                }
            }
            else{
                $newFile = CFile::CopyFile($this->Files);
                $arFiles[] = $newFile;
            }
            $arFieldsFiles = array(
                "FILES" => $arFiles,
            );
            $oTask = CTaskItem::getInstanceFromPool($this->TaskID, 1);
            $oTask->update($arFieldsFiles);


        }

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
            "TaskID" => "task_id",
            "Files" => "files"
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
            "task_id" => "TaskID",
            "files" => "Files"
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
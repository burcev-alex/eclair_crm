<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitSaveVariables
	extends CBPActivity
{
	public function __construct($name)
	{
		parent::__construct($name);
		$this->arProperties = array(
            "Title" => ""
        );
	}

	public function Execute()
	{
        if (!CModule::IncludeModule("studiobit.base"))
            return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

        $documentId = $rootActivity->GetDocumentId();
        $arDocumentId = explode('_', $documentId[2]);

        $loader = CBPWorkflowTemplateLoader::GetLoader();
        $workflowTemplateId = $rootActivity->GetWorkflowTemplateId();
        $dbTemplatesList = $loader->GetTemplatesList(array(), array("ID" => $workflowTemplateId), false, false, array('DOCUMENT_TYPE', 'VARIABLES'));
        $arTemplatesListItem = $dbTemplatesList->Fetch();

        $BPVariable = new \Studiobit\Base\Entity\BPVariableTable();
        $rsVar = $BPVariable->getList(array(
            'filter' => array(
                'UF_ENTITY_TYPE' => $arDocumentId[0] ,
                'UF_ENTITY_ID' => $arDocumentId[1],
                'UF_TEMPLATE_ID' => $workflowTemplateId
            ),
            'select' => array('ID')
        ));

        while($arVar = $rsVar->Fetch()){
            $BPVariable->delete($arVar['ID']);
        }

        foreach($arTemplatesListItem['VARIABLES'] as $code => $variable)
        {
            $value = $rootActivity->GetVariable($code);

            $fields = array(
                'UF_ENTITY_TYPE' => $arDocumentId[0] ,
                'UF_ENTITY_ID' => $arDocumentId[1],
                'UF_TEMPLATE_ID' => $workflowTemplateId,
                'UF_PARAMS' => serialize($variable),
                'UF_VARIABLE' => $code,
                'UF_VALUE' => array()
            );

            if(!is_array($value))
                $value = array($value);

            if($variable['Type'] == 'file'){
                foreach ($value as $val)
                {
                    $file = \CFile::MakeFileArray($val);
                    $file["MODULE_ID"] = "studiobit";
                    $fields['UF_VALUE'][] = \CFile::SaveFile($file, "studiobit");
                }
                $BPVariable->add($fields);
            }
            else{
                foreach($value as $val){
                    $fields['UF_VALUE'][] = $val;
                }
                $BPVariable->add($fields);
            }
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
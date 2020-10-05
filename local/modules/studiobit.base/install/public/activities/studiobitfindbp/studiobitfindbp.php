<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitFindBP
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
        );
	}

	public function Execute()
	{
        if (!CModule::IncludeModule("studiobit.base"))
            return CBPActivityExecutionStatus::Closed;

		$rootActivity = $this->GetRootActivity();

        CModule::IncludeModule("bizproc");
        CModule::IncludeModule("crm");
        global $USER, $DB;

        $status = 0;
        if(strlen($this->ObjectID) > 0){
            // определить какой статус котировки
            $arDealItem = CCrmDeal::GetList(array("ID"=>"AC"), array("ID"=>IntVal($this->ObjectID)), array("ID", "TITLE", "STAGE_ID"))->GetNext();
            $realStatus = $arDealItem["STAGE_ID"];

            if($this->Type == "DEAL"){
                if(substr_count($this->TitlePart, "|") > 0){
                    $arListFindText = explode("|", $this->TitlePart);
                }
                else{
                    $arListFindText = array($this->TitlePart);
                }

                foreach($arListFindText as $findText){
                	if(strlen($findText) == 0) continue;
                    $dbResTmp = CBPTaskService::GetList(
                        array("NAME" => "ASC"),
                        array("DOCUMENT_ID"=>$this->Type."_".$this->ObjectID),
                        false,
                        false,
                        array("ID", "NAME", "DOCUMENT_ID", "DESCRIPTION", "ENTITY", "STATUS")
                    );
                    while ($arResTmp = $dbResTmp->GetNext()){

						if(substr_count($arResTmp["NAME"], $findText) == 0){
							if(substr_count($arResTmp["DESCRIPTION"], $findText) == 0){
								continue;
							}
						}

                        $arUsers = array();
                        $dbResUser = $DB->Query("SELECT USER_ID FROM b_bp_task_user WHERE TASK_ID = ".intval($arResTmp["ID"]));
                        while ($arResUser = $dbResUser->Fetch())
                        {
                            $arUserInfo = CUser::GetByID($arResUser["USER_ID"])->Fetch();
                            $arGroups = CUser::GetUserGroup($arResUser["USER_ID"]);

                            $rsGroupUser = array();
                            foreach($arGroups as $k=>$group){
                                $rsGroup = CGroup::GetByID($group)->Fetch();
                                $rsGroupUser[] = $rsGroup["NAME"];
                            }

                            $arUsers[] = array("ID"=>$arResUser["USER_ID"], "FULL_NAME"=>$arUserInfo["NAME"]." ".$arUserInfo["LAST_NAME"], "GROUP"=>$rsGroupUser);
                        }

                        if(count($arUsers) > 0) $status++;

                    }

                    if($status > 0) break;
                }
            }

            $rootActivity->SetVariable("FIND_BP", $status);

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
            "ObjectID" => "object_id",
            "Type" => "type",
            "TitlePart" => "title_part",
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
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

class CBPStudiobitTransferActivity
    extends CBPActivity
{
    public function __construct($name)
    {
        parent::__construct($name);
        $this->arProperties = array(
            "Title" => "",
            "ObjectFromID" => "",
            "ObjectToID" => "",
            "DeleteFrom" => ""
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
        global $USER, $DB, $APPLICATION;

        if(IntVal($this->ObjectFromID) > 0){
			$objActivity = new \CCrmActivity();
			$arFilter = array("=OWNER_ID"=>IntVal($this->ObjectFromID), "OWNER_TYPE_ID"=>CCrmOwnerType::Lead);
			#AddMessage2Log($arFilter);

			$rsActivity = \CCrmActivity::GetList(array("ID"=>"ASC"), $arFilter, false, false, array("ID", "SUBJECT", "TYPE_ID", "RESPONSIBLE_ID", "PRIORITY", "COMPLETED", "START_TIME", "END_TIME", "OWNER_TYPE_ID", "OWNER_TYPE_NAME", "OWNER_ID", "DEADLINE", "SETTINGS", "DESCRIPTION", "LOCATION", "CREATED", "LAST_UPDATED", "ASSOCIATED_ENTITY_ID", "NOTIFY_TYPE", "NOTIFY_VALUE", "DESCRIPTION_TYPE", "DIRECTION", "PARENT_ID", "AUTHOR_ID", "ORIGINATOR_ID", "SEARCH_CONTENT"));
			$i = 0;
			if(is_object($rsActivity))
			{
				while($arActivity = $rsActivity->Fetch())
				{
					$arBindings = array(
						array(
							'OWNER_TYPE_ID' => CCrmOwnerType::Company,
							'OWNER_ID' => IntVal($this->ObjectToID)
						)
					);

					// проверка даты обновления дела
					// если задача закрылась в последние 5сек, - это авто закрытие
					if((mktime() - strtotime($arActivity["LAST_UPDATED"])) < 6){
						$arActivity["COMPLETED"] = "N";
					}

					$arFields = array(
						"OWNER_TYPE_ID" => CCrmOwnerType::Company,
						"OWNER_TYPE_NAME" => "COMPANY",
						"OWNER_ID" => IntVal($this->ObjectToID),
						"DEADLINE" => $arActivity["DEADLINE"],
						"SUBJECT" => $arActivity["SUBJECT"],
						"TYPE_ID" => $arActivity["TYPE_ID"],
						"RESPONSIBLE_ID" => $arActivity["RESPONSIBLE_ID"],
						"PRIORITY" => $arActivity["PRIORITY"],
						"COMPLETED" => $arActivity["COMPLETED"],
						"START_TIME" => $arActivity["START_TIME"],
						"END_TIME" => $arActivity["END_TIME"],
						"SETTINGS" => $arActivity["SETTINGS"],
						"DESCRIPTION" => $arActivity["DESCRIPTION"],
						"LOCATION" => $arActivity["LOCATION"],
						"CREATED" => $arActivity["CREATED"],
						"LAST_UPDATED" => $arActivity["LAST_UPDATED"],
						#"ASSOCIATED_ENTITY_ID" => $arActivity["ASSOCIATED_ENTITY_ID"],
						"NOTIFY_TYPE" => $arActivity["NOTIFY_TYPE"],
						"NOTIFY_VALUE" => $arActivity["NOTIFY_VALUE"],
						"DESCRIPTION_TYPE" => $arActivity["DESCRIPTION_TYPE"],
						"DIRECTION" => $arActivity["DIRECTION"],
						#"PARENT_ID" => $arActivity["PARENT_ID"],
						"AUTHOR_ID" => $arActivity["AUTHOR_ID"],
						#"ORIGINATOR_ID" => $arActivity["ORIGINATOR_ID"],
						"SEARCH_CONTENT" => $arActivity["SEARCH_CONTENT"],
						'BINDINGS' => array_values($arBindings),
					);

					#AddMessage2Log($arFields);

					if($ID = $objActivity->Add($arFields, false, false, array('REGISTER_SONET_EVENT' => true))){

						$urn = \CCrmActivity::PrepareUrn($arFields);
						if($urn !== '')
						{
							\CCrmActivity::Update($ID, array('URN'=> $urn), false, false, array('REGISTER_SONET_EVENT' => true));
						}

						$i++;
					}
					else {
						AddMessage2Log($APPLICATION->LAST_ERROR);
					}
				}
			}
			echo $i;
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
            "ObjectFromID" => "object_from_id",
            "ObjectToID" => "object_to_id",
            "DeleteFrom" => "delete_from"
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
			"object_from_id" => "ObjectFromID",
			"object_to_id" => "ObjectToID",
			"delete_from" => "DeleteFrom"
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
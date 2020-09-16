<?

namespace App\Base;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Sale\Location\Exception;

Loc::loadMessages(__FILE__);

class Tools
{
    /**
	 * Возвращает елемент по ID вместе со всеми свойствами в том числе получает пути к файлам,
	 * и добавляет связанные элементы в массив LINKED_ELEMENT - $maxCoherenceDepth -
	 * максимальная глубина линковки связанных элементов
	 *
	 * @param $ID
	 * @param bool $ignoreEmpty
	 * @param int $maxCoherenceDepth
	 * @param int $coherenceDepth
	 *
     * @throws Main\LoaderException
	 * @return mixed
	 */
	public static function getElementByIDWithProps($ID, $ignoreEmpty = true, $maxCoherenceDepth = 1, $coherenceDepth = 1)
	{
		Loader::includeModule("iblock");

		$res = \CIBlockElement::GetByID($ID);
		$arResult = $res->Fetch();

		$db_props = \CIBlockElement::GetProperty($arResult['IBLOCK_ID'], $ID, 'sort', 'asc');
		while ($ar_props = $db_props->Fetch()) {
			if (!($ignoreEmpty && empty($ar_props['VALUE']))) {
				// преобразование некоторых свойств
				if ($ar_props['PROPERTY_TYPE'] == 'F') {
					$ar_props['PATH'] = \CFile::GetPath($ar_props['VALUE']);
					$ar_props['FILE_PARAM'] = \CFile::GetFileArray($ar_props['VALUE']);
				} else if ($ar_props['PROPERTY_TYPE'] == 'E') {
					if ($coherenceDepth < $maxCoherenceDepth) {
						$ar_props['LINKED_ELEMENT'] = self::getElementByIDWithProps($ar_props['VALUE'], $ignoreEmpty, $maxCoherenceDepth, $coherenceDepth);
						$coherenceDepth = $coherenceDepth + 1;
					}
				}
				// добавление свойств в массив результата
				if ($ar_props['MULTIPLE'] == 'Y') {
					$arResult['PROPERTIES'][$ar_props['CODE']][] = $ar_props;
					$arResult['PROPERTIES_VALUE'][$ar_props['CODE']][] = $ar_props['VALUE'];
				} else {
					$arResult['PROPERTIES'][$ar_props['CODE']] = $ar_props;
					$arResult['PROPERTIES_VALUE'][$ar_props['CODE']] = $ar_props['VALUE'];
				}
			}
		}

		return $arResult;
	}

	/**
	 * Возвращает список вариантов значений свойств типа "список"
	 *
	 * @param $IBLOCK_ID
	 * @param $PROPERTY_CODE
	 *
     * @throws Main\LoaderException
	 * @return array
	 */
	public static function getListPropertyEnum($IBLOCK_ID, $PROPERTY_CODE)
	{
		\CModule::IncludeModule("iblock");
		$result = array();
		$obCache = new \CPHPCache;
		$cache_id = md5('getListPropertyEnum|' . $IBLOCK_ID . "|" . $PROPERTY_CODE . "|");
		if ($obCache->InitCache(3600000, $cache_id, "/sys/list_prop/" . $IBLOCK_ID . "/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			$property_enums = \CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("IBLOCK_ID" => $IBLOCK_ID, "CODE" => $PROPERTY_CODE));
			while ($enum_fields = $property_enums->GetNext()) {
				$result[] = $enum_fields;
			}
			$obCache->EndDataCache(array("FIELD" => $result));
		}

		return $result;
	}

	/**
	 * Возвращает ID значения свойства типа "список"
	 *
	 * @param $IBLOCK_ID
	 * @param $PROPERTY_CODE
	 * @param $VALUE
	 *
     * @throws Main\LoaderException
	 * @return array|int
	 */
	public static function getIdPropertyEnum($IBLOCK_ID, $PROPERTY_CODE, $VALUE)
	{
		\CModule::IncludeModule("iblock");
		$result = 0;
        $VALUE = strtolower($VALUE);
		$obCache = new \CPHPCache;
		$cache_id = md5('getIdPropertyEnum|' . $IBLOCK_ID . "|" . $PROPERTY_CODE . "|" . $VALUE);
		if ($obCache->InitCache(3600000, $cache_id, "/sys/id_enum_prop/" . $IBLOCK_ID . "/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			$property_enums = \CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("IBLOCK_ID" => $IBLOCK_ID, "CODE" => $PROPERTY_CODE));
			while ($enum_fields = $property_enums->GetNext()) {
				if (strtolower($enum_fields["VALUE"]) != $VALUE) {
					continue;
				}
				$result = $enum_fields["ID"];
			}

			$obCache->EndDataCache(array("FIELD" => $result));
		}

		return $result;
	}

    /**
     * Возвращает ID значения свойства типа "список" по XML_ID
     *
     * @param $IBLOCK_ID
     * @param $PROPERTY_CODE
     * @param $VALUE
     *
     * @return array|int
     */
    public static function getIdPropertyEnumByXml($IBLOCK_ID, $PROPERTY_CODE, $XML_ID)
    {
        \CModule::IncludeModule("iblock");
        $result = 0;
        $obCache = new \CPHPCache;
        $cache_id = md5('getIdPropertyEnumByXml|' . $IBLOCK_ID . "|" . $PROPERTY_CODE . "|" . $XML_ID . "|v2");
        if ($obCache->InitCache(0, $cache_id, "/sys/id_enum_prop/" . $IBLOCK_ID . "/")) {
            $vars = $obCache->GetVars();
            $result = $vars['FIELD'];
        }
        if ($obCache->StartDataCache()) {
            $property_enums = \CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("IBLOCK_ID" => $IBLOCK_ID, "CODE" => $PROPERTY_CODE, 'EXTERNAL_ID' => $XML_ID));

            if ($enum_fields = $property_enums->GetNext()) {
                $result = $enum_fields["ID"];
            }

            $obCache->EndDataCache(array("FIELD" => $result));
        }

        return $result;
    }

	/**
	 * Возвращает id пользовательского свойства типа "список", по значниею
	 *
	 * @param $UF_PROPERTY_CODE
	 * @param $value
	 * @param int $userFieldID
	 * @param string $entity
	 *
	 * @return string
	 */
	public static function getIDInUFPropEnum($UF_PROPERTY_CODE, $value, $userFieldID = 0, $entity = "")
	{
		$result = "";
		$obCache = new \CPHPCache;
		$cache_id = md5('getIDInUFPropEnum|' . $UF_PROPERTY_CODE . "|" . $value . "|" . $userFieldID . "|". $entity);
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			if ($value != "") {
				$arFilter = array("USER_FIELD_NAME" => $UF_PROPERTY_CODE, "VALUE" => $value);
				if (intval($userFieldID) > 0) {
					$arFilter["USER_FIELD_ID"] = $userFieldID;
				}
				elseif (strlen($entity) > 0) {
                    $rs = \CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => $UF_PROPERTY_CODE]);
                    if($ar = $rs->Fetch())
					    $arFilter["USER_FIELD_ID"] = $ar['ID'];
				}

                $enum = new \CUserFieldEnum();
				$enumFields = $enum->GetList(
					array("ID" => "DESC"),
					$arFilter
				);

				if ($arEnum = $enumFields->GetNext()) {
                    $result = $arEnum["ID"];
				}
			}

			$obCache->EndDataCache(array("FIELD" => $result));
		}

		return $result;
	}
	
	/**
	 * Возвращает id пользовательского свойства типа "список", по XML_ID
	 *
	 * @param $UF_PROPERTY_CODE
	 * @param $value
	 * @param xml_id $userFieldID
	 * @param string $entity
	 *
	 * @return string
	 */
	public static function getIDInUFPropEnumByXml($UF_PROPERTY_CODE, $xml_id, $userFieldID = 0, $entity = "")
	{
		$result = "";
		$obCache = new \CPHPCache;
		$cache_id = md5('getIDInUFPropEnumByXml|' . $UF_PROPERTY_CODE . "|" . $xml_id . "|" . $userFieldID);
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			if ($xml_id != "") {
				$arFilter = array("USER_FIELD_NAME" => $UF_PROPERTY_CODE, "XML_ID" => $xml_id);
				if (intval($userFieldID) > 0) {
					$arFilter["USER_FIELD_ID"] = $userFieldID;
				}
				if (strlen($entity) > 0) {
                    $rs = \CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => $UF_PROPERTY_CODE]);
                    if($ar = $rs->Fetch())
                        $arFilter["USER_FIELD_ID"] = $ar['ID'];
				}

                $enum = new \CUserFieldEnum();
				$enumFields = $enum->GetList(
					array("ID" => "DESC"),
					$arFilter
				);

				while ($arEnum = $enumFields->GetNext()) {
					$result = $arEnum["ID"];
				}
			}

			$obCache->EndDataCache(array("FIELD" => $result));
		}

		return $result;
	}

	/**
	 * ID свойства инфоблока по символьному коду
	 * @param $iblockId
	 * @param $code
	 *
	 * @return int
	 */
	public static function getPropertyIdByCode($iblockId, $code){
		$result = 0;

		\CModule::IncludeModule("iblock");
		$properties = \CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockId, "CODE" => $code));
		if ($arProperty = $properties->Fetch()) {
			$result = $arProperty["ID"];
		}

		return $result;
	}

	/**
	 * Возвращает value пользовательского свойства типа "список", по id
	 *
	 * @param $UF_PROPERTY_CODE
	 * @param $id
	 *
	 * @return string
	 */
	public static function getValueInUFPropEnumID($UF_PROPERTY_CODE, $id, $userFieldID = 0, $entity = "")
	{
		$result = "";
		$obCache = new \CPHPCache;
        $cache_id = md5('getValueInUFPropEnumID|' . $UF_PROPERTY_CODE . "|" . $id . "|" . $userFieldID. "|" . $entity);
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum_id/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			if ($id != "") {
                $arFilter = array("USER_FIELD_NAME" => $UF_PROPERTY_CODE, "ID" => $id);
                if (intval($userFieldID) > 0) {
                    $arFilter["USER_FIELD_ID"] = $userFieldID;
                }
                if (strlen($entity) > 0) {
                    $rs = \CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => $UF_PROPERTY_CODE]);
                    if($ar = $rs->Fetch())
                        $arFilter["USER_FIELD_ID"] = $ar['ID'];
                }

				$enumFields = \CUserFieldEnum::GetList(
					array(),
                    $arFilter
				);

				if ($arEnum = $enumFields->GetNext()) {
					$result = $arEnum["VALUE"];
				}
			}

			$obCache->EndDataCache(array("FIELD" => $result));
		}

		return $result;
	}

	/**
	 * Возвращает массив пользовательских свойств типа "список", по фильтру
	 *
	 * @param $UF_PROPERTY_CODE
	 * @param $arFilter
	 *
	 * @return id
	 */
	public static function getUFPropEnum($UF_PROPERTY_CODE, $arFilter = array(), $userFieldID = 0, $entity = "")
	{
		$arEnumFields = array();
		$obCache = new \CPHPCache;
		$cache_id = md5('getUFPropEnum|' . $UF_PROPERTY_CODE . "|" . serialize($arFilter) . "|");
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum_list/")) {
			$vars = $obCache->GetVars();
			$arEnumFields = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
            if (intval($userFieldID) > 0) {
                $arFilter["USER_FIELD_ID"] = $userFieldID;
            }
            if (strlen($entity) > 0) {
                $rs = \CUserTypeEntity::GetList([], ['ENTITY_ID' => $entity, 'FIELD_NAME' => $UF_PROPERTY_CODE]);
                if($ar = $rs->Fetch())
                    $arFilter["USER_FIELD_ID"] = $ar['ID'];
            }

			$enumFields = \CUserFieldEnum::GetList(
				array(),
				array_merge(array("USER_FIELD_NAME" => $UF_PROPERTY_CODE), $arFilter)
			);
			$arEnumFields = array();
			while ($arEnum = $enumFields->GetNext()) {
				$arEnumFields[] = $arEnum;
			}

			$obCache->EndDataCache(array("FIELD" => $arEnumFields));
		}

		return $arEnumFields;
	}

	/**
	 * Список значений из инфоблока по фильтру
	 *
	 * @param array $filter
	 * @param array $select
	 *
	 * @return array
	 */
	public static function getValuesFromIblock($filter = array(), $select = array("ID", "NAME"), $pageSize = false, $cached = true)
	{

		\CModule::IncludeModule("iblock");

		$result = array();

		$arSize = array();
		if ($pageSize) {
			$arSize = array("nPageSize" => $pageSize);
		}

		if ($cached == true) {
			$obCache = new \CPHPCache;
			$cache_id = md5('GetValuesFromDirectory' . serialize($filter) . serialize($select) . serialize($pageSize));

			if ($obCache->InitCache(1, $cache_id, "/sys/list_directory/")) {
				$result = $obCache->GetVars();
			}

			if ($obCache->StartDataCache()) {
				$res = \CIBlockElement::GetList(array(), $filter, false, $arSize, $select);
				while ($ob = $res->GetNext()) {
					$result[] = $ob;
				}

				$obCache->EndDataCache($result);
			}
		} else {
			$res = \CIBlockElement::GetList(array(), $filter, false, $arSize, $select);
			while ($ob = $res->GetNext()) {
				$result[] = $ob;
			}
		}

		return $result;
	}


	/**
	 * Выборка дерева подразделов для раздела
	 *
	 * @param $SECTION_ID
	 * @param string $format
	 * @param bool $add_current
	 * @param array $arFields - дополнительные данные по текущему разделу
	 *
	 * @return array
	 */
	public static function getListSectionChild($SECTION_ID, $format = "small", $add_current = true, $arFields = array())
	{
		\CModule::IncludeModule("iblock");
		$LIST_FIELD = array();

		if (!isset($SECTION_ID)) {
			$SECTION_ID = -1;
		}

		$obCache = new \CPHPCache;
		$cache_id = md5('getListSectionChild_' . $SECTION_ID . '_' . $format . '_' . $add_current);
		if ($obCache->InitCache(3600000, $cache_id, "/sys/section_child/")) {
			$vars = $obCache->GetVars();
			$LIST_FIELD = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			$LIST_FIELD = array();

			$arSelect = array();
			if ($format == "small") {
				$arSelect = array(
					"ID",
					"IBLOCK_ID",
					"LEFT_MARGIN",
					"RIGHT_MARGIN",
					"DEPTH_LEVEL",
					"IBLOCK_SECTION_ID"
				);
			}

			if (count($arFields) == 0) {
				$arParentSection = \CIBlockSection::GetList(array("ID" => "ASC"), array("ID" => $SECTION_ID), false, $arSelect)->GetNext();
			} else {
				$arParentSection = $arFields;
			}
			if (is_array($arParentSection)) {
				if ($add_current) {
					if ($format == "full") {
						$LIST_FIELD[] = $arParentSection;
					} else {
						$LIST_FIELD[] = $arParentSection["ID"];
					}
				}
				$arFilter = array('IBLOCK_ID' => $arParentSection['IBLOCK_ID'], '>LEFT_MARGIN' => $arParentSection['LEFT_MARGIN'], '<RIGHT_MARGIN' => $arParentSection['RIGHT_MARGIN'], '>DEPTH_LEVEL' => $arParentSection['DEPTH_LEVEL']); // выберет потомков без учета активности
				$rsSect = \CIBlockSection::GetList(array('left_margin' => 'asc'), $arFilter, false, $arSelect);
				while ($arSect = $rsSect->GetNext()) {
					if ($format == "full") {
						$LIST_FIELD[] = $arSect;
					} else {
						$LIST_FIELD[] = $arSect["ID"];
					}
				}
			}

			$obCache->EndDataCache(array("FIELD" => $LIST_FIELD));
		}

		return $LIST_FIELD;
	}

	/**
	 * Все родители конкретного раздела
	 *
	 * @param int $section_id
	 *
	 * @return array
	 */
	public function getParentSection($section_id = 0)
	{
		\CModule::IncludeModule("iblock");
		$result = array();

		if (!is_array($section_id)) {
			$nav = \CIBlockSection::GetNavChain(\COption::GetOptionInt('intranet', 'iblock_structure'), $section_id, array("ID"));
			while ($arSectionPath = $nav->GetNext()) {
				$result[] = $arSectionPath["ID"];
			}
		} else {
			foreach ($section_id as $sect) {
				$nav = \CIBlockSection::GetNavChain(\COption::GetOptionInt('intranet', 'iblock_structure'), $sect, array("ID"));
				while ($arSectionPath = $nav->GetNext()) {
					$result[] = $arSectionPath["ID"];
				}
			}
		}

		$result = array_unique($result);

		return $result;
	}

	/**
	 * Пренадлежит пользователь к конкретной группе?
	 *
	 * @param $group_code
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public static function isUserOfGroup($group_code, $user_id = 0)
	{
		$result = false;
		global $USER;

		if ($user_id == 0) {
			$user_id = $USER->GetID();
		}

		if (!is_array($group_code)) {
			$group_id = 0;
			// по коду $group_code определяем ID
			$itemGroup = self::getGroupByCode($group_code);
			if (is_array($itemGroup)) {
				$group_id = $itemGroup["ID"];
			}
		} else {
			$group_id = array();

			// по коду $group_code определяем ID
			$itemGroup = self::getGroupByCode($group_code);
			foreach ($itemGroup as $item) {
				$group_id[] = $item["ID"];
			}
		}

		if (IntVal($user_id) == 0) {
			$arGroups = $USER->GetUserGroupArray();
		} else {
			// получим массив групп текущего указанного пользователя
			$arGroups = $USER->GetUserGroup($user_id);
		}

		if (!is_array($group_code)) {
			if (in_array($group_id, $arGroups)) {
				$result = true;
			}
		} else {
			foreach ($group_id as $group) {
				if (in_array($group, $arGroups)) {
					$result = true;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Возвращает группу по символьному идентификатору
	 *
	 * @param $code
	 *
	 * @return array
	 */
	public static function getGroupByCode($code)
	{
		$result = array();
		if (is_array($code)) {
			$arFilter = array("STRING_ID" => implode("|", $code));
		} else {
			$arFilter = array("STRING_ID" => $code);
		}

		$rsGroups = \CGroup::GetList($by = "c_sort", $order = "asc", $arFilter);
		while ($ar = $rsGroups->Fetch()) {
			if (!is_array($code)) {
				$result = $ar;
			} else {
				$result[] = $ar;
			}
		}

		return $result;
	}

	/**
	 * Кодирование файла в массив байт
	 *
	 * @param $pathFile
	 *
	 * @return string
	 */
	public static function base64Encode($pathFile)
	{
		$fileData = base64_encode(file_get_contents($pathFile));

		// Format the image SRC:  data:{mime};base64,{data};
		$src = 'data: ' . mime_content_type($pathFile) . ';base64,' . $fileData;

		return $src;
	}

	/**
	 * Вовращает слово во всех падежах
	 *
	 * @param $text
	 *
	 * @return mixed
	 */
	public static function morpherInflect($text, $case = '')
	{
		$result = array();
		$httpClient = new \Bitrix\Main\Web\HttpClient();
		$response = $httpClient->get('http://ws3.morpher.ru/russian/declension/?s=' . urlencode($text));
		$xml = simplexml_load_string($response);

		foreach ((array)$xml as $key => $val) {
			if (!is_object($val)) {
				$result[$key] = $val;
			}
		}

		if (!empty($case)) {
			return $result[$case];
		}

		return $result;
	}

	/**
	 * Разницп двух дат
	 *
	 * @param $date_start
	 * @param $date_end
	 * @param string $type
	 * @param string $round
	 *
	 * @return int
	 */
	public static function DiffDate($date_start, $date_end, $type = "m", $round = "floor"){
		$result = 0;
		if(substr_count($date_start, ".") > 0){
			$datetime1 = strtotime($date_start);
			$datetime2 = strtotime($date_end);
		}
		else{
			$datetime1 = $date_start;
			$datetime2 = $date_end;
		}
		$diff = $datetime2-$datetime1;

		if($round == "floor") $nameFN = "floor"; else $nameFN = $round;

		if($type == "d"){
			$result = $nameFN($diff/(60*60*24));
		}
		else if($type == "m"){
			if($round == "floor"){
				$result = $nameFN($diff/(30*60*60*24));
			}
			else if($round == "ceil"){
				$datetime1 = date("m", $datetime1);
				if(substr($datetime1, 0, 1) == "0"){
					$datetime1 = IntVal(str_replace("0", "", $datetime1));
				}
				else{
					$datetime1 = IntVal($datetime1);
				}

				$datetime2 = date("m", $datetime2);
				if(substr($datetime2, 0, 1) == "0"){
					$datetime2 = IntVal(str_replace("0", "", $datetime2));
				}
				else{
					$datetime2 = IntVal($datetime2);
				}

				$result =$datetime2-$datetime1+1;
			}
		}
		else if($type == "y"){
			$result = $nameFN($diff/(12*30*60*60*24));
		}

		return $result;
	}

    /**
     * Функция склонения числительных в русском языке
     *
     * @param int    $number Число которое нужно просклонять
     * @param array  $titles Массив слов для склонения
     * @return string
     **/
    //$titles = array('котик', 'котика', 'котиков');
    public static function declOfNum($number, $titles)
    {
        $cases = array (2, 0, 1, 1, 1, 2);
        return $number." ".$titles[ ($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)] ];
    }

    /**
     * Возвращает ID пользователей с ролью $role
     * @param $role - название или ID роли или массив названий, ID ролей
     * @return array
     * @throws Main\LoaderException
     */
    public static function getUsersByRoleName($role){
        if(!Loader::includeModule('crm'))return [];
        if(!is_array($role))
            $role = [$role];

        foreach($role as $key => &$r) {
            if(!intval($r)) {
                $rsRole = \CCrmRole::GetList([], ['NAME' => $r]);
                if ($arRole = $rsRole->Fetch()) {
                    $r = $arRole['ID'];
                }
                else{
                    unset($role[$key]);
                }
            }
        }


        $return = [];

        if(!empty($role))
        {
            global $DB;
            $res = $DB->Query(
                'SELECT RR.RELATION AS RELATION FROM b_crm_role_relation RR WHERE RR.ROLE_ID IN (' . implode(',', $role) . ')',
                false,
                'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__
            );

            while ($ar = $res->Fetch())
            {
                if(strpos($ar['RELATION'], 'DR') === 0){
                    $departmentId = str_replace('DR', '', $ar['RELATION']);
                    foreach(self::getUsersByDepartment($departmentId, false, true) as $id){
                        $return[$id] = $id;
                    }
                }
                elseif(strpos($ar['RELATION'], 'D') === 0){
                    $departmentId = str_replace('D', '', $ar['RELATION']);
                    foreach(self::getUsersByDepartment($departmentId, false, false) as $id){
                        $return[$id] = $id;
                    }
                }
                elseif(strpos($ar['RELATION'], 'IU') === 0){
                    $userId = str_replace('IU', '', $ar['RELATION']);
                    if($userId > 0)
                        $return[$userId] = $userId;
                }
                elseif(strpos($ar['RELATION'], 'U') === 0){
                    $userId = str_replace('U', '', $ar['RELATION']);
                    if($userId > 0)
                        $return[$userId] = $userId;
                }
            }

            $res = $DB->Query(
                'SELECT UA.USER_ID FROM b_crm_role_relation RR INNER JOIN b_user_access UA ON UA.ACCESS_CODE = RR.RELATION WHERE RR.ROLE_ID IN (' . implode(',', $role) . ')',
                false,
                'FILE: ' . __FILE__ . '<br /> LINE: ' . __LINE__
            );

            while ($ar = $res->Fetch()) {
                $return[$ar['USER_ID']] = $ar['USER_ID'];
            }
        }

        return array_values($return);
    }

    /**
     * @param $departmentId
     * @param bool $withHeads
     * @param bool $recursive
     * @return array
     * @throws Main\LoaderException
     */
    public static function getUsersByDepartment($departmentId, $withHeads = true, $recursive = false)
    {
        $return = [];

        if(!Loader::includeModule('iblock'))
            return $return;
        
        $iblockId = intval(\COption::GetOptionInt('intranet', 'iblock_structure'));
        
        $departmentIDs = [$departmentId];

        if($recursive || $withHeads) 
        {
            $rsSection = \CIBlockSection::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    'ID'   => $departmentId
                ],
                false,
                ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'IBLOCK_ID', 'UF_HEAD']
            );
            
            if($arSection = $rsSection->Fetch()) {
                if ($arSection['UF_HEAD'] > 0 && $withHeads){
                    $return[$arSection['UF_HEAD']] = $arSection['UF_HEAD'];
                }
            
                if($recursive){
                    $rsChild = \CIBlockSection::GetList(
                        [],
                        [
                            'IBLOCK_ID' => $iblockId,
                            '>=LEFT_MARGIN'   => $arSection['LEFT_MARGIN'],
                            '<=RIGHT_MARGIN'   => $arSection['RIGHT_MARGIN']
                        ],
                        false,
                        ['ID', 'IBLOCK_ID', 'UF_HEAD']
                    );
                    
                    while($arChild = $rsChild->Fetch())
                    {
                        if ($arChild['UF_HEAD'] > 0 && $withHeads){
                            $return[$arChild['UF_HEAD']] = $arChild['UF_HEAD'];
                        }
                        
                        $departmentIDs[] = $arChild['ID'];
                    }
                }
            }
        }

        $rsUsers = \CUser::GetList(
            $by = 'id', 
            $order = 'asc', 
            [
                'UF_DEPARTMENT' => $departmentIDs, 
                'ACTIVE' => 'Y'
            ],
            ['FIELDS' => ['ID']]
        );

        while($arUser = $rsUsers->Fetch()){
            $return[$arUser['ID']] = $arUser['ID'];
        }

        return $return;
    }

    public static function isLocal(){
        return strpos(\App\Base\BASE_DIR, 'local') !== false;
    }

    public static function getPathForStatic(){
        static $return = '';

        if(empty($return)){
            if(self::isLocal()){
                $return = '/local/static';
            }
            else{
                $return = '/bitrix';
            }
        }

        return $return;
    }
}

<?

namespace Studiobit\Base;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock;
use Bitrix\Crm;
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
	 * Добавление уведомления через API
	 *
	 * @param $from
	 * @param $to
	 * @param $message
	 * @param int $type
	 */
	public static function addNoteUser($from, $to, $message, $type = IM_NOTIFY_FROM)
	{
		global $USER, $APPLICATION;

		// отправить уведомление менеджеру
		if (Loader::includeModule("im")) {
			Loader::includeModule("crm");

			$arMessageFields = array(
				"TO_USER_ID" => $to, // получатель
				"FROM_USER_ID" => $from, // отправитель
				"NOTIFY_TYPE" => $type, // тип уведомления
				"NOTIFY_MODULE" => "crm", // модуль запросивший отправку уведомления
				"NOTIFY_TAG" => "CRM|" . mktime(), // символьный тэг для группировки и массового удаления, если это не требуется - не задаем параметр
				"NOTIFY_MESSAGE" => htmlspecialchars_decode($message), // текст уведомления на сайте
				"NOTIFY_MESSAGE_OUT" => strip_tags($message) // текст уведомления для отправки на почту (или XMPP), если различий нет - не задаем параметр
			);
            
			try {
				$messageID = \CIMNotify::Add($arMessageFields);

			} catch (Exception $e) {
				AddMessage2Log($e->getMessage());
			}
		}
	}

	/**
	 * Выборка контактов из зависимости определенной сущности
	 *
	 * @param $entityType
	 * @param $entityID
	 * @param string $communicationType
	 *
	 * @return array
	 */
	public static function getCrmEntityCommunications($entityType, $entityID, $communicationType = '')
	{
		\CModule::IncludeModule("crm");

		if ($entityType === 'LEAD') {
			$data = array(
				'ownerEntityType' => 'LEAD',
				'ownerEntityId' => $entityID,
				'entityType' => 'LEAD',
				'entityId' => $entityID,
				'entityTitle' => "{$entityType}_{$entityID}",
				'entityDescription' => '',
				'tabId' => 'main',
				'communications' => array()
			);

			$entity = \CCrmLead::GetByID($entityID);
			if (!$entity) {
				return array('ERROR' => 'Invalid data');
			}

			// Prepare title
			$title = isset($entity['TITLE']) ? $entity['TITLE'] : '';
			$honorific = isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '';
			$name = isset($entity['NAME']) ? $entity['NAME'] : '';
			$secondName = isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : '';
			$lastName = isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '';

			if ($title !== '') {
				$data['entityTitle'] = $title;
				$data['entityDescription'] = \CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => $honorific,
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					)
				);
			} else {
				$data['entityTitle'] = \CCrmLead::PrepareFormattedName(
					array(
						'HONORIFIC' => $honorific,
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					)
				);
				$data['entityDescription'] = '';
			}

			// Try to load entity communications
			if (!\CCrmActivity::CheckReadPermission(\CCrmOwnerType::ResolveID($entityType), $entityID)) {
				return array('ERROR' => 'error');
			}

			if ($communicationType !== '') {
				$dbResFields = \CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' => $communicationType)
				);

				while ($arField = $dbResFields->Fetch()) {
					if (empty($arField['VALUE'])) {
						continue;
					}

					$comm = array('type' => $communicationType, 'value' => $arField['VALUE']);
					$data['communications'][] = $comm;
				}
			}

			return array(
				'DATA' => array()
			);
		} else if ($entityType === 'DEAL') {
			$entity = \CCrmDeal::GetByID($entityID);
			if (!$entity) {
				return array('ERROR' => 'Invalid data');
			}

			$dealData = array();

			// Prepare company data
			$entityCompanyData = null;
			$entityCompanyID = isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
			$entityCompany = $entityCompanyID > 0 ? \CCrmCompany::GetByID($entityCompanyID) : null;

			if (is_array($entityCompany)) {
				$entityCompanyData = array(
					'ownerEntityType' => 'DEAL',
					'ownerEntityId' => $entityID,
					'entityType' => 'COMPANY',
					'entityId' => $entityCompanyID,
					'entityTitle' => isset($entityCompany['TITLE']) ? $entityCompany['TITLE'] : '',
					'entityDescription' => '',
					'communications' => array()
				);

				if ($communicationType !== '') {
					$entityCompanyComms = \CCrmActivity::PrepareCommunications('COMPANY', $entityCompanyID, $communicationType);

					foreach ($entityCompanyComms as &$entityCompanyComm) {
						$comm = array(
							'type' => $entityCompanyComm['TYPE'],
							'value' => $entityCompanyComm['VALUE']
						);

						$entityCompanyData['communications'][] = $comm;
					}
					unset($entityCompanyComm);
				}
			}

			// Try to get contact of deal
			$entityContactID = isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
			if ($entityContactID > 0) {
				$entityContact = \CCrmContact::GetByID($entityContactID);
				if (is_array($entityContact)) {
					$item = array(
						'ownerEntityType' => 'DEAL',
						'ownerEntityId' => $entityID,
						'entityType' => 'CONTACT',
						'entityId' => $entityContactID,
						'entityTitle' => \CCrmContact::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($entityContact['HONORIFIC']) ? $entityContact['HONORIFIC'] : '',
								'NAME' => isset($entityContact['NAME']) ? $entityContact['NAME'] : '',
								'LAST_NAME' => isset($entityContact['LAST_NAME']) ? $entityContact['LAST_NAME'] : '',
								'SECOND_NAME' => isset($entityContact['SECOND_NAME']) ? $entityContact['SECOND_NAME'] : ''
							)
						),
						'tabId' => 'deal',
						'communications' => array()
					);

					$entityCompany = isset($entityContact['COMPANY_ID']) ? \CCrmCompany::GetByID($entityContact['COMPANY_ID']) : null;
					if ($entityCompany && isset($entityCompany['TITLE'])) {
						$item['entityDescription'] = $entityCompany['TITLE'];
					}

					if ($communicationType !== '') {
						$entityContactComms = \CCrmActivity::PrepareCommunications('CONTACT', $entityContactID, $communicationType);
						foreach ($entityContactComms as &$entityContactComm) {
							$comm = array(
								'type' => $entityContactComm['TYPE'],
								'value' => $entityContactComm['VALUE']
							);

							$item['communications'][] = $comm;
						}
						unset($entityContactComm);
					}

					if ($communicationType === '' || !empty($item['communications'])) {
						$dealData["CONTACT_{$entityContactID}"] = $item;
					}
				}
			}

			if ($entityCompanyData && !empty($entityCompanyData['communications'])) {
				$dealData['COMPANY_' . $entityCompanyID] = $entityCompanyData;
				$dealData['COMPANY_' . $entityCompanyID]['tabId'] = 'deal';
			}

			// Try to get previous communications
			$entityComms = \CCrmActivity::GetCommunicationsByOwner('DEAL', $entityID, $communicationType);
			foreach ($entityComms as &$entityComm) {
				\CCrmActivity::PrepareCommunicationInfo($entityComm);
				$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
				if (!isset($dealData[$key])) {
					$dealData[$key] = array(
						'ownerEntityType' => 'DEAL',
						'ownerEntityId' => $entityID,
						'entityType' => \CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
						'entityId' => $entityComm['ENTITY_ID'],
						'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
						'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
						'tabId' => 'deal',
						'communications' => array()
					);
				}

				if ($communicationType !== '') {
					$commFound = false;
					foreach ($dealData[$key]['communications'] as &$comm) {
						if ($comm['value'] === $entityComm['VALUE']) {
							$commFound = true;
							break;
						}
					}
					unset($comm);

					if ($commFound) {
						continue;
					}

					$comm = array(
						'type' => $entityComm['TYPE'],
						'value' => $entityComm['VALUE']
					);

					$dealData[$key]['communications'][] = $comm;
				}
			}
			unset($entityComm);

			$companyData = array();
			// Try to get contacts of company
			if ($entityCompany > 0) {
				$entityComms = \CCrmActivity::GetCompanyCommunications($entityCompanyID, $communicationType);
				foreach ($entityComms as &$entityComm) {
					\CCrmActivity::PrepareCommunicationInfo($entityComm);
					$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
					if (!isset($companyData[$key])) {
						$companyData[$key] = array(
							'ownerEntityType' => 'DEAL',
							'ownerEntityId' => $entityID,
							'entityType' => \CCrmOwnerType::ResolveName($entityComm['ENTITY_TYPE_ID']),
							'entityId' => $entityComm['ENTITY_ID'],
							'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
							'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
							'tabId' => 'company',
							'communications' => array()
						);
					}

					if ($communicationType !== '') {
						$comm = array(
							'type' => $entityComm['TYPE'],
							'value' => $entityComm['VALUE']
						);

						$companyData[$key]['communications'][] = $comm;
					}
				}
				unset($entityComm);
			}

			if ($entityCompanyData && !empty($entityCompanyData['communications'])) {
				$companyData['COMPANY_' . $entityCompanyID] = $entityCompanyData;
				$companyData['COMPANY_' . $entityCompanyID]['tabId'] = 'company';
			}

			return array(
				'DATA' => array(
					'DEAL' => array_values($dealData),
					'COMPANY' => array_values($companyData),
				)
			);
		} else if ($entityType === 'COMPANY') {
			$companyData = array();

			$entity = \CCrmCompany::GetByID($entityID);
			if (!$entity) {
				return array('ERROR' => 'Invalid data');
			}

			$companyItem = array(
				'ownerEntityType' => 'COMPANY',
				'ownerEntityId' => $entityID,
				'entityType' => 'COMPANY',
				'entityId' => $entityID,
				'entityTitle' => isset($entity['TITLE']) ? $entity['TITLE'] : "{$entityType}_{$entityID}",
				'entityDescription' => '',
				'tabId' => 'company',
				'communications' => array()
			);

			// Try to load entity communications
			if (!\CCrmActivity::CheckReadPermission(\CCrmOwnerType::ResolveID($entityType), $entityID)) {
				return array('ERROR' => 'error');
			}

			if ($communicationType !== '') {
				$dbResFields = \CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' => $communicationType)
				);

				while ($arField = $dbResFields->Fetch()) {
					if (empty($arField['VALUE'])) {
						continue;
					}

					$comm = array(
						'type' => $communicationType,
						'value' => $arField['VALUE']
					);

					$companyItem['communications'][] = $comm;
				}
			}

			$companyData["{$entityType}_{$entityID}"] = $companyItem;

			if ($communicationType !== '') {
				$entityComms = \CCrmActivity::GetCompanyCommunications($entityID, $communicationType, 50);
				foreach ($entityComms as &$entityComm) {
					\CCrmActivity::PrepareCommunicationInfo($entityComm);
					$key = "{$entityComm['ENTITY_TYPE']}_{$entityComm['ENTITY_ID']}";
					if (!isset($companyData[$key])) {
						$companyData[$key] = array(
							'ownerEntityType' => 'COMPANY',
							'ownerEntityId' => $entityID,
							'entityType' => $entityComm['ENTITY_TYPE'],
							'entityId' => $entityComm['ENTITY_ID'],
							'entityTitle' => isset($entityComm['TITLE']) ? $entityComm['TITLE'] : '',
							'entityDescription' => isset($entityComm['DESCRIPTION']) ? $entityComm['DESCRIPTION'] : '',
							'tabId' => 'company',
							'communications' => array()
						);
					}

					$comm = array(
						'type' => $entityComm['TYPE'],
						'value' => $entityComm['VALUE']
					);

					$companyData[$key]['communications'][] = $comm;
				}
				unset($entityComm);
			}

			return array(
				'DATA' => array(
					'COMPANY' => array_values($companyData)
				)
			);
		} else if ($entityType === 'CONTACT') {
			$contactData = array();

			$entity = \CCrmContact::GetByID($entityID);
			if (!$entity) {
				return array('ERROR' => 'Invalid data');
			}

			$entityCompany = isset($entity['COMPANY_ID']) ? \CCrmCompany::GetByID($entity['COMPANY_ID']) : null;

			$contactItem = array(
				'ownerEntityType' => 'CONTACT',
				'ownerEntityId' => $entityID,
				'entityType' => 'CONTACT',
				'entityId' => $entityID,
				'entityTitle' => \CCrmContact::PrepareFormattedName(
					array(
						'HONORIFIC' => isset($entity['HONORIFIC']) ? $entity['HONORIFIC'] : '',
						'NAME' => isset($entity['NAME']) ? $entity['NAME'] : '',
						'LAST_NAME' => isset($entity['LAST_NAME']) ? $entity['LAST_NAME'] : '',
						'SECOND_NAME' => isset($entity['SECOND_NAME']) ? $entity['SECOND_NAME'] : ''
					)
				),
				'entityDescription' => ($entityCompany && isset($entityCompany['TITLE'])) ? $entityCompany['TITLE'] : '',
				'tabId' => 'contact',
				'communications' => array()
			);

			// Try to load entity communications
			if (!\CCrmActivity::CheckReadPermission(\CCrmOwnerType::ResolveID($entityType), $entityID)) {
				return array('ERROR' => 'error');
			}

			if ($communicationType !== '') {
				$dbResFields = \CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' => $communicationType)
				);

				while ($arField = $dbResFields->Fetch()) {
					if (empty($arField['VALUE'])) {
						continue;
					}

					$comm = array(
						'type' => $communicationType,
						'value' => $arField['VALUE']
					);

					$contactItem['communications'][] = $comm;
				}
			}

			$contactData["{$entityType}_{$entityID}"] = $contactItem;

			return array(
				'DATA' => array(
					'CONTACT' => array_values($contactData)
				)
			);
		}

		return array('ERROR' => 'Invalid data');
	}

	/**
	 * Возвращает список вариантов значений свойств типа "список"
	 *
	 * @param $IBLOCK_ID
	 * @param $PROPERTY_CODE
	 *
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

        if($result == "")
            $obCache->Clean($cache_id, "/sys/prop_enum/");

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
	 * Возвращает value пользовательского свойства типа "список", по id
	 *
	 * @param $UF_PROPERTY_CODE
	 * @param $id
	 *
	 * @return string
	 */
	public static function getValueInUFPropEnumID($UF_PROPERTY_CODE, $id)
	{
		$result = "";
		$obCache = new \CPHPCache;
		$cache_id = md5('getValueInUFPropEnumID|' . $UF_PROPERTY_CODE . "|" . $id . "|");
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum_id/")) {
			$vars = $obCache->GetVars();
			$result = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
			if ($id != "") {
				$enumFields = \CUserFieldEnum::GetList(
					array(),
					array("USER_FIELD_NAME" => $UF_PROPERTY_CODE, "ID" => $id)
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
	public static function getUFPropEnum($UF_PROPERTY_CODE, $arFilter = array())
	{
		$arEnumFields = array();
		$obCache = new \CPHPCache;
		$cache_id = md5('getUFPropEnum|' . $UF_PROPERTY_CODE . "|" . serialize($arFilter) . "|");
		if ($obCache->InitCache(3600000, $cache_id, "/sys/prop_enum_list/")) {
			$vars = $obCache->GetVars();
			$arEnumFields = $vars['FIELD'];
		}
		if ($obCache->StartDataCache()) {
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
	 * Возвращает значение какого-то свойства сущности CRM
	 *
	 * @param $type
	 * @param $ELEMENT_ID
	 * @param array $CODE_PROPERTY
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getPropertyEntityCrm($type, $ELEMENT_ID, $CODE_PROPERTY = array())
	{
		Loader::includeModule("crm");

		$result = array();
		if (!is_array($CODE_PROPERTY)) {
			$arSelect = array("ID", $CODE_PROPERTY);
			$arSelect = array_merge($arSelect, $CODE_PROPERTY);
		} else {
			$arSelect = $CODE_PROPERTY;
		}

		if ($type == "COMPANY") {
			$idEntity = "\\Bitrix\\Crm\\Company";
		} else if ($type == "DEAL") {
			$idEntity = "\\Bitrix\\Crm\\Deal";
		} else if ($type == "LEAD") {
			$idEntity = "\\Bitrix\\Crm\\Lead";
		} else if ($type == "CONTACT") {
			$idEntity = "\\Bitrix\\Crm\\Contact";
		}

		$entity = clone Entity\Base::getInstance($idEntity);

		$main_query = new Entity\Query($entity);
		$main_query->setSelect($arSelect)->setFilter(array("ID" => $ELEMENT_ID));

		$resultQuery = $main_query->exec();
		$resultQuery = new \CDBResult($resultQuery);

		$data = array();
		$grcDataPrimaryValues = array();
		$grcDataPrimaryPointers = array();

		while ($row = $resultQuery->Fetch()) {
			$data = $row;
		}

		foreach ($CODE_PROPERTY as $k) {
			$result[$k] = $data[$k];
		}

		return $result;
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

    public static function getStorageByName($name)
    {
        $storage = false;

        if(\Bitrix\Main\Loader::includeModule('disk')) {
            global $USER;

            $storageId = 0;
            $filterReadableList = array('=STORAGE.ENTITY_TYPE' => \Bitrix\Disk\ProxyType\Common::className(), '=STORAGE.NAME' => $name);
            foreach (\Bitrix\Disk\Storage::getReadableList(new \Bitrix\Disk\Security\FakeSecurityContext($USER), array('filter' => $filterReadableList)) as $storage) {
                $storageId = $storage->getId();
            }
            if ($storageId)
            {
                $storage = \Bitrix\Disk\Storage::loadById((int)$storageId, array('ROOT_OBJECT'));
            }
        }

        return $storage;
    }

    public static function getDealStageIDByName($name, $deal_id = 0){
        if(\Bitrix\Main\Loader::includeModule('crm'))
		{
			$categoryID = 0;
			if($deal_id){
				$rsDeal = \CCrmDeal::GetListEx(array(), array('ID' => $deal_id), false, false, array('ID', 'CATEGORY_ID'));
				if($arDeal = $rsDeal->Fetch()){
					$categoryID = (int)$arDeal['CATEGORY_ID'];
				}
			}

            $name = strtolower(trim($name));
            $stages = \CCrmDeal::GetStages($categoryID);

            foreach($stages as $stage){
                if(strtolower($stage['NAME']) == $name){
                    return $stage['STATUS_ID'];
                }
            }
        }

        return false;
    }

    public static function getDealCategory($dealID)
    {
        static $cache = [];
        if(!isset($cache[$dealID])){
            if(\Bitrix\Main\Loader::includeModule('crm'))
            {
                $rsDeal = \CCrmDeal::GetListEx([], ['ID' => $dealID], false, false, ['ID', 'CATEGORY_ID']);
                if ($arDeal = $rsDeal->Fetch()) {
                    $cache[$dealID] = $arDeal['CATEGORY_ID'];
                }
            }
        }

        return $cache[$dealID];
    }

    public static function getLoseStages($dealId){
        $return = [];
        if(\Bitrix\Main\Loader::includeModule('crm'))
        {
            $entity = 'DEAL_STAGE';
            if (self::getDealCategory($dealId) > 1) {
                $entity .= '_' . self::getDealCategory($dealId);
            }
            $stages = \CCrmStatus::GetStatusListEx($entity);

            $isLose = false;
            foreach ($stages as $stageId => $stage) {
                if ($isLose) {
                    $return[$stageId] = $stage;
                }

                if (strpos($stageId, 'WON') !== false) {
                    $isLose = true;
                }
            }
        }

        return $return;
    }

    public static function getStatusSort($statusId, $dealId)
    {
        if(\Bitrix\Main\Loader::includeModule('crm'))
        {
            $entity = 'DEAL_STAGE';
            if (self::getDealCategory($dealId) > 1) {
                $entity .= '_' . self::getDealCategory($dealId);
            }

            $rsStatus = \CCrmStatus::GetList([], ['STATUS_ID' => $statusId, 'ENTITY_ID' => $entity]);
            if ($arStatus = $rsStatus->Fetch()) {
                return $arStatus['SORT'];
            }
        }

        return 0;
    }

    public static function getColorByBackground($hex){
        if (substr($hex, 0, 1) != "#")
            $hex = "#" . $hex;

        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        $koef = 0.21 * $r + 0.72 * $g + 0.07 * $b;

        return($koef < 145) ? "#fff" : "#333";
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
     * @param $groupCode
     * @param array $additionalFilter
     * @return array
     */
    public static function getUsersByGroupCode($groupCode, $additionalFilter = []){
        $return = [];
        $arGroup = static::getGroupByCode($groupCode);

        if(!empty($arGroup)){
            $rsUser = \CUser::GetList(
                $by = 'ID',
                $order = 'ASC',
                array_merge(['GROUPS_ID' => [$arGroup['ID']]], $additionalFilter),
                ['SELECT' => ['ID']]
            );

            while($arUser = $rsUser->fetch()){
                $return[] = $arUser['ID'];
            }
        }

        return $return;
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

    public static function getAllUserManagers($user_id = false){
        $return = [];
        $id = array_keys(\CIntranetUtils::GetDepartmentManager(\CIntranetUtils::GetUserDepartments($user_id), $user_id, true));
        if(!empty($id)){
            $return = array_merge($return, $id);
            $return = array_merge($return, self::getAllUserManagers($id[0]));
        }

        return $return;
    }

    public static function getUsersByDepartment($departmentId, $withHeads = true,  $recursive = false)
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

    public static function addTimelineComment($entityType, $entityId, $comment, $authorId = 0){
        if(Loader::includeModule('crm')) {

            if(!$authorId)
                $authorId = $GLOBALS['USER']->GetID();
            
            $entryID = \Bitrix\Crm\Timeline\CommentEntry::create(
                [
                    'TEXT'      => $comment,
                    'AUTHOR_ID' => $authorId ?: 0,
                    'BINDINGS'  => [['ENTITY_TYPE_ID' => $entityType, 'ENTITY_ID' => $entityId]]
                ]
            );

            if ($entryID) {
                $saveData = [
                    'COMMENT'        => $authorId,
                    'ENTITY_TYPE_ID' => $entityType,
                    'ENTITY_ID'      => $entityId,
                ];

                \Bitrix\Crm\Timeline\CommentController::getInstance()->onCreate($entryID, $saveData);

                return true;
            }
        }

        return false;
    }
}

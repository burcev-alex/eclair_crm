<?

namespace Studiobit\Project\Integration\Rest;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Iblock as Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Studiobit\Base;
use Studiobit\Base\Rest;
use Studiobit\Project;

class ServerApi extends Rest\Api
{
	protected $apiKey;
	protected $debug = false;

	public function __construct($request, $origin)
	{
		global $USER;
		parent::__construct($request);

		$this->apiKey = Main\Config\Option::get('studiobit.base', 'server_rest_api', '123456');

		if (Main\Config\Option::get('studiobit.base', 'debug', 'N') == "Y") {
			$this->debug = true;
		}

		if (!array_key_exists('apiKey', $this->request)) {
			throw new \Exception('No API Key provided');
		} elseif ($this->apiKey != $this->request['apiKey']) {
			throw new \Exception('Invalid API Key');
		}

		// авторизуемся под админов
		$USER->Authorize(1);
	}

	/**
	 * Тестовый запрос
	 * @return string
	 */
	protected function test()
	{
		if ($this->method == 'GET') {
			return array("message" => "Hello world!");
		} else {
			return array("message" => "Only accepts GET requests");
		}
	}

	/**
	 * Список риэлторов конктретного агенства
	 *
	 * @return array
	 */
	protected function realtors_agency()
	{
		if ($this->method == 'GET') {
			$arData = [
				'count' => 0,
				'item' => []
			];

			$realtorId = IntVal($this->request['realtor_id']);

			$result = [];
			if($realtorId > 0) {
				try {
					$companyBindings = \Bitrix\Crm\Binding\ContactCompanyTable::getContactBindings($realtorId);
				}
				catch (Main\ArgumentException $e){
					return $result;
				}

				// агенство недвижимости
				$agencyIds = [];
				foreach($companyBindings as $company){
					$agencyIds[] = $company['COMPANY_ID'];
				}

				$filter = array("COMPANY_ID" => $agencyIds, "TYPE_ID" => "PARTNER");
				// найти всех риэлторов из этого агенства
				$rsRealtor = \CCrmContact::GetListEx(array(), $filter, false, false, array('ID', 'UF_CRM_AGENCY', 'UF_CRM_REALTOR', 'ASSIGNED_BY_ID', "COMPANY_ID"));
				while ($item = $rsRealtor->Fetch()) {
					#if ($item['UF_CRM_REALTOR'] == $item['ID']) {
						$result[] = $item['ID'];
					#}
				}
			}

			$arData['count'] = count($result);
			$arData['item'] = $result;

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Смена ответственного для риэлтора
	 *
	 * @return array
	 */
	protected function realtor_set_manager()
	{
		if ($this->method == 'GET') {
			$arData = [
				'count' => 0,
				'status' => 'error'
			];

			$realtorId = IntVal($this->request['realtor_id']);
			$managerId = IntVal($this->request['manager_id']);

			$entity = new \CCrmContact(false);

			if($realtorId > 0) {
				$arRealtor = \CCrmContact::GetListEx(array(), array("ID" => $realtorId), false, false, array('ID', 'ASSIGNED_BY_ID', "COMPANY_ID"))->Fetch();

				if($arRealtor['ASSIGNED_BY_ID'] != $managerId){
					$fields = [
						'ASSIGNED_BY_ID' => $managerId,
                        'UF_CRM_STATUS' => Base\Tools::getIDInUFPropEnumByXml(
                            'UF_CRM_STATUS',
                            'REALTOR_WORK',
                            0,
                            'CRM_CONTACT'
                        )
					];
					$entity->Update($arRealtor['ID'], $fields);
					$arData['count'] = 1;
					$arData['status'] = 'ok';
				}

			}

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Все риэлторы системы
	 *
	 * @return array
	 */
	protected function realtors_list()
	{
		if ($this->method == 'GET') {
			$arData = [
				'count' => 0,
				'item' => []
			];

			$filter = array("TYPE_ID"=>"PARTNER");

			$realtorId = IntVal($this->request['realtor_id']);

			if($realtorId > 0){
				$filter = [];
				$filter['UF_GUID'] = $realtorId;
			}

			// найти всех риэлторов
			$result = [];
			$rsRealtor = \CCrmContact::GetListEx(array(), $filter, false, false, array('ID', 'UF_CRM_AGENCY', 'UF_CRM_REALTOR', 'ASSIGNED_BY_ID', "COMPANY_ID", "UF_GUID"));
			while ($item = $rsRealtor->Fetch()) {
				$result[] = $item;
			}

			$arData['count'] = count($result);
			$arData['item'] = $result;

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Информация по найденному контакту
	 */
	protected function find_deal()
	{
		if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");

			$arData['count'] = 0;

			// сообщения по найденному контакту принимаются
			$arData['limit'] = 0;

			// маркер, что сделка ищется сразу после Создания
			$isCreated = IntVal($this->request['create'])?true:false;
			// этот маркер и не нужен, после создания контакта, сделки может и не быть
			// логика такая, что сделки без привязки к объекту не должно существовать
			$isCreated = false;

			// статусы сделки
			$arData['stage'] = $this->stageDeal();

			$phone = trim($this->request['phone']);
			// удаляем префик, код страны
			$phone = substr($phone, 1, strlen($phone));

			$phoneValue1 = str_replace(array(" ", "(", ")", "-"), array("","","",""), $phone);
			$phoneValue2 = str_replace(array(" ", "(", ")"), array(""," "," "), $phone);

			$realtorId = IntVal($this->request['realtor_id']);

			$filterRealtor = array(
				array(
					'LOGIC' => 'OR',
					array(
						'ID'=>$realtorId,
						'UF_GUID' => null
					),
					array(
						'UF_GUID'=>$realtorId
					),
				),

			);
			$arRealtor = \CCrmContact::GetListEx(array(), $filterRealtor, false, false, array('ID', 'UF_CRM_AGENCY', 'ASSIGNED_BY_ID', "COMPANY_ID", "UF_CRM_STATUS"))->Fetch();
			// замена UF_CRM_AGENCY на COMPANY_ID
			if(IntVal($arRealtor['UF_CRM_AGENCY']) == 0){
				if(IntVal($arRealtor['COMPANY_ID']) > 0){
					$arRealtor['UF_CRM_AGENCY'] = $arRealtor['COMPANY_ID'];
				}
			}

			$contactId = [];
			if (strlen($phone) > 0) {
				// поиск клиента по номеру телефона
				$rsPhone = \CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phone, 'TYPE_ID' =>  'PHONE')
				);
				while ($arPhone = $rsPhone->Fetch()) {
					$contactId[] = $arPhone['ELEMENT_ID'];
				}

				if (count($contactId) == 0) {
					$rsPhone = \CCrmFieldMulti::GetList(
						array('ID' => 'asc'),
						array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue1, 'TYPE_ID' =>  'PHONE')
					);
					while ($arPhone = $rsPhone->Fetch()) {
						$contactId[] = $arPhone['ELEMENT_ID'];
					}
				}

				if (count($contactId) == 0) {
					$rsPhone = \CCrmFieldMulti::GetList(
						array('ID' => 'asc'),
						array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue2, 'TYPE_ID' =>  'PHONE')
					);
					while ($arPhone = $rsPhone->Fetch()) {
						$contactId[] = $arPhone['ELEMENT_ID'];
					}
				}

				$arFilter = array(
					"!ASSIGNED_BY_ID" => 42 // TODO - Менеджеры
				);

				if (count($contactId) > 0) {
					$arFilter['CONTACT_ID'] = $contactId;
				} else {
					$arFilter['ID'] = 0;
				}

				function checkContact($arFilter, $contactId, $arRealtor, $arData, $isCreated, $request)
				{
					$arSelect = array(
						"ID",
						"TITLE",
						"STAGE_ID",
						"CLOSED",
						"CONTACT_ID",
						"COMPANY_ID",
						"CONTACT.FULL_NAME",
						"ASSIGNED_BY",
						"DATE_CREATE",
						"UF_CRM_CHANNEL", // канал привлечения
						"UF_CRM_STAGE_DATE", // Дата изменения стадии
						"UF_CRM_AGENCY", // агентство
						"UF_CRM_OWNERS" // Дольщики
					);

					$arSelectContact = array(
						'ID',
						'LAST_NAME',
						'NAME',
						'DATE_CREATE',
						'UF_CRM_STATUS',
						'ASSIGNED_BY_ID',
						'UF_CRM_AGENCY',
						'UF_CRM_CHANNEL',
						'UF_CRM_SOURCE',
						'UF_CRM_STAGE_DATE',
						'UF_BINGO_DATE_SET',
						'UF_DATE_MORATORIUM'
					);

					// статусы клиентов
					$statusClients = [];

					$statusContactLimit = [
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Не обработан"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "На дозвоне"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "В работе"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "ДС / второй дольщик"),
					];

					$statusContactNextStage = [
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Дебиторка"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Купили у нас"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Снята бронь"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Прозвон баз"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Не выходит на связь"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Отложили покупку"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Купили в другом месте"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Нецелевой"),
					];

					// стадии на доп проверку по дате моратория
					$statusContactNextStageBefore = [
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Снята бронь"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Прозвон баз"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Не выходит на связь"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Отложили покупку"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Купили в другом месте"),
						Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Нецелевой"),
					];

					$statusDealBook = [
						"Оформление документов",
                        "Регистрация договора",
                        "Дебиторка",
						"Согласование ипотеки",
						"Бронь"
					];

					$isLimit = $isContactDc = false;
					$count = 0;
					if (count($contactId) > 0) {
						$rsClient = \CCrmContact::GetListEx(array(), array('ID'=>$contactId), false, false, $arSelectContact);
						while ($arClient = $rsClient->Fetch()) {
							$count++;

							// данные менеджера
							$arUser = \CUser::GetByID($arClient['ASSIGNED_BY_ID'])->Fetch();

							// ответственный по сделке
							$assignedFullName = $arUser['LAST_NAME'] . " " . $arUser['NAME'] . " " . $arUser['SECOND_NAME'];
							// телефон ответственного
							$assignedPhone = $arUser['WORK_PHONE'] ? $arUser['WORK_PHONE'] : $arUser['PERSONAL_MOBILE'];
							// если пользователь из подразделения "Колл-центр", тогда выводить другие данные
							$departmentUser = \CIntranetUtils::GetUserDepartments($arUser['ID']);

							$arrNameDepartment = [];
							$dbDepartment = \CIBlockSection::GetList(array(), array('ID' => $departmentUser));
							while ($arSection = $dbDepartment->Fetch()) {
								$arrNameDepartment[] = $arSection['NAME'];
							}
							if (in_array("Call-Центр", $arrNameDepartment)) {
								$assignedFullName = "Call-Центр";
								$assignedPhone = "(383) 311-05-05";
							}
							if(strlen($assignedPhone) == 0){
								$assignedPhone = "(383) 311-05-05";
							}

							$arClient['CONTACT_FULL_NAME'] = trim($arClient['LAST_NAME'].' '.$arClient['NAME']);
							$arClient['CONTACT_PHONE'] = trim($request['phone']);
							$arClient['ASSIGNED_BY'] = $arClient['ASSIGNED_BY_ID'];
							$arClient['ASSIGNED_PHONE'] = $assignedPhone;
							$arClient['ASSIGNED_BY_FULL_NAME'] = trim($assignedFullName);

							$rsChannel = \CIBlockSection::GetList([], ['ID' => $arClient['UF_CRM_CHANNEL']], false, ['ID', 'NAME']);
							$arChannel = $rsChannel->Fetch();
							if (strlen($arChannel['NAME']) > 0) {
								$arClient['UF_CRM_CHANNEL'] = $arChannel['NAME'];
							}

							$arData['client'] = $arClient;
							// статусы клиентов
							if(IntVal($arClient['UF_CRM_STATUS']) == 0){
								$arClient['UF_CRM_STATUS'] = Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Не обработан");
							}

							$statusClients[] = $arClient['UF_CRM_STATUS'];

							// проверка совпадений
							// ЕСЛИ статус контакта: «Не обработан», «На дозвоне», «В Работе» – уведомление не принимается.
							if (in_array($arClient['UF_CRM_STATUS'], $statusContactLimit)) {
								$isLimit = true;
							}

							// ЕСЛИ статус контакта: «Дебиторка», «Купили у нас», «Снята Бронь», «Прозвон Баз»,
							// «Не выходит на связь», «Отложили покупку», «Купили в другом месте», «Нецелевой» выполняем проверку 1.
							if (in_array($arClient['UF_CRM_STATUS'], $statusContactNextStage)) {

								// проверка по дате наложения моратория
								$isMoratorium = false;
								if (in_array($arClient['UF_CRM_STATUS'], $statusContactNextStageBefore)) {
									if (strlen($arClient['UF_DATE_MORATORIUM']) > 0) {
										// Смотрим дату проигрыша карточки контакта,
										// если с момента проигрыша прошло 3 суток, значит проверка пройдена,
										// иначе отказ в принятии уведомления.
										$countDayLimit = 3;

										$dateChange = \DateTime::createFromFormat('d.m.Y H:i:s', $arClient['UF_DATE_MORATORIUM']);
										$now = new \DateTime();
										$dayDiff = $dateChange->diff($now)->format('%a');

										if ($dayDiff < $countDayLimit) {
											$isMoratorium = true;
										}
									}

									if ($isMoratorium) {
										$isLimit = true;
									}
								}

								if(!$isMoratorium) {
									// Проверка 1:
									// Проверяем наличие действующего уведомления от другого агентства недвижимости.
									// Смотрим в поле «Канал» карточки контакта, ЕСЛИ там «Агентство недвижимости»,
									// тогда не принимаем уведомление, ИНАЧЕ принимаем (смотрим принятие уведомления).
									if ($arClient['UF_CRM_CHANNEL'] == 'Агентство недвижимости') {
										// через сколько будет блокировка
										$countDayLimit = 14;

										// разница в днях между датами
										if (strlen($arClient['UF_BINGO_DATE_SET']) > 0) {
											$arClient['UF_CRM_STAGE_DATE'] = date('d.m.Y H:i:s', strtotime($arClient['UF_BINGO_DATE_SET']));
										} else {
											$arClient['UF_CRM_STAGE_DATE'] = date('d.m.Y H:i:s', strtotime($arClient['DATE_CREATE']));
										}
										$dateChange = \DateTime::createFromFormat('d.m.Y H:i:s', $arClient['UF_CRM_STAGE_DATE']);
										$now = new \DateTime();
										$dayDiff = $dateChange->diff($now)->format('%a');

										if ($dayDiff < $countDayLimit) {
											$isLimit = true;
										}
									}
								}
							}

							// ЕСЛИ у Контакта статус - «Бронь», то выполняем проверку контакта:
							// Смотрим все его сделки и выбираем только в стадиях -
							// «Бронь», «Оформление Документов», «Согласование ипотеки».
							// Если сделки в этих стадиях находятся, тогда уведомление не принимается.
							// ИНАЧЕ выполняем проверку 1.
							if($arClient['UF_CRM_STATUS'] == Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "Бронь")){
								// Последняя сделка клиента
								$resDeals = Crm\DealTable::getList([
									'filter' => $arFilter,
									'select' => $arSelect,
									'order' => array('DATE_CREATE' => 'DESC')
								]);
								//p2f($arFilter);
								while ($arDeal = $resDeals->Fetch()) {
									if (in_array($arData['stage'][$arDeal['STAGE_ID']]['NAME'], $statusDealBook)) {
										$isLimit = true;
										break;
									}

									$item = [
										'ID' => $arDeal['ID'],
										'DATE_CREATE' => $arDeal['DATE_CREATE'],
										'CONTACT_FULL_NAME' => $arDeal['CRM_DEAL_CONTACT_FULL_NAME'],
										'CONTACT_PHONE' => trim($request['phone']),
										'ASSIGNED_BY' => $arDeal['CRM_DEAL_ASSIGNED_BY_ID'],
										'ASSIGNED_PHONE' => $assignedPhone,
										'ASSIGNED_BY_FULL_NAME' => $assignedFullName,
										'CHANNEL' => $arDeal['UF_CRM_CHANNEL']
									];

									if (strlen($item['ASSIGNED_PHONE']) == 0) {
										$item['ASSIGNED_PHONE'] = '-';
									}

									$arData['info'] = $item;
								}

								if(!$isLimit) {
									if ($arClient['UF_CRM_CHANNEL'] == 'Агентство недвижимости') {
										// через сколько будет блокировка
										$countDayLimit = 14;

										// разница в днях между датами
										if (strlen($arClient['UF_BINGO_DATE_SET']) > 0) {
											$arClient['UF_CRM_STAGE_DATE'] = date('d.m.Y H:i:s', strtotime($arClient['UF_BINGO_DATE_SET']));
										} else {
											$arClient['UF_CRM_STAGE_DATE'] = date('d.m.Y H:i:s', strtotime($arClient['DATE_CREATE']));
										}
										$dateChange = \DateTime::createFromFormat('d.m.Y H:i:s', $arClient['UF_CRM_STAGE_DATE']);
										$now = new \DateTime();
										$dayDiff = $dateChange->diff($now)->format('%a');

										if ($dayDiff < $countDayLimit) {
											$isLimit = true;
										}
									}
								}

							}
						}
					}

					if ($isLimit) {
						// сообщения по найденному контакту НЕ принимаются
						$arData['limit'] = 1;
						$arData['count'] = 0;
					}
					else{
						$arData['count'] = $count;

						if($count == 0) {
							$arData['client'] = ['ID' => 0];
						}
					}


					return $arData;
				}

				$arData = checkContact($arFilter, $contactId, $arRealtor, $arData, $isCreated, $this->request);

				$arData['message'] = IntVal($arData['count'])." records processed";
			} else {
				$arData['message'] = "0 records processed";
				$arData['count'] = 0;
			}
			//p2f($arData);
			//p2f("-------------------".date("d.m.Y H:i:s")."-------------------------------------");

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

    /**
     * Список звонков
     *
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\NotImplementedException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    protected function records()
    {
        if ($this->method == 'GET') {
            \CModule::IncludeModule("crm");
            \CModule::IncludeModule('disk');
            \CModule::IncludeModule('intranet');

            if (IntVal($this->request['id'])) {
                $filterContact = [
                    'ID'          => IntVal($this->request['id'])
                ];
            }
            else{
                return array("error" => "parameter id is empty");
            }

            $filePath = [];

            // поиск всех контактов риэлтора
            $rsClient = \CCrmContact::GetListEx([], $filterContact, false, false, ['ID', 'UF_BINGO_DATE_SET']);
            if ($arClient = $rsClient->Fetch()) {
                // вытягиваем телефонные звонки, если таковы имеются

                $filterCall = [
                    '>=CREATED'         => $arClient['UF_BINGO_DATE_SET'],
                    'COMPLETED'         => "Y",
                    '?PROVIDER_TYPE_ID' => 'MEETING || CALL',
                    'BINDINGS'          => [['OWNER_TYPE_ID' => 3, 'OWNER_ID' => $arClient['ID']]]
                ];

                $dbResultActivity = \CCrmActivity::GetList(
                    [],
                    $filterCall,
                    false,
                    false,
                    []
                );
                while ($rowActivity = $dbResultActivity->fetch()) {
                    $rowActivity['FILES'] = $rowActivity['WEBDAV_ELEMENTS'] = $rowActivity['DISK_FILES'] = [];

                    \CCrmActivity::PrepareStorageElementIDs($rowActivity);
                    \CCrmActivity::PrepareStorageElementInfo($rowActivity);

                    if (!empty($rowActivity['FILES'])) {
                        foreach ($rowActivity['FILES'] as $file) {
                            $filePath[] = [
                                "created" => $rowActivity["CREATED"],
                                "path"    => "https://" . SITE_SERVER_NAME . $file["fileURL"]
                            ];
                        }
                    } elseif (!empty($rowActivity['DISK_FILES'])) {
                        foreach ($rowActivity['DISK_FILES'] as $file) {
                            if ($file = \Bitrix\Disk\File::loadById($file['ID'])) {
                                $arFile = \CFile::GetFileArray($file->GetFileId());
                                $filePath[] = [
                                    "created" => $rowActivity["CREATED"],
                                    "path"    => "https://" . SITE_SERVER_NAME . $arFile["SRC"]
                                ];
                            }
                        }
                    }
                }
            }
        }

        $arData = ['items' => $filePath];

        return $arData;
    }

    /**
     * Список сделок в ЛК риэлтора
     *
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\NotImplementedException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
	protected function realtor_deal()
	{
        $bDebug = $_GET['debug'] == 'Y';

	    if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");
			\CModule::IncludeModule('disk');
			\CModule::IncludeModule('intranet');

			$arData['count'] = 0;

		    $realtorId = IntVal($this->request['realtor_id']);
			$multiple = IntVal($this->request['multiple']);
			if($multiple == 1){
				$realtorId = explode("|", $this->request['realtor_id']);
			}

			$arSelect = array(
				"ID",
				"NAME",
				"OPPORTUNITY",
				"STAGE_ID",
				"CLOSED",
				"OPENED",
				"CONTACT_ID",
				"CATEGORY_ID",
				"COMPANY_ID",
				"CONTACT_FULL_NAME",
				"ASSIGNED_BY",
				"DATE_CREATE",
				"UF_CRM_CHANNEL", // канал привлечения
				"UF_CRM_STAGE_DATE", // дата изменения стадии
				"UF_COMMENT_BINGO", // Комментарий БИНГО
				"UF_CRM_REALTOR_2",
				"UF_CRM_AGENCY_2",
			);

			// статусы сделки
			$arData['stage'] = $this->stageDeal();

            $filterContact = array(
                'UF_IS_BINGO' => 1,
                array(
                    'LOGIC' => 'OR',
                    array("UF_CRM_REALTOR" => $realtorId),
                    array("UF_CRM_REALTOR_2" => $realtorId)
                )
            );

            if(IntVal($this->request['id'])){
                $filterContact = array(
                    'UF_IS_BINGO' => 1,
                    'ID' => IntVal($this->request['id'])
                );
            }

			// поиск всех контактов риэлтора
			$rsClient = \CCrmContact::GetListEx(
			    array(),
                $filterContact,
                false,
                false,
                array(
                    'ID', 'NAME', 'ASSIGNED_BY_ID', 'LAST_NAME', 'UF_BINGO_DATE_SET',
                    'UF_CRM_STATUS', 'UF_COMMENT_BINGO', 'UF_CRM_REALTOR', 'UF_CRM_REALTOR_2'
                )
            );

			while ($arClient = $rsClient->Fetch()) {

			    if(!$realtorId)
                    $realtorId = intval($arClient['UF_CRM_REALTOR']);

                if(!$realtorId)
                    $realtorId = intval($arClient['UF_CRM_REALTOR_2']);

				// вытягиваем телефонные звонки, если таковы имеются
				$filePath = [];

				$filterCall = array(
					'>=CREATED' => $arClient['UF_BINGO_DATE_SET'],
					'COMPLETED' => "Y",
					'?PROVIDER_TYPE_ID' => 'MEETING || CALL',
					'BINDINGS' => array(array('OWNER_TYPE_ID' => 3, 'OWNER_ID' => $arClient['ID'] ))
				);

				$dbResultActivity = \CCrmActivity::GetList(
					array(),
					$filterCall,
					false,
					false,
					array()
				);
				while ($rowActivity = $dbResultActivity->fetch()) {
                    $rowActivity['FILES'] = $rowActivity['WEBDAV_ELEMENTS'] = $rowActivity['DISK_FILES'] = array();

                    \CCrmActivity::PrepareStorageElementIDs($rowActivity);
                    \CCrmActivity::PrepareStorageElementInfo($rowActivity);

                    if(!empty($rowActivity['FILES']))
                    {
                        foreach($rowActivity['FILES'] as $file)
                        {
                            $filePath[] = array(
                                "created" => $rowActivity["CREATED"],
                                "path" => "https://".SITE_SERVER_NAME.$file["fileURL"]
                            );
                        }
                    }
                    elseif(!empty($rowActivity['DISK_FILES']))
                    {
                        foreach($rowActivity['DISK_FILES'] as $file)
                        {
                            if ($file = \Bitrix\Disk\File::loadById($file['ID'])) {
                                $arFile = \CFile::GetFileArray($file->GetFileId());
                                $filePath[] = array(
                                    "created" => $rowActivity["CREATED"],
                                    "path" => "https://".SITE_SERVER_NAME.$arFile["SRC"]
                                );
                            }
                        }
                    }
				}

				// контакт клиента
				$clientPhone = '';
				$rsPhone = \CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arClient['ID'], 'TYPE_ID' =>  'PHONE')
				);
				while ($arPhone = $rsPhone->Fetch()) {
					$clientPhone = $arPhone['VALUE'];
				}

				// данные менеджера контакта
				$arUser = \CUser::GetByID($arClient['ASSIGNED_BY_ID'])->Fetch();

				// ответственный по сделке
				$assignedFullName = $arUser['LAST_NAME']." ".$arUser['NAME']." ".$arUser['SECOND_NAME'];
				// телефон ответственного
				$assignedPhone = $arUser['WORK_PHONE']?$arUser['WORK_PHONE']:$arUser['PERSONAL_MOBILE'];

				// если пользователь из подразделения "Колл-центр", тогда выводить другие данные
				$departmentUser = \CIntranetUtils::GetUserDepartments($arUser['ID']);

				$arrNameDepartment = [];
				$dbDepartment = \CIBlockSection::GetList(array(), array('ID' => $departmentUser));
				while ($arSection = $dbDepartment->Fetch()) {
					$arrNameDepartment[] = $arSection['NAME'];
				}
				if (in_array("Call-Центр", $arrNameDepartment)) {
					$assignedFullName = "Call-Центр";
					$assignedPhone = "(383) 311-05-05";
				}

				$isNewFormatComment = false;
				if(substr_count($arClient['UF_COMMENT_BINGO'], "{") > 0) {
					$arComment = json_decode(htmlspecialchars_decode($arClient['UF_COMMENT_BINGO']), true);
					$comment = "";
					foreach($arComment as $itemComment){
						$comment .= $itemComment['text']."\r\n";
					}
					$isNewFormatComment = true;
				}
				else{
					$comment = $arClient['UF_COMMENT_BINGO'];
					$arComment = [];
				}

				$arClient['CONTACT_PHONE'] = $clientPhone;
				$arClient['ASSIGNED_PHONE'] = $assignedPhone;
				$arClient['ASSIGNED_BY_FULL_NAME'] = $assignedFullName;
				$arClient['COMMENTS'] = $comment;
				$arClient['NEW_FORMAT_COMMENT'] = $isNewFormatComment?1:0;
				$arClient['COMMENT_ITEMS'] = $arComment;
				$arClient['CALL_FILES'] = $filePath;
				$arClient['REALTOR'] = IntVal($arClient['UF_CRM_REALTOR'])>0?$arClient['UF_CRM_REALTOR']:$arClient['UF_CRM_REALTOR_2'];

				$arClient['STATUS'] = Base\Tools::getValueInUFPropEnumID("UF_CRM_STATUS", $arClient['UF_CRM_STATUS']);

				$arData['client'][$arClient['ID']] = $arClient;

				$arFilter = array(
					"UF_IS_BINGO" => 1,
					"CONTACT_ID" => $arClient['ID'],
					array(
						'LOGIC' => 'OR',
						array("UF_CRM_REALTOR" => $realtorId),
						array("UF_CRM_REALTOR_2" => $realtorId),
					),
					">=DATE_CREATE" => $arClient['UF_BINGO_DATE_SET']
				);
				//p2f($arFilter);

				// Последняя сделка клиента
				$resDeals = \CCrmDeal::GetListEx(
					array('DATE_CREATE' => 'DESC'),
					$arFilter, // сделки НЕ менеджеров
					false,
					false,
					$arSelect
				);
				while ($arDeal = $resDeals->Fetch()) {

					$rsChannel = \CIBlockSection::GetList([], ['ID' => $arDeal['UF_CRM_CHANNEL']], false, ['ID', 'NAME']);
					$arChannel = $rsChannel->Fetch();
					if(strlen($arChannel['NAME']) > 0) {
						$arDeal['UF_CRM_CHANNEL'] = $arChannel['NAME'];
					}

					// данные менеджера
					$arUser = \CUser::GetByID($arDeal['ASSIGNED_BY'])->Fetch();

					$categoryId = DealCategory::resolveFromStageID($arDeal['STAGE_ID']);
					$stageEntityId = 'DEAL_STAGE';
					if ($categoryId > 0) {
						$stageEntityId .= '_' . $categoryId;
					}

					$stageName = Crm\Category\DealCategory::getStageName($arDeal['STAGE_ID'], $categoryId);
					if ($arDeal['CLOSED'] == 'Y' && $arDeal['STAGE_ID'] != 'WON') {
						$stageName = 'Проиграна';
					} elseif ($arDeal['STAGE_ID'] == 'WON') {
						$stageName = 'Выиграна';
					}

					$arPaymentSum = IntVal($arDeal['OPPORTUNITY']);

					// поступления
					$transactionResult = Project\Entity\PaymentTable::getList([
						'select' => [
							'*',
						],
						'filter' => [
							'=UF_DEAL' => (int)$arDeal['ID'],
							'>UF_SUM' => 0,
						],
						'order' => [
							'UF_DATE' => 'ASC',
						],
					]);
					$transactionSum = 0;
					while ($transaction = $transactionResult->fetch()) {
						$transaction['UF_SUM'] = (float)$transaction['UF_SUM'];

						$transactionSum += $transaction['UF_SUM'];
					}

					// ответственный по сделке
					$assignedFullName = $arUser['LAST_NAME']." ".$arUser['NAME']." ".$arUser['SECOND_NAME'];
					// телефон ответственного
					$assignedPhone = $arUser['WORK_PHONE']?$arUser['WORK_PHONE']:$arUser['PERSONAL_MOBILE'];
					// если пользователь из подразделения "Колл-центр", тогда выводить другие данные
					$departmentUser = \CIntranetUtils::GetUserDepartments($arUser['ID']);

					$arrNameDepartment = [];
					$dbDepartment = \CIBlockSection::GetList(array(), array('ID' => $departmentUser));
					while ($arSection = $dbDepartment->Fetch()) {
						$arrNameDepartment[] = $arSection['NAME'];
					}
					if (in_array("Call-Центр", $arrNameDepartment)) {
						$assignedFullName = "Call-Центр";
						$assignedPhone = "(383) 311-05-05";
					}

					$item = [
						'ID' => $arDeal['ID'],
						'DATE_CREATE' => $arDeal['DATE_CREATE'],
						'CONTACT_FULL_NAME' => $arDeal['CONTACT_FULL_NAME'],
						'CONTACT_PHONE' => $clientPhone,
						'ASSIGNED_BY' => $arDeal['ASSIGNED_BY'],
						'ASSIGNED_PHONE' => $assignedPhone,
						'ASSIGNED_BY_FULL_NAME' => $assignedFullName,
						'CHANNEL' => $arDeal['UF_CRM_CHANNEL'],
						'BINGO_DATE_SET' => date('d.m.Y', strtotime($arClient['UF_BINGO_DATE_SET'])),
						'STAGE_ID' => $arDeal['STAGE_ID'],
						'CONTACT_ID' => $arClient['ID'],
						'STAGE_CATEGORY_ID' => $stageEntityId,
						'STAGE_COLOR' => $arData['stage'][$arDeal['STAGE_ID']]['COLOR'],
						'STAGE_NAME' => $stageName,
						'PAYMENT_SUM' => $arPaymentSum,
						'BALANCE_SUM' => ($transactionSum <= $arPaymentSum)?$transactionSum:$arPaymentSum,
						'RESIDUAL_SUM' => $arPaymentSum - $transactionSum,
						'CALL_FILES' => $filePath,
						'DATE_CHANGE_STAGE' => $arDeal['UF_CRM_STAGE_DATE'],
						'COMMENTS' => $arDeal['UF_COMMENT_BINGO'],
						'DISCOUNT' => (IntVal($arDeal['UF_CRM_REALTOR_2']) > 0)?true:false,
						'DISCOUNT_VALUE' => (IntVal($arDeal['UF_CRM_REALTOR_2']) > 0)?50:0,
						'REALTOR' => IntVal($arClient['UF_CRM_REALTOR'])>0?$arClient['UF_CRM_REALTOR']:$arClient['UF_CRM_REALTOR_2'],
						'DEAL_INFO' => $arDeal
					];

					if (strlen($item['ASSIGNED_PHONE']) == 0) {
						$item['ASSIGNED_PHONE'] = '-';
					}

					$arData['info'][] = $item;
					$arData['count']++;
				}
			}

			if (IntVal($arData['count']) > 0) {
				$arData['message'] = IntVal($arData['count'])." records processed";
			} else {
				$arData['message'] = "0 records processed";
				$arData['count'] = 0;
			}

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Для смены поля "Дата смены статуса сделки"
	 *
	 * @return array
	 */
	protected function deal_update()
	{
		if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");
			\CModule::IncludeModule("bizproc");

			$dealId = IntVal($this->request['id']);

			if (IntVal($this->request['date_change_stage']) > 0) {
				$dateChangeStage = \ConvertTimeStamp(false, 'FULL');
			}

			$realtorId = IntVal($this->request['realtor_id']);

			$dealEntity = new \CCrmDeal();

			if (IntVal($dealId) > 0) {
				if ($realtorId > 0) {
					// данные по риэлтору
					$arRealtor = \CCrmContact::GetListEx(array(), array("ID"=>$realtorId), false, false, array('ID', 'UF_CRM_AGENCY', 'ASSIGNED_BY_ID', "COMPANY_ID"))->Fetch();
				}

				$fields = [
					'UF_CRM_STAGE_DATE' => $dateChangeStage
				];

				$dealEntity->Update($dealId, $fields);
			}

			$arData['message'] = "1 records save";
			$arData['count'] = 1;
			$arData['deal'] = $dealId;



			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Для смены поля "Дата уведомления"
	 *
	 * @return array
	 */
	protected function contact_update()
	{
		if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");
			\CModule::IncludeModule("bizproc");

			$contactId = IntVal($this->request['id']);

			if (IntVal($this->request['date_change_stage']) > 0) {
				$dateChangeStage = \ConvertTimeStamp(false, 'FULL');
			}

			$realtorId = IntVal($this->request['realtor_id']);

			$dealEntity = new \CCrmContact();

			if (IntVal($contactId) > 0) {
				if ($realtorId > 0) {
					// данные по риэлтору
					$arRealtor = \CCrmContact::GetListEx(array(), array("ID"=>$realtorId), false, false, array('ID', 'UF_CRM_AGENCY', 'ASSIGNED_BY_ID', "COMPANY_ID"))->Fetch();
				}

				$fields = [
					'UF_BINGO_DATE_SET' => $dateChangeStage
				];

				$dealEntity->Update($contactId, $fields);
			}

			$arData['message'] = "1 records save";
			$arData['count'] = 1;
			$arData['contact'] = $contactId;



			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	protected function create_deal()
	{
		if ($this->method == 'GET') {
			$defaultManager = 42; // Менеджеры

			$arData = array();

			\CModule::IncludeModule("crm");
			\CModule::IncludeModule("bizproc");
			\CModule::IncludeModule("intranet");
			p2log("------------".date("d.m.Y H:i:s")."-------------------", "bingo_create_deal");
			p2log($this->request, "bingo_create_deal");

			$phone = trim($this->request['phone']);

			// удаляем префик, код страны
			$searchPhone = substr($phone, 1, strlen($phone));

			$phoneValue = str_replace(array(" ", "(", ")", "-"), array("","","",""), $phone);

			$phoneValue1 = substr($phoneValue, 1, strlen($phoneValue));

			$phoneValue2 = str_replace(array(" ", "(", ")"), array(""," "," "), $phone);
			$phoneValue2 = substr($phoneValue2, 1, strlen($phoneValue2));

			// менеджер который работает с текущим риэлтором
			$managerId = IntVal($this->request['manager_id']);

			$realtorId = IntVal($this->request['realtor_id']);

			// поиск клиента
			$contactId = IntVal(\CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'CONTACT', '%VALUE' => $searchPhone, 'TYPE_ID' =>  'PHONE')
			)->Fetch()['ELEMENT_ID']);

			// найти нужный канал
			$arChannel = \CIBlockSection::GetList(array(), array("IBLOCK_ID"=>29, "NAME"=>"Агентство недвижимости"))->Fetch();

			// найти нужный источник
			$arSource = \CIBlockElement::GetList(array(), array("IBLOCK_ID"=>29, "NAME"=>"Риэлтор"))->Fetch();

			if ($contactId == 0) {
				// поиск клиента по номеру телефона
				$contactId = IntVal(\CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue1, 'TYPE_ID' =>  'PHONE')
				)->Fetch()['ELEMENT_ID']);

				if ($contactId == 0) {
					$contactId = IntVal(\CCrmFieldMulti::GetList(
						array('ID' => 'asc'),
						array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue2, 'TYPE_ID' =>  'PHONE')
					)->Fetch()['ELEMENT_ID']);
				}
			}
			p2log("Contact: ".$contactId, "bingo_create_deal");

			// данные по риэлтору
			$arRealtor = \CCrmContact::GetListEx(array(), array("ID"=>$realtorId), false, false, array('ID', 'UF_CRM_AGENCY', 'ASSIGNED_BY_ID', "COMPANY_ID"))->Fetch();

			$contact = new \CCrmContact();

			if ($contactId > 0) {
				// данные клиента
				$arClient = \CCrmContact::GetListEx(array(), array("ID" => $contactId), false, false, array('ID', 'NAME', 'ASSIGNED_BY_ID', 'LAST_NAME'))->Fetch();

				// обновляем данные по контакту
				// записываем риэлтора
				$arFieldsClient = [
					'UF_IS_BINGO' => 1,
					'UF_CRM_AGENCY' => $arRealtor['UF_CRM_AGENCY'], // Агенство недвижимости / в новой будет UF_CRM_AGENCY
					'UF_BINGO_DATE_SET' => date('d.m.Y H:i:s', (mktime()-10)), // Дата уведомления
					'UF_CRM_REALTOR' => $realtorId, // Риэлтор БИНГО
					'UF_CRM_CHANNEL' => $arChannel['ID'],
					'UF_CRM_SOURCE' => $arSource['ID'],
					'UF_CRM_STATUS' => Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "В работе")
				];
				$contact->Update($arClient['ID'], $arFieldsClient);
			} else {
				$arFullName = explode(' ', $this->request['fio']);
				$arClient = [
					'ASSIGNED_BY_ID' => $defaultManager, // Менеджеры
					'NAME' => $arFullName[1],
					'LAST_NAME' => $arFullName[0],
					'UF_IS_BINGO' => 1,
					'FM' => array(
						'PHONE' => array(
							'n0' => array(
								'VALUE_TYPE' => 'WORK',
								'VALUE' => $phoneValue
							)
						)

					),
					'UF_CRM_AGENCY' => $arRealtor['UF_CRM_AGENCY'],
					'UF_BINGO_DATE_SET' => date('d.m.Y H:i:s'), // Дата уведомления
					'UF_CRM_REALTOR' => $realtorId, // Риэлтор БИНГО
					'UF_CRM_PORTRAIT' => $this->request['messages'], // Портрет клиента
					'UF_CRM_CHANNEL' => $arChannel['ID'],
					'UF_CRM_SOURCE' => $arSource['ID'],
					'UF_CRM_STATUS' => Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "В работе")
				];
				$contactId = $contact->Add($arClient, true);
			}

			$fields = [
				'TITLE' => $phoneValue,
				'CONTACT_ID' => $contactId,
				'UF_CRM_CHANNEL' => $arChannel['ID'],
				'UF_CRM_SOURCE' => $arSource['ID'],
				'UF_CRM_REALTOR' => $arRealtor['ID'], // Риэлтор
				'UF_CRM_AGENCY' => $arRealtor['UF_CRM_AGENCY']?$arRealtor['UF_CRM_AGENCY']:$arRealtor["COMPANY_ID"], // Агенство недвижимости
				'ASSIGNED_BY_ID' => $managerId?$managerId:$arRealtor['ASSIGNED_BY_ID'],
				'UF_IS_BINGO' => 1 , // Создано из БИНГО
			];
			p2log($fields, "bingo_create_deal");

			$deal = new \CCrmDeal();
			$dealId = $deal->Add($fields, true);

			if (IntVal($dealId) > 0) {
				// сделка успешно создана, делаем запрос на отправку СМС для менеджера
				if ((IntVal($managerId) > 0) && ($managerId != $defaultManager)) {
					$arManager = \CUser::GetList($by = "ID", $order = "ASC", array("ID"=>$managerId), array("FIELDS"=>array("ID", "PERSONAL_MOBILE", "LAST_NAME", "NAME", "SECOND_NAME", "PERSONAL_PHOTO", "WORK_PHONE"), "SELECT" => array("UF_DEPARTMENT")))->Fetch();

					// формируем параметры для сообщения и само сообщение
					$phoneManager = $arManager['WORK_PHONE']?$arManager['WORK_PHONE']:$arManager['PERSONAL_MOBILE'];
					$serverName = \COption::GetOptionString("studiobit.base", "server_name_rest_api", "gk-strizhi.ru");
					$message = 'Бинго. клиент ' . $phoneValue . ' https://crm.gk-strizhi.ru/crm/contact/details/'.$contactId.'/';

					// отправляем смс
					$jsonResult = file_get_contents('http://' . $serverName . '/api/v1/sms/?apiKey=123456&message=' . urlencode($message) . '&phone=' . $phoneManager);

					// найти руководителя менеджера
					$arHeadUserList = \CIntranetUtils::GetDepartmentManager(is_array($arManager['UF_DEPARTMENT'])?$arManager['UF_DEPARTMENT']:array($arManager['UF_DEPARTMENT']));

					// отправить email всем руководителям этого пользователя
					foreach ($arHeadUserList as $userItem) {
						$arHead = \CUser::GetList($by = "ID", $order = "ASC", array("ID"=>$userItem['ID']), array("FIELDS"=>array("ID", "PERSONAL_MOBILE", "LAST_NAME", "NAME", "EMAIL", "PERSONAL_PHOTO", "WORK_PHONE")))->Fetch();
						$arEventFields = array(
							"EMAILTO" => $arHead['EMAIL'],
							"LINK" => "Менеджеру ".$arManager['LAST_NAME']." ".$arManager['NAME']." отправлено уведомление по клиенту ".$phoneValue,
							"SUBJECT" => "Получено уведомление из БИНГО"
						);

						\CEvent::SendImmediate("CRON_OBJECTS", SITE_ID, $arEventFields);

						$phoneHead = $arHead['WORK_PHONE']?$arHead['WORK_PHONE']:$arHead['PERSONAL_MOBILE'];
						$messageHead = 'Бинго для '.$arManager['LAST_NAME']." ".$arManager['NAME'].', тел ' . $phoneValue. ' https://crm.gk-strizhi.ru/crm/contact/details/'.$contactId.'/';

						// отправляем смс
						$jsonResult = file_get_contents('http://' . $serverName . '/api/v1/sms/?apiKey=123456&message=' . urlencode($messageHead) . '&phone=' . $phoneHead);

						p2log($jsonResult, "bingo_create_deal");
						p2log($messageHead, "bingo_create_deal");
					}

					p2log($jsonResult, "bingo_create_deal");
					p2log($message, "bingo_create_deal");
				}

				$arErrors = [];
				\CBPDocument::StartWorkflow(
					27, // TODO ID бизнес-процесса создания сделки из БИНГО
					array("crm", "CCrmDocumentDeal", 'DEAL_' . $dealId),
					array(),
					$arErrors
				);
			} else {
				p2log($fields, "bingo_create_deal");
				p2log($deal->LAST_ERROR, "bingo_create_deal");
			}
			p2log("------------------------------------------", "bingo_create_deal");

			$arData['message'] = "1 records save";
			$arData['count'] = 1;
			$arData['deal'] = $dealId;



			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Создание контакта
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 */
	protected function create_contact()
	{
		if ($this->method == 'GET')
		{
            p2log("------------".date("d.m.Y H:i:s")."-------------------", "bingo_create_contact");
            p2log($this->request, "bingo_create_contact");

		    $check = self::find_deal();
		    if($check['limit']){
                p2log('deal is find in crm', "bingo_create_contact");
                return array("error" => "deal is find in crm");
            }

			$defaultManager = 42; // Менеджеры

			$arData = array();

			\CModule::IncludeModule("crm");
			\CModule::IncludeModule("bizproc");
			\CModule::IncludeModule("intranet");


			$phone = trim($this->request['phone']);

			// удаляем префик, код страны
			$searchPhone = substr($phone, 1, strlen($phone));

			$phoneValue = str_replace(array(" ", "(", ")", "-"), array("","","",""), $phone);

			$phoneValue1 = substr($phoneValue, 1, strlen($phoneValue));

			$phoneValue2 = str_replace(array(" ", "(", ")"), array(""," "," "), $phone);
			$phoneValue2 = substr($phoneValue2, 1, strlen($phoneValue2));

			// менеджер который работает с текущим риэлтором
			$managerId = IntVal($this->request['manager_id']);

			$realtorId = IntVal($this->request['realtor_id']);

			// поиск клиента
			$contactId = IntVal(\CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'CONTACT', '%VALUE' => $searchPhone, 'TYPE_ID' =>  'PHONE')
			)->Fetch()['ELEMENT_ID']);

			if ($contactId == 0) {
				// поиск клиента по номеру телефона
				$contactId = IntVal(\CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue1, 'TYPE_ID' =>  'PHONE')
				)->Fetch()['ELEMENT_ID']);

				if ($contactId == 0) {
					$contactId = IntVal(\CCrmFieldMulti::GetList(
						array('ID' => 'asc'),
						array('ENTITY_ID' => 'CONTACT', '%VALUE' => $phoneValue2, 'TYPE_ID' =>  'PHONE')
					)->Fetch()['ELEMENT_ID']);
				}
			}
			p2log("Contact: ".$contactId, "bingo_create_contact");

			// найти нужный канал
			$arChannel = \CIBlockSection::GetList(array(), array("IBLOCK_ID"=>29, "NAME"=>"Агентство недвижимости"))->Fetch();

			// найти нужный источник
			$arSource = \CIBlockElement::GetList(array(), array("IBLOCK_ID"=>29, "NAME"=>"Риэлтор"))->Fetch();

			// данные по риэлтору
			$arRealtor = \CCrmContact::GetListEx(array(), array("ID"=>$realtorId), false, false, array('ID', 'UF_CRM_AGENCY', 'ASSIGNED_BY_ID', "COMPANY_ID"))->Fetch();

			$contact = new \CCrmContact();

			if ($contactId > 0) {
				// данные клиента
				$arClient = \CCrmContact::GetListEx(array(), array("ID" => $contactId), false, false, array('ID', 'NAME', 'ASSIGNED_BY_ID', 'LAST_NAME', 'UF_CRM_PORTRAIT'))->Fetch();

				$messageBingo = $this->request['messages'];
				if(strlen($arClient["UF_CRM_PORTRAIT"]) > 0){
					$messageBingo .= "\r\n\r\n".$arClient["UF_CRM_PORTRAIT"];
				}

				// обновляем данные по контакту
				// записываем риэлтора
				$arFieldsClient = [
					'ASSIGNED_BY_ID' => $managerId?$managerId:$defaultManager,
					'UF_IS_BINGO' => 1,
					'UF_CRM_AGENCY' => $arRealtor['COMPANY_ID'], // Агенство недвижимости / в новой будет UF_CRM_AGENCY
					'UF_BINGO_DATE_SET' => date('d.m.Y H:i:s', (mktime()-10)), // Дата уведомления
					'UF_CRM_REALTOR' => $realtorId, // Риэлтор БИНГО
					'UF_CRM_CHANNEL' => $arChannel['ID'],
					'UF_CRM_SOURCE' => $arSource['ID'],
					'UF_CRM_PORTRAIT' => $messageBingo, // портрет клиента
					'UF_CRM_STATUS' => Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "В работе")
				];
				if(!$contact->Update($arClient['ID'], $arFieldsClient)){
                    p2log($contact->LAST_ERROR, "bingo_create_contact");
                }
			} else {
				$arFullName = explode(' ', $this->request['fio']);
				$arClient = [
					'ASSIGNED_BY_ID' => $managerId?$managerId:$defaultManager,
					'NAME' => $arFullName[1],
					'LAST_NAME' => $arFullName[0],
					'UF_IS_BINGO' => 1,
					'FM' => array(
						'PHONE' => array(
							'n0' => array(
								'VALUE_TYPE' => 'WORK',
								'VALUE' => $phoneValue
							)
						)

					),
					'UF_CRM_AGENCY' => $arRealtor['COMPANY_ID'],
					'UF_BINGO_DATE_SET' => date('d.m.Y H:i:s'), // Дата уведомления
					'UF_CRM_REALTOR' => $realtorId, // Риэлтор БИНГО
					'UF_CRM_PORTRAIT' => $this->request['messages'], // Портрет клиента
					'UF_CRM_CHANNEL' => $arChannel['ID'],
					'UF_CRM_SOURCE' => $arSource['ID'],
					'UF_CRM_STATUS' => Base\Tools::getIDInUFPropEnum("UF_CRM_STATUS", "В работе")
				];
				$contactId = $contact->Add($arClient, true);

				if($contactId == false){
                    p2log($contact->LAST_ERROR, "bingo_create_contact");
                }
			}

            p2log("Contact add\update success: ".$contactId, "bingo_create_contact");

			if (IntVal($contactId) > 0)
			{
				// сделка успешно создана, делаем запрос на отправку СМС для менеджера
				if ((IntVal($managerId) > 0) && ($managerId != $defaultManager)) {
					$arManager = \CUser::GetList($by = "ID", $order = "ASC", array("ID"=>$managerId), array("FIELDS"=>array("ID", "PERSONAL_MOBILE", "LAST_NAME", "NAME", "SECOND_NAME", "PERSONAL_PHOTO", "WORK_PHONE"), "SELECT" => array("UF_DEPARTMENT")))->Fetch();

					// формируем параметры для сообщения и само сообщение
					$phoneManager = $arManager['WORK_PHONE']?$arManager['WORK_PHONE']:$arManager['PERSONAL_MOBILE'];
					$serverName = \COption::GetOptionString("studiobit.base", "server_name_rest_api", "gk-strizhi.ru");
                    $message = 'Бинго. клиент ' . $phoneValue . ' https://crm.gk-strizhi.ru/crm/contact/details/'.$contactId.'/';

					// отправляем смс
					$jsonResult = file_get_contents('http://' . $serverName . '/api/v1/sms/?apiKey=123456&message=' . urlencode($message) . '&phone=' . $phoneManager);

					$departmentList = is_array($arManager['UF_DEPARTMENT'])?$arManager['UF_DEPARTMENT']:array($arManager['UF_DEPARTMENT']);
					// найти руководителя менеджера
					$arHeadUserList = \CIntranetUtils::GetDepartmentManager($departmentList);

					// найти контролеров
					$arControllerUserList = Project\Tools::getDepartmentController($departmentList);

					// все сообщения для руководителей дублируются контролерам
					$arHeadUserList = array_merge($arHeadUserList, $arControllerUserList);

					// отправить email всем руководителям этого пользователя
					foreach ($arHeadUserList as $userItem) {
						$arHead = \CUser::GetList($by = "ID", $order = "ASC", array("ID"=>$userItem['ID']), array("FIELDS"=>array("ID", "PERSONAL_MOBILE", "LAST_NAME", "NAME", "EMAIL", "PERSONAL_PHOTO", "WORK_PHONE")))->Fetch();
						$arEventFields = array(
							"EMAILTO" => $arHead['EMAIL'],
							"LINK" => "Менеджеру ".$arManager['LAST_NAME']." ".$arManager['NAME']." отправлено уведомление по клиенту ".$phoneValue,
							"SUBJECT" => "Получено уведомление из БИНГО"
						);

						\CEvent::SendImmediate("CRON_OBJECTS", SITE_ID, $arEventFields);

						$phoneHead = $arHead['WORK_PHONE']?$arHead['WORK_PHONE']:$arHead['PERSONAL_MOBILE'];
                        $messageHead = 'Бинго для '.$arManager['LAST_NAME']." ".$arManager['NAME'].', тел ' . $phoneValue. ' https://crm.gk-strizhi.ru/crm/contact/details/'.$contactId.'/';


						// отправляем смс
						$jsonResult = file_get_contents('http://' . $serverName . '/api/v1/sms/?apiKey=123456&message=' . urlencode($messageHead) . '&phone=' . $phoneHead);

						p2log($jsonResult, "bingo_create_contact");
						p2log($messageHead, "bingo_create_contact");
					}

					p2log($jsonResult, "bingo_create_contact");
					p2log($message, "bingo_create_contact");
				}

				$arErrors = [];
				\CBPDocument::StartWorkflow(
					32, // TODO ID бизнес-процесса создания контакта из БИНГО
					array("crm", "CCrmDocumentContact", 'CONTACT_' . $contactId),
					array(),
					$arErrors
				);

				try{
                    // создание комментария в лете риэлтора
                    \Bitrix\Crm\Timeline\CommentEntry::create(
                        array(
                            'TEXT' => "Принято уведомление по клиенту: ".$this->request['fio']." (".$phone.")",
                            'CREATED' => date('d.m.Y H:i:s'),
                            'SETTINGS' => array('HAS_FILES' => 'N'),
                            'AUTHOR_ID' => $managerId?$managerId:$defaultManager,
                            'BINDINGS' => array(array('ENTITY_TYPE_ID' => 3, 'ENTITY_ID' => $realtorId))
                        )
                    );
                }
                catch (\Exception $e){
                    p2log($e->getMessage(), "bingo_create_contact");
                }
			}

			$arData['message'] = "1 records save";
			$arData['count'] = 1;
			$arData['contact'] = $contactId;

            p2log($arData, "bingo_create_contact");
            p2log("------------------------------------------", "bingo_create_contact");

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * Контакы риэлтора
	 *
	 * @return array
	 */
	protected function contact()
	{
		if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");

			$select = array(
				'ID',
				'UF_CRM_AGENCY', // Агенство недвижимости
				'ASSIGNED_BY_ID'
			);
			// данные по риэлтору
			$rsRealtor = \CCrmContact::GetListEx(array(), array("ID"=>IntVal($this->request['realtor_id'])), false, false, $select);

			if ($arRealtor = $rsRealtor->Fetch()) {
				// данные менеджера
				$arUser = \CUser::GetByID($arRealtor['ASSIGNED_BY_ID'])->Fetch();
				$arRealtor['ASSIGNED_PHONE'] = $arUser['WORK_PHONE']?$arUser['WORK_PHONE']:$arUser['PERSONAL_MOBILE'];
				$arRealtor['ASSIGNED_BY_FULL_NAME'] = $arUser['LAST_NAME']." ".$arUser['NAME']." ".$arUser['SECOND_NAME'];
				$arData['data'] = $arRealtor;
			}



			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

	/**
	 * менеджеры которые доступны для БИНГО
	 *
	 * @return array
	 */
	protected function managers()
	{
		if ($this->method == 'GET') {
			\CModule::IncludeModule("crm");

			$filter = array("UF_IS_BINGO"=>1, "ACTIVE" => "Y");

			if((int)$this->request['id']){
			    $filter['ID'] = (int)$this->request['id'];
            }

			// данные по риэлтору
			$rsContact = \CUser::GetList(
			    $by = "ID",
                $order = "ASC",
                $filter,
                array(
                    "FIELDS" => array(
                        "ID",
                        "PERSONAL_MOBILE",
                        "LAST_NAME",
                        "NAME",
                        "SECOND_NAME",
                        "PERSONAL_PHOTO",
                        "WORK_PHONE",
                        "EMAIL"
                    ),
                    "SELECT" => ["UF_HEAD"]
                )
            );

			while ($arUser = $rsContact->Fetch()) {
				$arManager = [];
				$arManager['USER_ID'] = $arUser['ID'];
				$arManager['ID'] = $arUser['ID'];
				$arManager['EMAIL'] = $arUser['EMAIL'];
				$arManager['ASSIGNED_PHONE'] = $arUser['WORK_PHONE']?$arUser['WORK_PHONE']:$arUser['PERSONAL_MOBILE'];
				$arManager['ASSIGNED_BY_FULL_NAME'] = $arUser['LAST_NAME']." ".$arUser['NAME']." ".$arUser['SECOND_NAME'];
				$arManager['ASSIGNED_PHOTO'] = "";

				if($arUser['PERSONAL_PHOTO']){
                    $arManager['ASSIGNED_PHOTO'] = "https://".SITE_SERVER_NAME.\CFile::GetPath($arUser['PERSONAL_PHOTO']);
                    $preview = \CFile::ResizeImageGet($arUser['PERSONAL_PHOTO'], ['width'=> 200, 'height' => 200]);
                    $arManager['ASSIGNED_PHOTO_PREVIEW'] = "https://".SITE_SERVER_NAME.$preview['src'];
                }


				if($arUser['UF_HEAD'] > 0){
                    $arManager['HEAD_FULL_NAME'] = Project\Entity\UserTable::getFullName($arUser['UF_HEAD']);
                    $arHeadItem =   Project\Entity\UserTable::getData($arUser['UF_HEAD']);
                    $arManager['HEAD_EMAIL'] = (!empty($arHeadItem['EMAIL']) ? $arHeadItem['EMAIL'] : '' );
                }

				$arData['data'][] = $arManager;
			}

			return $arData;
		} else {
			return array("error" => "Only accepts GET/POST requests");
		}
	}

    /**
     * Стадии сделок
     *
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\ArgumentNullException
     * @throws Main\ArgumentTypeException
     * @throws Main\NotImplementedException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
	private function stageDeal(){
		// статусы сделки
		$statusResult = Crm\StatusTable::getList([
			'filter' => [
				'=ENTITY_ID' => array(
					'DEAL_STAGE',
					'DEAL_STAGE_1'
				),
			],
			'order' => ['SORT'],
		]);
		$stages = [];
		while ($status = $statusResult->fetch()) {
			$categoryId = DealCategory::resolveFromStageID($status['STATUS_ID']);

			$scheme = Crm\Color\DealStageColorScheme::getByCategory($categoryId);

			$element = $scheme->getElementByName($status['STATUS_ID']);

			$status['COLOR'] = $element->getColor();
			if ($status['STATUS_ID'] == 'WON') {
				$status['COLOR'] = '#1f815f';
			}

			$stages[$status['STATUS_ID']] = $status;
		}

		return $stages;
	}

    /**
     * Устанавливает для реалктора галку "зарегистрирован в Бинго"
     *
     * @return array
     */
    protected function realtor_set_reg()
    {
        if ($this->method == 'GET' || $this->method == 'POST') {
            \CModule::IncludeModule("crm");

            $success = $fail = 0;
            $errors = [];

            $contactId = $this->request['id'];

            $obEntity = new \CCrmContact();

            if (!empty($contactId) > 0) {
                $filter = [/*'TYPE_ID' => 'PARTNER'*/'UF_REG_IN_BINGO' => [0, false]];

                if(is_array($contactId))
                    $filter['ID'] = array_merge([0], $contactId);
                else
                    $filter['ID'] = intval($contactId);

                $arData['filter'] = $filter;

                // данные по риэлтору
                $rs = \CCrmContact::GetListEx(array(), $filter, false, false, array('ID'));

                while($ar = $rs->Fetch()){
                    if($obEntity->Update($ar['ID'], $fields = ['UF_REG_IN_BINGO' => 1])){
                        $success++;
                    }
                    else{
                        $fail++;
                        $errors[] = $obEntity->LAST_ERROR;
                    }
                }
            }

            $arData['message'] = $success." records save, ".$fail." records fail";
            $arData['count'] = $success + $fail;
            $arData['errors'] = $errors;

            return $arData;
        } else {
            return array("error" => "Only accepts GET/POST requests");
        }
    }

    /**
     * Активация риэлтора на сайте
     * На портале находится по ID карточка риелтора и там меняется ответственный на «менеджеры»,
     * ставится галочка «зарегистрирован в Бинго», проверяем заполнено ли поле «компания»,
     * если оно не заполнено отправляется уведомление Н. Мошкиной с текстом –
     * "в карточке риелтора" + <ссылка на карточку контакта типа риелтор> + "не указана компания".
     * @return array
     * @throws Main\ArgumentException
     * @throws Main\LoaderException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    protected function realtor_activate()
    {
        if ($this->method == 'GET' || $this->method == 'POST') {
            \CModule::IncludeModule("crm");

            $success = $fail = 0;
            $errors = [];

            $contactId = (int)$this->request['realtor_id'];

            $obEntity = new \CCrmContact(false);

            if (!empty($contactId) > 0) {
                // данные по риэлтору
                $rs = \CCrmContact::GetListEx(
                    [],
                    ['ID' => $contactId],
                    false,
                    false,
                    ['ID', 'COMPANY_ID', 'ASSIGNED_BY_ID', 'FULL_NAME']
                );

                while($ar = $rs->Fetch()){
                    $fields = ['UF_REG_IN_BINGO' => 1];
                    if((int)$ar['ASSIGNED_BY_ID'] !== Project\MANAGERS){
                        $fields['ASSIGNED_BY_ID'] =  Project\MANAGERS;
                    }

                    if($obEntity->Update($ar['ID'], $fields)){
                        $success++;

                        if(empty($ar['COMPANY_ID'])){
                            $arEventFields = $ar;
                            $arEventFields['LINK'] = Project\Entity\Crm\ContactTable::getUrl($ar['ID']);

                            $userIds = Base\Tools::getUsersByRoleName('Руководитель КЦ');

                            if(!empty($userIds)) {
                                $rsUser = Project\Entity\UserTable::getList([
                                    'filter' => ['ID' => $userIds],
                                    'select' => ['ID', 'EMAIL']
                                ]);

                                while($arUser = $rsUser->fetch()) {
                                    $arEventFields['EMAIL'] = $arUser['EMAIL'];
                                    \CEvent::Send("REALTOR_COMPANY_EMPTY", SITE_ID, $arEventFields);
                                }
                            }
                        }
                    }
                    else{
                        $fail++;
                        $errors[] = $obEntity->LAST_ERROR;
                    }
                    die();
                }
            }

            $arData['message'] = $success." records save, ".$fail." records fail";
            $arData['count'] = $success + $fail;
            $arData['errors'] = $errors;

            return $arData;
        } else {
            return array("error" => "Only accepts GET/POST requests");
        }
    }
}

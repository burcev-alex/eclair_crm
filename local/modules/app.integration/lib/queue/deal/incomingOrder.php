<?php

namespace App\Integration\Queue\Deal;

use \Bitrix\Main\Entity\Query;
use \Bitrix\Main\Type\DateTime;
use \Bitrix\Main\Loader;
use \Bitrix\Main;
use \Bitrix\Crm;
use \Bitrix\Main\Entity;
use \App\Base;
use \App\Base\Tools;
use \Bitrix\Main\Type\Date;
use \App\Integration as Union;
use \Bitrix\Crm\UtmTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;

/**
 * Обработка очереди заказов из сайта барбиллиони
 *
 * Class IncomingOrder
 * @package App\Integration\Queue\Deal
 */
class IncomingOrder extends Union\Queue\AbstractBase implements Union\Queue\Host
{
	protected $contactId = 0;
	protected $leadId = 0;
	protected $contactInfo = [];
	protected $dealId = 0;
	private $category = 0;
	protected $result = [];

	/**
	 * Команда выполнения-обработки данных
	 */
	public function command()
	{
		try {
			$this->processing($this->resource);
		} catch (\Exception $e) {
			p($e->getMessage());
			return false;
		}

		return true;
	}

	/**
	 * Процесс обработки-подготовки данных
	 *
	 * @param $data
	 *
	 * @throws \Exception
	 */
	protected function processing($data)
	{
		// определить наличие order id от CRM
		$this->dealId = 0;

		// если контакт не найден по GUID , тогда находим по номеру телефона
		if ($this->contactId == 0) {
			$this->contactId = $this->searchContactByPhone($data['profile']['phone']);
		}

	    // найти лид, если есть информацию, забрать часть данных из лида
	    if ($this->contactId == 0) {
		    $this->leadId = $this->searchLeadByPhone($data['profile']['phone']);
		    if ($this->leadId == 0) {
			    $this->leadId = $this->searchLeadByEmail($data['profile']['email']);
		    }

		    // лид найден, забираем ТОЛЬКО фамилию, если в исходных данных она пустая
		    if($this->leadId > 0){
		    	if(strlen($data['profile']['lastName']) == 0) {
				    $data['profile']['lastName'] = Union\Entity\Crm\LeadTable::getById($this->leadId)->fetch()['LAST_NAME'];
			    }
		    }
		}

		if($this->contactId == 0 && $this->leadId == 0){
			$this->createContact($data['profile']);
		}

		// сохраняем заказ
		$this->save($data);
	}

	/**
	 * Сохранять или обновить сделку
	 * 
	 * @param $fields
	 *
	 * @return bool
	 */
	protected function save($fields)
	{
		global $APPLICATION;

		if ($this->contactId > 0) {
			$dealEntity = new Crm\DealTable();
			$deal = new \CCrmDeal(false);

			try {
				$fio = $fields['profile']['fullName'];
				if(strlen($fio) == 0){
					$fio = $fields['profile']['phone'];
				}

				$typeId = $this->getTypeDeal("Продажа");

				// определить ответственного за сделку
				$this->setResponsible();

				// определить стадию сделки
				$stageId = "NEW";

				$fieldOrder = [
					'SOURCE_ID' => $this->getSourceId(),
					'CATEGORY_ID' => $this->category,
					'TITLE' => date("d.m.Y H:i:s"),
					'ASSIGNED_BY_ID' => $this->responsibleId,
					'CREATED_BY_ID' => $this->responsibleId,
					'STAGE_ID' => $stageId,
					'STAGE_SEMANTIC_ID' => $stageId,
					'BEGINDATE' => date("d.m.Y H:i:s"),
					"OPPORTUNITY" => $fields["price"],
					"CURRENCY_ID" => "RUB",
					"COMMENTS" => "",
					"DATE_CREATE" => date("d.m.Y H:i:s"),
					"TYPE_ID" => $typeId,
					'CONTACT_ID' => $this->contactId,
					"CONTACT_BINDINGS" => array(
						array(
							"CONTACT_ID" => $this->contactId,
							"SORT" => 10,
							"IS_PRIMARY" => "Y"
						)
					),
					"OPENED" => "Y",
					"CLOSED" => "N",
					"ORIGIN_ID" => $fields["id"],
					"OPPORTUNITY_ACCOUNT" => $fields["price"],
				];

				// снять проверку обязательных полей
				$fieldOrder['APP_EVENT_HANDLERS_DISABLED'] = true;

				// поиск сделки
				$rsDeals = \CCrmDeal::GetList([], ['ORIGIN_ID' => $fields['id']], false, false, ['ID']);
				if($arrDeal = $rsDeals->Fetch()){
					$this->dealId = $arrDeal['ID'];
				}

				if(IntVal($this->dealId) == 0){
					$this->dealId = $deal->Add($fieldOrder);

					$this->saveProduct($fields);

					// обновление воронки
					\CCrmDeal::RebuildStatistics(
						[$this->dealId],
						array(
							'FORCED' => true,
							'ENABLE_SUM_STATISTICS' => true,
							'ENABLE_HISTORY'=> true,
							'ENABLE_INVOICE_STATISTICS' => true,
							'ENABLE_ACTIVITY_STATISTICS' => true
						)
					);
				}

				$this->responce['dealId'] = $this->dealId;

			} catch (\Exception $e) {
				return false;
			}
		} else {
			return false;
		}

		return true;
	}

	/**
	 * Обновление списка продуктов
	 *
	 * @param $fields
	 */
	protected function saveProduct($fields)
    {
		\CModule::IncludeModule('iblock');

		$obProduct = new \CCrmProductRow();

		// собираем продукты из разных типов услуг
		$productList = [];
	    foreach ($fields['basket'] as $key=>$product) {
	    	if(IntVal($product['price']) == 0){
	    		continue;
			}

		    $productList[] = [
			    'name' => $product['name'],
			    'price' => $product['price'],
				'count' => $product['quantity'],
				'sku' => trim($product['property']['PRODUCT.XML_ID'])
		    ];
		}
		
		foreach ($productList as $sort => $itemProduct) {
			$productId = 0;
			// найти товар по артикулу
			$rsElement = \CIBlockElement::GetList([], ['IBLOCK_ID' => 16, 'PROPERTY_ARTICLE' => $itemProduct['sku']], false, false, ['ID', 'PROPERTY_CML2_LINK']);
			while($arElement = $rsElement->Fetch()){
				$productId = $arElement['PROPERTY_CML2_LINK_VALUE'];
			}


			$productItems = array(
				'OWNER_TYPE' => 'D',
				'OWNER_ID' => $this->dealId,
				'DISCOUNT_SUM' => 0,
				'DISCOUNT_RATE' => 0,
				'TAX_RATE' => 0,
				'PRODUCT_ID' => $productId,
				'PRODUCT_NAME' => $itemProduct['name'],
				'PRICE' => ceil($itemProduct['price']),
				'PRICE_BRUTTO' => ceil($itemProduct['price']),
				'PRICE_NETTO' => ceil($itemProduct['price']),
				'PRICE_EXCLUSIVE' => ceil($itemProduct['price']),
				'SUM' => ceil($itemProduct['price']),
				'QUANTITY' => IntVal($itemProduct['count']),
				'CUSTOMIZED' => 'Y',
				'TAX_INCLUDED' => 'N',
				'CURRENCY_ID' => 'RUB',
				'SORT' => $sort+10,
			);

			$productId = $obProduct->Add($productItems);
		}
	}

	/**
	 * Тип сделки
	 *
	 * @param string $name
	 *
	 * @return array|int|string
	 */
	private function getTypeDeal($name = "")
    {
		$result = \CCrmStatus::GetStatusList('DEAL_TYPE');
		foreach ($result as $code => $item) {
			if ($item == $name) {
				$result = $code;
			}
		}

		return $result;
	}

	/**
	 * Источник
	 *
	 * @return array|int|string
	 */
	private function getSourceId()
    {
    	$result = "SELF";

    	$name = "Интернет-магазин";

	    $rs = \CCrmStatus::GetStatusList('SOURCE');
		foreach ($rs as $code => $item) {
			if ($item == $name) {
				$result = $code;
			}
		}

		return $result;
	}

	/**
	 * Поиск пользователья по ФИО
	 * @param $fio
	 *
	 * @return int
	 */
	private function searchUserId($fio)
    {
		$exp = explode(" ", $fio);

		$result = 1;
		$rs = \CUser::GetList($by = "ID", $order = "ASC", ['LAST_NAME' => $exp[0], 'NAME' => $exp[1]], ['FIELDS'=>['ID']]);
		if ($user = $rs->Fetch()) {
			$result = $user['ID'];
		}

		return $result;
	}

	private function setResponsible()
	{
		$result = 1;

		$this->responsibleId = $result;
	}

	/**
	 * Поиск контакта по номеру телефона
	 *
	 * @param $phone
	 *
	 * @return int
	 */
	private function searchContactByPhone($phone)
    {
		$id = 0;

		$entityContact = new Union\Entity\Crm\ContactTable();

		$sql = $entityContact->getSqlForSearchByPhone($phone);
		$rs = $sql->setSelect(['ID'])->exec();
		while ($ar = $rs->fetch()) {
			$id = $ar['ID'];
		}

		return $id;
	}

	/**
	 * Поиск лида по номеру телефона
	 *
	 * @param $phone
	 *
	 * @return int
	 */
	private function searchLeadByPhone($phone)
    {
		$id = 0;

		$entityLead = new Union\Entity\Crm\LeadTable();

		try {
			$sql = $entityLead->getSqlForSearchByPhone($phone);
			$rs = $sql->setSelect(['ID', 'STATUS_ID'])->exec();
			while ($ar = $rs->fetch()) {
				if($ar['STATUS_ID'] == 'CONVERTED') continue;
				$id = $ar['ID'];
			}
		}
		catch (SystemException $e){
			$id = 0;
		}
		catch (ArgumentException $e){
			$id = 0;
		}

		return $id;
	}

	/**
	 * Поиск лида по email
	 *
	 * @param $email
	 *
	 * @return int
	 */
	private function searchLeadByEmail($email)
    {
		$id = 0;

		$entityLead = new Union\Entity\Crm\LeadTable();

		try {
			$sql = $entityLead->getSqlForSearchByEmail($email);
			$rs = $sql->setSelect(['ID', 'STATUS_ID'])->exec();
			while ($ar = $rs->fetch()) {
				if($ar['STATUS_ID'] == 'CONVERTED') continue;
				$id = $ar['ID'];
			}
		}
		catch (SystemException $e){
			$id = 0;
		}
		catch (ArgumentException $e){
			$id = 0;
		}

		return $id;
	}

	private function createContact($item)
    {
		// создать контакт
		$entityContact = new \CCrmContact(false);

		$fio = explode(" ", $item['fullName']);
		if(count($fio) > 1){
			$item['lname'] = $fio[1];
		}
		else{
			$item['lname'] = '';
		}
		
		$item['fname'] = $fio[0];

		if(count($fio) > 2){
			$item['mname'] = $fio[2];
		}
		else{
			$item['mname'] = '';
		}

		$result = [
			'EXPORT' => 'Y',
			'UF_MODIFY_BY_ID' => 1,
			'ASSIGNED_BY_ID' => 1,
			'DATE_CREATE' => date('d.m.Y H:i:s'),
			'LAST_NAME' => $item['lname']?$item['lname']:"-",
		];

		if (strlen($item['fname']) > 0) {
			$result['NAME'] = $item['fname'];
		} elseif ((strlen($item['fname']) == 0) && (array_key_exists('fname', $item))) {
			$result['NAME'] = '';
		}

		if (strlen($item['mname']) > 0) {
			$result['SECOND_NAME'] = $item['mname'];
		} elseif ((strlen($item['mname']) == 0) && (array_key_exists('mname', $item))) {
			$result['SECOND_NAME'] = '';
		}

	    if (strlen($item['phone']) > 0) {
		    $number = 0;

		    $result['FM']['PHONE'] = [
				'n' . $number => [
					'VALUE_TYPE' => "OTHER",
					'VALUE' => $item['phone']
				]
			];
	    }

		if (strlen($item['email']) > 0) {
			$emailItem = explode(",", $item['email']);
			if (count($emailItem) > 1) {
				$item['email'] = $emailItem[0];
			}

			$number = 'n0';

			$result['FM']['EMAIL'] = [
				$number => [
					'VALUE_TYPE' => 'WORK',
					'VALUE' => $item['email']
				]
			];
		}
		else{
			$result['FM']['EMAIL'] = [];
		}

		$this->contactId = $entityContact->Add($result);

		return $this->contactId;
	}
}

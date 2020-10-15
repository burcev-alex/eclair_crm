<?php

namespace App\Integration\Queue\Deal;

use App\Base;
use App\Integration as Union;
use Bitrix\Crm;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;

/**
 * Обработка очереди заказов из сайта барбиллиони.
 *
 * Class IncomingOrder
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
     * Команда выполнения-обработки данных.
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
     * Процесс обработки-подготовки данных.
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
            if ($this->leadId > 0) {
                if (strlen($data['profile']['lastName']) == 0) {
                    $data['profile']['lastName'] = Union\Entity\Crm\LeadTable::getById($this->leadId)->fetch()['LAST_NAME'];
                }
            }
        }

        if ($this->contactId == 0 && $this->leadId == 0) {
            $this->createContact($data['profile']);
        }

        // сохраняем заказ
        $this->save($data);
    }

    /**
     * Сохранять или обновить сделку.
     *
     * @param $fields
     *
     * @return bool
     */
    protected function save($fields)
    {
        \CModule::IncludeModule('crm');
        \CModule::IncludeModule('iblock');
        global $APPLICATION;

        if ($this->contactId > 0) {
            $dealEntity = new Crm\DealTable();
            $objDeal = new \CCrmDeal(false);

            try {
                $fio = $fields['profile']['fullName'];
                if (strlen($fio) == 0) {
                    $fio = $fields['profile']['phone'];
                }

                $typeId = $this->getTypeDeal('Продажа');

                // определить ответственного за сделку
                $this->setResponsible();

                // определить стадию сделки
                $stageId = 'NEW';

                $property = [];
                foreach ($fields['property']['properties'] as $kProp => $valProp) {
                    if ($valProp['TYPE'] == 'LOCATION') {
                        $property[$valProp['NAME']] = implode(' / ', $valProp['VALUE']);
                    } elseif ($valProp['TYPE'] == 'ENUM') {
                        foreach ($valProp['VALUE'] as $value) {
                            $property[$valProp['NAME']] = $valProp['OPTIONS'][$value];
                        }
                    } else {
                        $property[$valProp['NAME']] = implode(' / ', $valProp['VALUE']);
                    }
				}

                $fieldOrder = [
                    'SOURCE_ID' => $this->getSourceId(),
                    'CATEGORY_ID' => $this->category,
                    'TITLE' => date('d.m.Y H:i:s'),
                    'ASSIGNED_BY_ID' => $this->responsibleId,
                    'CREATED_BY_ID' => $this->responsibleId,
                    'STAGE_ID' => $stageId,
                    'STAGE_SEMANTIC_ID' => $stageId,
                    'BEGINDATE' => date('d.m.Y H:i:s'),
                    'OPPORTUNITY' => $fields['price'],
                    'CURRENCY_ID' => 'RUB',
                    'COMMENTS' => $fields['comments'],
                    'DATE_CREATE' => date('d.m.Y H:i:s'),
                    'TYPE_ID' => $typeId,
                    'CONTACT_ID' => $this->contactId,
                    'CONTACT_BINDINGS' => [
                        [
                            'CONTACT_ID' => $this->contactId,
                            'SORT' => 10,
                            'IS_PRIMARY' => 'Y',
                        ],
                    ],
                    'OPENED' => 'Y',
                    'CLOSED' => 'N',
                    'ORIGIN_ID' => $fields['id'],
                    'OPPORTUNITY_ACCOUNT' => $fields['price'],
                    'UF_COMMUNICATION' => $property['Удобный сбособ связи'],
                    'UF_DELIVERY_AREA' => $property['Район'],
                    'UF_DELIVERY_STREET' => $property['Улица'],
                    'UF_DELIVERY_ENTRANCE' => $property['Подъезд'],
                    'UF_DELIVERY_FLOOR' => $property['Этаж'],
                    'UF_DELIVERY_APARTMENT' => $property['Квартира'],
                    'UF_DELIVERY' => 2, // Доставка, самовывоза нет
                    'UF_DELIVERY_SUM' => $fields['priceDelivery'],
                    'UF_DELIVERY_COMMENT' => $fields['delivery'], // Способ доставки
                ];

                // снять проверку обязательных полей
                $fieldOrder['APP_EVENT_HANDLERS_DISABLED'] = true;

                // поиск сделки
                $rsDeals = \CCrmDeal::GetList([], ['ORIGIN_ID' => $fields['id']], false, false, ['ID']);
                if ($arrDeal = $rsDeals->Fetch()) {
                    $this->dealId = $arrDeal['ID'];
                }

                if (intval($this->dealId) == 0) {
                    $this->dealId = $objDeal->Add($fieldOrder);
                    $productList = $this->saveProduct($fields);

                    $stringProductList = '';
                    foreach ($productList as $sort => $itemProduct) {
                        $tmp = explode('#', $itemProduct['name']);
                        $itemProduct['name'] = $tmp[0];
                        $stringProductList .= $itemProduct['name']." (".intval($itemProduct['count'])." шт.)"."\r\n";
                    }
                    $fieldOrder = [
                        'UF_GOODS_TEXT' => $stringProductList
                    ];
                    $objDeal->Update($this->dealId, $fieldOrder);
				}
				else{
					$fieldOrder = [
						'UF_PAYMENT' => array_key_exists('payed', $fields) ? $fields['payed'] : 0
					];

					$objDeal->Update($this->dealId, $fieldOrder);
				}

				// обновление воронки
				\CCrmDeal::RebuildStatistics(
					[$this->dealId],
					[
						'FORCED' => true,
						'ENABLE_SUM_STATISTICS' => true,
						'ENABLE_HISTORY' => true,
						'ENABLE_INVOICE_STATISTICS' => true,
						'ENABLE_ACTIVITY_STATISTICS' => true,
					]
				);

                $this->responce['dealId'] = $this->dealId;
            } catch (\Exception $e) {
                p2f($e->getMessage());
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Обновление списка продуктов.
     *
     * @param $fields
     */
    protected function saveProduct($fields)
    {
        \CModule::IncludeModule('iblock');

        $obProduct = new \CCrmProductRow();

        // собираем продукты
        $productList = $this->prepareItems($fields['basket']);

        foreach ($productList as $sort => $itemProduct) {
            $productItems = [
                'OWNER_TYPE' => 'D',
                'OWNER_ID' => $this->dealId,
                'DISCOUNT_SUM' => 0,
                'DISCOUNT_RATE' => 0,
                'TAX_RATE' => 0,
                'PRODUCT_ID' => $itemProduct['productId'],
                'PRODUCT_NAME' => $itemProduct['name'],
                'PRICE' => ceil($itemProduct['price']),
                'PRICE_BRUTTO' => ceil($itemProduct['price']),
                'PRICE_NETTO' => ceil($itemProduct['price']),
                'PRICE_EXCLUSIVE' => ceil($itemProduct['price']),
                'SUM' => ceil($itemProduct['price']),
                'QUANTITY' => intval($itemProduct['count']),
                'CUSTOMIZED' => 'Y',
                'TAX_INCLUDED' => 'N',
                'CURRENCY_ID' => 'RUB',
                'SORT' => $sort + 10,
            ];

            $productId = $obProduct->Add($productItems);
        }

        return $productList;
    }

    /**
     * Подготовка корзины к сохранению в сделке.
     *
     * @return array|int|string
     */
    private function prepareItems($arData)
    {
        $productList = [];
        foreach ($arData as $product) {
            if (intval($product['price']) == 0) {
                continue;
            }

            $isSimpleProduct = true;
            $xmlId = $product['property']['PRODUCT.XML_ID'];
            if (substr_count($product['property']['PRODUCT.XML_ID'], '#') > 0) {
                $tmp = explode('#', $product['property']['PRODUCT.XML_ID']);
                $xmlId = $tmp[1];

                $isSimpleProduct = false;
            }

            if ($isSimpleProduct) {
                $iblockId = $this->getIblockId('catalog');
            } else {
                $iblockId = $this->getIblockId('offers');
            }

            $productId = 0;
            $productName = $product['name'];

            // найти товар по артикулу
            $rsElement = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId, 'XML_ID' => $xmlId], false, false, ['ID']);
            while ($arElement = $rsElement->Fetch()) {
                $arProduct = Base\Tools::getElementByIDWithProps($arElement['ID']);
                if (array_key_exists('CML2_LINK', $arProduct['PROPERTIES_VALUE'])) {
                    $productId = $arProduct['PROPERTIES_VALUE']['CML2_LINK'];

                    $partName = [];
                    // собираем свойства, чтобы добавить в название товара
                    foreach ($arProduct['PROPERTIES_VALUE'] as $key => $value) {
                        if ($key == 'CML2_LINK') {
                            continue;
                        }

                        $partName[] = $arProduct['PROPERTIES'][$key]['VALUE_ENUM'];
                    }
                    $productName .= '('.implode(' / ', $partName).'). #'.$arElement['ID'];
                } else {
                    $productId = $arElement['ID'];
                }
            }

            $productList[] = [
                'name' => $productName,
                'price' => $product['price'],
                'count' => $product['quantity'],
                'productId' => $productId,
                'sku' => $xmlId,
            ];
        }

        return $productList;
    }

    /**
     * ID инфоблока.
     *
     * @return array|int|string
     */
    private function getIblockId($code = '')
    {
        $result = 0;

        // создаем объект
        $obCache = new \CPHPCache();

        // время кеширования - 30 минут
        $lifeTime = 60 * 60 * 10;

        // формируем идентификатор кеша в зависимости от всех параметров
        $cache_id = __METHOD__.$code;

        // если кэш есть и он ещё не истек то
        if ($obCache->InitCache($lifeTime, $cache_id, '/')) {
            // получаем закешированные переменные
            $vars = $obCache->GetVars();
            $result = $vars['ID'];
        } else {
            // иначе обращаемся к базе
            \CModule::IncludeModule('iblock');
            $res = \CIBlock::GetList(
                [],
                [
                    'CODE' => $code,
                    'ACTIVE' => 'Y',
                ],
                true
            );
            while ($arr = $res->Fetch()) {
                $result = $arr['ID'];
            }
        }

        // начинаем буферизирование вывода
        if ($obCache->StartDataCache()) {
            \CModule::IncludeModule('iblock');
            $res = \CIBlock::GetList(
                [],
                [
                    'CODE' => $code,
                    'ACTIVE' => 'Y',
                ],
                true
            );
            while ($arr = $res->Fetch()) {
                $result = $arr['ID'];
            }

            // записываем предварительно буферизированный вывод в файл кеша
            // вместе с дополнительной переменной
            $obCache->EndDataCache([
                'ID' => $result,
            ]);
        }

        return $result;
    }

    /**
     * Тип сделки.
     *
     * @param string $name
     *
     * @return array|int|string
     */
    private function getTypeDeal($name = '')
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
     * Источник.
     *
     * @return array|int|string
     */
    private function getSourceId()
    {
        $result = 'SELF';

        $name = 'Интернет-магазин';

        $rs = \CCrmStatus::GetStatusList('SOURCE');
        foreach ($rs as $code => $item) {
            if ($item == $name) {
                $result = $code;
            }
        }

        return $result;
    }

    /**
     * Поиск пользователья по ФИО.
     *
     * @param $fio
     *
     * @return int
     */
    private function searchUserId($fio)
    {
        $exp = explode(' ', $fio);

        $result = 1;
        $rs = \CUser::GetList($by = 'ID', $order = 'ASC', ['LAST_NAME' => $exp[0], 'NAME' => $exp[1]], ['FIELDS' => ['ID']]);
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
     * Поиск контакта по номеру телефона.
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
     * Поиск лида по номеру телефона.
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
                if ($ar['STATUS_ID'] == 'CONVERTED') {
                    continue;
                }
                $id = $ar['ID'];
            }
        } catch (SystemException $e) {
            $id = 0;
        } catch (ArgumentException $e) {
            $id = 0;
        }

        return $id;
    }

    /**
     * Поиск лида по email.
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
                if ($ar['STATUS_ID'] == 'CONVERTED') {
                    continue;
                }
                $id = $ar['ID'];
            }
        } catch (SystemException $e) {
            $id = 0;
        } catch (ArgumentException $e) {
            $id = 0;
        }

        return $id;
    }

    private function createContact($item)
    {
        // создать контакт
        $entityContact = new \CCrmContact(false);

        $fio = explode(' ', $item['fullName']);
        if (count($fio) > 1) {
            $item['lname'] = $fio[1];
        } else {
            $item['lname'] = '';
        }

        $item['fname'] = $fio[0];

        if (count($fio) > 2) {
            $item['mname'] = $fio[2];
        } else {
            $item['mname'] = '';
        }

        $result = [
            'EXPORT' => 'Y',
            'UF_MODIFY_BY_ID' => 1,
            'ASSIGNED_BY_ID' => 1,
            'DATE_CREATE' => date('d.m.Y H:i:s'),
            'LAST_NAME' => $item['lname'] ? $item['lname'] : '-',
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
                'n'.$number => [
                    'VALUE_TYPE' => 'OTHER',
                    'VALUE' => $item['phone'],
                ],
            ];
        }

        if (strlen($item['email']) > 0) {
            $emailItem = explode(',', $item['email']);
            if (count($emailItem) > 1) {
                $item['email'] = $emailItem[0];
            }

            $number = 'n0';

            $result['FM']['EMAIL'] = [
                $number => [
                    'VALUE_TYPE' => 'WORK',
                    'VALUE' => $item['email'],
                ],
            ];
        } else {
            $result['FM']['EMAIL'] = [];
        }

        $this->contactId = $entityContact->Add($result);

        return $this->contactId;
    }
}

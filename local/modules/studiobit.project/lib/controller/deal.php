<?php

namespace Studiobit\Project\Controller;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Studiobit\Base\View;
use Studiobit\Project as Project;
use Studiobit\Matrix\Entity\Object;
use Studiobit\Matrix\Entity\ObjectStatus;

/**
 * ajax-контроллер для сделки
 */

class Deal extends Prototype
{
    public function exportAction()
    {
        $this->view = new View\Original();
        $this->returnAsIs = true;
        $id = (int)$this->getParam('id');

        if(!$id)
            throw new \Exception('Не задан id');

        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'studiobit.project:1c.export',
            '',
            [
                'DEAL_ID' => $id,
                'PRINT' => 'Y'
            ]
        );

        return ob_get_clean();
    }

    public function createAction()
    {
        $this->view = new View\Json();

        $contact_id = (int)$this->getParam('contact_id');
        $category_id = (int)$this->getParam('category_id');
        $product_id = (int)$this->getParam('product_id');

        if(Loader::includeModule('crm') && Loader::includeModule('studiobit.matrix')){
            if($object = Object::getObjectByID($product_id)){
                if($object->getStatus() == ObjectStatus::Open)
                {
                    if($category_id !== 0){
                        if($object->code() !== 'TRADE_IN_APARTMENT' && $object->code() !== 'TRADE_IN_CAR'){
                            throw new \Exception('Для направления "Вторичка", можно бронировать помещения только из папки "Трейд-ин в деньги".');
                        }
                    }
                    else{
                        if($object->code() == 'TRADE_IN_APARTMENT' || $object->code() == 'TRADE_IN_CAR'){
                            throw new \Exception('Для направления "Общее", нельзя бронировать помещения из папки "Трейд-ин в деньги".');
                        }
                    }

                    $deal = new \CCrmDeal(false);

                    $rsContact = \CCrmContact::GetListEx([], ['ID' => $contact_id], false, false, ['*', 'UF_*']);

                    if($arContact = $rsContact->Fetch())
                    {
                        $title = \CCrmContact::GetFullName($arContact);
                        $title .= '_' . $object->getFullName('_');

                        $price = $object->getPrice();

                        $arDeal = [
                            'CATEGORY_ID'    => $category_id,
                            'TITLE'          => $title,
                            'CONTACT_ID'     => $contact_id,
                            'CONTACT_IDS'    => [$contact_id],
                            'STAGE_ID'       => \CCrmDeal::GetStartStageID($category_id),
                            'UF_CRM_SOURCE'  => $arContact['UF_CRM_SOURCE'],
                            'UF_CRM_CHANNEL' => $arContact['UF_CRM_CHANNEL'],
                            'UF_CRM_AGENCY'  => $arContact['UF_CRM_AGENCY'],
                            'UF_CRM_REALTOR' => $arContact['UF_CRM_REALTOR'],
                            'UF_CRM_PRICE'   => $price['PRICE'],
                            'UF_CRM_OWNERS'  => [$contact_id]
                        ];

                        if($dealId = $deal->Add($arDeal)){
                            $object->toEntity('DEAL', $dealId);
                            return \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::Deal, $dealId);
                        }
                        else{
                            throw new \Exception($deal->LAST_ERROR);
                        }
                    }
                    else{
                        throw new \Exception('Клиент не найден.');
                    }
                }
                else{
                    throw new \Exception('Выберите свободное помещение.');
                }
            }
            else{
                throw new \Exception('Не удалось найти выбранное помещение.');
            }
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

    public function paymentAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            'studiobit.project:deal.payment',
            '',
            [
                'DEAL_ID' => $this->getParam('DEAL_ID'),
                'MODE' => 'FORM'
            ],
            $componentResult
        );
    }
    public function sendlinkbywaAction()
    {
        $PHONE = $this->getParam("PHONE");
        $LINK = $this->getParam("LINK");

        $this->view = new View\Html();
        $this->returnAsIs = true;

        $text = 'Ссылка для оплаты Вашего заказа: '.$LINK;

        $res = sendByWhatsApp($PHONE,$text);

        return \Bitrix\Main\Web\Json::encode(['result'=>$res]);
    }

    public function sendlinkbysmsAction()
    {
        $PHONE = $this->getParam("PHONE");
        $LINK = $this->getParam("LINK");

        $this->view = new View\Html();
        $this->returnAsIs = true;

        $text = 'Ссылка для оплаты Вашего заказа: '.$LINK;

        $res = sendSMS($PHONE,$text);

        return \Bitrix\Main\Web\Json::encode(['result'=>$res]);
    }

    public function paymentScheduleAddAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            'studiobit.project:payment.schedule.edit',
            '',
            [
                'ENTITY_TYPE' => 'DEAL',
                'ENTITY_ID' => $this->getParam('DEAL_ID'),
                'ID' => 0,
                'MODE' => 'EDIT'
            ],
            $componentResult
        );
    }

    public function paymentAddAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            'studiobit.project:payment.edit',
            '',
            [
                'ENTITY_TYPE' => 'DEAL',
                'ENTITY_ID' => $this->getParam('DEAL_ID'),
                'ID' => 0,
                'MODE' => 'EDIT'
            ],
            $componentResult
        );
    }

    public function createMortageTaskAction()
    {
        $this->view = new View\Json();

        $id = (int)$this->getParam('id');
        $comment = $this->getParam('comment');

        if(Loader::includeModule('crm') && Loader::includeModule('bizproc'))
        {
                $rsDeal = \CCrmDeal::GetListEx([], ['ID' => $id], false, false, ['ID', 'UF_CRM_MORTGAGE_USER']);

                if($arDeal = $rsDeal->Fetch())
                {
                    if($arDeal['UF_CRM_MORTGAGE_USER'] > 0){
                        $templateId = \Bitrix\Main\Config\Option::get(Project\MODULE_ID, 'b_deal_mortage_task', 22);

                        $params = [
                            'USER' => 'user_'.$arDeal['UF_CRM_MORTGAGE_USER'],
                            'MESSAGE' => $comment
                        ];

                        if($workflowId = \CBPDocument::StartWorkflow($templateId, ['crm', "CCrmDocumentDeal", 'DEAL_' . $id], $params, $errors = [])){
                            return $workflowId;
                        }
                        else{
                            throw new \Exception(implode('<br />', $errors));
                        }


                    }
                    else{
                        throw new \Exception('В сделке не заполнено поле "Специалист по Ипотеке".');
                    }
                }
                else{
                    throw new \Exception('Сделка не найдена.');
                }
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

    public function createTradeInTaskAction()
    {
        $this->view = new View\Json();

        $id = (int)$this->getParam('id');
        $comment = $this->getParam('comment');

        if(Loader::includeModule('crm') && Loader::includeModule('bizproc'))
        {
            $rsDeal = \CCrmDeal::GetListEx([], ['ID' => $id], false, false, ['ID']);

            if($arDeal = $rsDeal->Fetch())
            {
                $templateId = \Bitrix\Main\Config\Option::get(Project\MODULE_ID, 'b_deal_tradein_task', 23);

                $params = [
                    'MESSAGE' => $comment
                ];

                if($workflowId = \CBPDocument::StartWorkflow($templateId, ['crm', "CCrmDocumentDeal", 'DEAL_' . $id], $params, $errors = [])){
                    return $workflowId;
                }
                else{
                    throw new \Exception(implode('<br />', $errors));
                }
            }
            else{
                throw new \Exception('Сделка не найдена.');
            }
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

    public function debitReportAction()
    {
        $this->view = new View\Json();

        $dealId = (int)$this->getParam('id');

        if($dealId)
        {
            $result = ['schedule' => [], 'payments' => [], 'debt' => 0];

            $transactionResult = Project\Entity\PaymentScheduleTable::getList([
                'select' => [
                    '*',
                    'TYPE' => 'ENUM_TYPE.VALUE'
                ],
                'filter' => ['DEAL_ID' => $dealId],
                'order' => [
                    'UF_DATE' => 'ASC',
                ],
            ]);
            while ($transaction = $transactionResult->fetch()) {
                $result['debt'] += $transaction['UF_SUM'];
                $this->processTransaction($transaction);
                $result['schedule'][] = $transaction;
            }

            $transactionResult = Project\Entity\PaymentTable::getList([
                'select' => [
                    '*',
                    'DEAL_ID',
                    'TYPE' => 'ENUM_TYPE.VALUE'
                ],
                'filter' => ['DEAL_ID' => $dealId],
                'order' => [
                    'UF_DATE' => 'ASC',
                ],
            ]);
            while ($transaction = $transactionResult->fetch()) {
                $result['debt'] -= $transaction['UF_SUM'];
                $this->processTransaction($transaction);
                $result['payments'][] = $transaction;
            }

            $result['debt'] = number_format($result['debt'], 0, '.', ' ');

            return $result;
        }

        throw new \Exception('Неизвестная ошибка. Обратитесь к администратору.');
    }

    /**
     * @param array $transaction
     */
    protected function processTransaction(array &$transaction)
    {
        $transaction['ID'] = (int)$transaction['ID'];
        $transaction['UF_SUM'] = floatval($transaction['UF_SUM']);
        $transaction['UF_SUM'] = number_format($transaction['UF_SUM'], 0, '.', ' ');
        if($transaction['UF_DATE'] instanceof Date){
            $transaction['UF_DATE'] = $transaction['UF_DATE']->format('d.m.Y');
        }
    }

    public function checkCanChangeStageAction()
    {
        $this->view = new View\Json();

        $dealId = (int)$this->getParam('id');
        $stageId = $this->getParam('stage');

        if($dealId)
        {
            $fields = [
                'ID' => $dealId,
                'STAGE_ID' => $stageId
            ];

            if (!Project\Entity\Crm\DealTable::validateFields($fields, false))
            {
                throw new \Exception($fields['RESULT_MESSAGE']);
            }
        }

        return true;
    }

    public function getDialogAddProductAction()
    {
        $this->view = new View\Html();
        $this->returnAsIs = true;

        return $this->getComponent(
            "studiobit.project:autocomplete.products",
            "",
            []
        );
    }
}

?>

<?php
namespace Studiobit\Project\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Studiobit\Project;

Main\Loader::includeModule('crm');

class CompanyTable extends Crm\CompanyTable
{
    /**
     * @return array
     */
    public static function getMap()
    {
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER, $DB;

        $map = parent::getMap();

        $map['ORIGIN_ID'] = array(
            'data_type' => 'string'
        );

        $userFields = $USER_FIELD_MANAGER->GetUserFields(static::getUFId());
        foreach ($userFields as $field) {
            if ($field['USER_TYPE_ID'] === 'enumeration') {
                $name = 'ENUM_' . substr($field['FIELD_NAME'], 3);
                $map[] = new Entity\ReferenceField($name,
                    '\Studiobit\Base\Entity\UserFieldEnumTable',
                    [
                        '=this.' . $field['FIELD_NAME'] => 'ref.ID',
                        'ref.USER_FIELD_ID' => new DB\SqlExpression('?i', $field['ID']),
                    ],
                    ['join_type' => 'left']
                );
            }
        }

        $map['REQUISITES'] = new Entity\ReferenceField(
            'REQUISITES',
            '\Bitrix\Crm\RequisiteTable',
            [
                '=this.ID' => 'ref.ENTITY_ID',
                '=ref.ENTITY_TYPE_ID' => ['?', \CCrmOwnerType::Company]
            ]
        );

        $map['FM'] = new Entity\ReferenceField(
            'FM',
            '\Bitrix\Crm\FieldMultiTable',
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'COMPANY']
            ]
        );

        return $map;
    }

    public static function validateFields(&$fields)
    {
        $errors = [];

        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_company_show');
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $post = $context->getRequest()->getPostList()->toArray();

        if(!empty($fields['TITLE'])){
            $sql = self::query();

            $sql->where('TITLE', '=', $fields['TITLE']);

            if ($fields['ID'] > 0) {
                $sql->setFilter(['!ID' => $fields['ID']]);
            }

            $rs = $sql->setSelect(['ID'])->exec();

            if ($arCompany = $rs->fetch()) {
                $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['company' => $arCompany['ID']]);
                $errors[] = 'Компания с названием "' . $arCompany['TITLE'] . '" уже существует - <a href="' . $url . '" target="_blank">показать</a>';
            }
        }

        if(isset($post['REQUISITES'])){
            foreach($post['REQUISITES'] as $id => $arReq) {
                $data = \CUtil::JsObjectToPhp($arReq['DATA']);
                $result = self::validateRequisitesByArray($data['fields']);

                if(!$result->isSuccess()){
                    $errors = array_merge($errors, $result->getErrorMessages());
                }
            }
        }

        if(!empty($errors)){
            $fields['RESULT_MESSAGE'] = implode('<br />', $errors);
        }
        
        return empty($errors);
    }

    public static function validateRequisitesByArray($data)
    {
        $result = new Entity\Result;
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_company_show');

        $sql = self::query();

        $sql->where('REQUISITES.RQ_INN', '=', $data['RQ_INN'])
            ->whereNot('ID', $data['ENTITY_ID']);

        $rs = $sql->setSelect(['ID'])->exec();

        if ($arCompany = $rs->fetch()) {
            $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['company_id' => $arCompany['ID']]);
            $result->addError(new Entity\EntityError('Компания с ИНН ' .  $data['RQ_INN'] . ' уже существует - <a href="' . $url . '" target="_blank">показать</a>'));
        }

        return $result;
    }

    public static function validateRequisites(Entity\Event $event)
    {
        $result = new Entity\EventResult;
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_company_show');

        $data = $event->getParameter("fields");
        $sql = self::query();

        $sql->where('REQUISITES.RQ_INN', '=', $data['RQ_INN'])
            ->whereNot('ID', $data['ENTITY_ID']);

        $rs = $sql->setSelect(['ID'])->exec();

        if ($arCompany = $rs->fetch()) {
            $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['company_id' => $arCompany['ID']]);
            $result->addError(new Entity\EntityError('Компания с ИНН ' .  $data['RQ_INN'] . ' уже существует - <a href="' . $url . '" target="_blank">показать</a>'));
        }

        return $result;
    }
}
?>
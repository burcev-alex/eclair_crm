<?php
namespace App\Integration\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Query;
use App\Integration;
use App\Base;

Main\Loader::includeModule('crm');

class ContactTable extends Crm\ContactTable
{
    private static $lastError;
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
                    '\App\Base\Entity\UserFieldEnumTable',
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
                '=ref.ENTITY_TYPE_ID' => ['?', \CCrmOwnerType::Contact]
            ]
        );

        $map['FM'] = new Entity\ReferenceField(
            'FM',
            '\Bitrix\Crm\FieldMultiTable',
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'CONTACT']
            ]
        );

        $map['PHONES'] = new Main\Entity\ReferenceField(
            'PHONES',
            Crm\FieldMultiTable::getEntity(),
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'CONTACT'],
                '=ref.TYPE_ID' => ['?', 'PHONE'],
            ]
        );

        $map['EMAIL'] = new Main\Entity\ReferenceField(
            'EMAIL',
            Crm\FieldMultiTable::getEntity(),
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'CONTACT'],
                '=ref.TYPE_ID' => ['?', 'EMAIL'],
            ]
        );

        $statement = '%s';
        $simbols = [' ', '\(', '\)', '-', '*', '+'];

        foreach($simbols as $symbol){
            $statement = "REPLACE($statement, '$symbol', '')";
        }

        $map['PHONE_VALUE'] = new Main\Entity\ExpressionField(
            'PHONE_VALUE',
            $statement,
            ['PHONES.VALUE']
        );

        $statement = '%s';
        $statement = "(CASE WHEN SUBSTRING($statement, 1, 1) = '8' THEN CONCAT('7', SUBSTRING($statement, 2))ELSE $statement END)";

        $map['PHONE_CLEAR_VALUE'] = new Main\Entity\ExpressionField(
            'PHONE_CLEAR_VALUE',
            $statement,
            ['PHONE_VALUE', 'PHONE_VALUE', 'PHONE_VALUE']
        );


        return $map;
    }
    
    
    public static function validateFields(&$fields)
    {
	    $fields['EXPORT'] = 'Y';

	    // отмена проверки обязательности полей
    	if($fields['FIRSTBIT_EVENT_HANDLERS_DISABLED']){
    		return true;
	    }

        $errors = [];
        $urlTemplate = \Bitrix\Main\Config\Option::get('crm', 'path_to_contact_show');

        //проверяем заполненность фамилии
        if(isset($fields['LAST_NAME']) && empty($fields['LAST_NAME'])) {
            $errors[] = 'Не заполнено обязательное поле "Фамилия"';
        }

        //проверяем заполненность имени
        if(isset($fields['NAME']) && empty($fields['NAME'])) {
            $errors[] = 'Не заполнено обязательное поле "Имя"';
        }

        //проверяем заполненность источника
        if(isset($fields['SOURCE_ID']) && empty($fields['SOURCE_ID'])) {
            $errors[] = 'Не заполнено обязательное поле "Источник"';
        }

        //запрещаем указывать номера телефонов, которые уже есть у других контактов
        if(isset($fields['FM']['PHONE'])) {
            $phoneCount = 0;
            $countPhoneUnique = 0;
            foreach ($fields['FM']['PHONE'] as $id => $arPhone) {
                if (!empty($arPhone['VALUE'])) {
                    $phoneCount++;

                    $sql = self::getSqlForSearchByPhone($arPhone['VALUE'], ['ID']);
                    $sql->whereNot('ID', '=', $fields['ID']);
                    $rsContact = $sql->exec();

                    if ($arContact = $rsContact->fetch()) {
                        $url = \CComponentEngine::makePathFromTemplate($urlTemplate, ['contact_id' => $arContact['ID']]);
                        $errors[] = 'Контакт с номером телефона ' . $arPhone['VALUE'] . ' уже существует - <a href="#preview" onclick="BX.SidePanel.Instance.open(\''.\CUtil::JSEscape($url).'\'); BX.PopupMenu.getCurrentMenu().popupWindow.close(); return false;">показать</a>';
                    }
                }
            }

            if(!$phoneCount){
                $errors[] = 'Не заполнено обязательное поле "Телефон"';
            }
        }
        
        if(!empty($errors)){
            $fields['RESULT_MESSAGE'] = implode('<br />', $errors);
        }
        
        return empty($errors);
    }

	/**
	 * Поиск гостя по номеру телефона
	 *
	 * @param $phone
	 * @param bool $select
	 *
	 * @return mixed
	 */
    public static function getSqlForSearchByPhone($phone, $select = false){
        $sql = self::query();
        
        $sql->where('FM.TYPE_ID', '=', 'PHONE');
        #$sql->where('FM.VALUE_TYPE', '=', Project\Custom\MultiField::$defaultPhoneType);

        $statement = '%s';
        $simbols = [' ', '\(', '\)', '-', '*', '+'];

        $query = str_replace(array_merge($simbols, ['(', ')']), '', $phone);
        if(substr($query, 0, 1) == '8')
            $query = '7'.substr($query, 1);

        foreach($simbols as $symbol){
            $statement = "REPLACE($statement, '$symbol', '')";
        }

        $sql->registerRuntimeField(
            'CONTACT_FM_VALUE',
            new Main\Entity\ExpressionField(
                'CONTACT_FM_VALUE',
                $statement,
                'FM.VALUE'
            )
        );

        $statement = '%s';
        $statement = "(CASE WHEN SUBSTRING($statement, 1, 1) = '8' THEN CONCAT('7', SUBSTRING($statement, 2))ELSE $statement END)";

        $sql->registerRuntimeField(
            'CONTACT_FM_CLEAR_VALUE',
            new Main\Entity\ExpressionField(
                'CONTACT_FM_CLEAR_VALUE',
                $statement,
                ['CONTACT_FM_VALUE', 'CONTACT_FM_VALUE', 'CONTACT_FM_VALUE']
            )
        );

        $sql->whereLike('CONTACT_FM_CLEAR_VALUE', $query.'%');

        if(is_array($select))
            $sql->setSelect($select);

        return $sql;
    }

    public static function getByPhone($phone, $select = false)
    {
        $sql = self::getSqlForSearchByPhone($phone, $select);
        $rs= $sql->exec();

        return $rs->fetch();
    }

	public static function getSqlForSearchByEmail($email, $select = false){
		$sql = self::query();

		$sql->setFilter([
			'=FM.VALUE' => $email,
			'FM.TYPE_ID' => 'EMAIL'
		]);

		if(is_array($select))
			$sql->setSelect($select);

		return $sql;
	}
}
?>
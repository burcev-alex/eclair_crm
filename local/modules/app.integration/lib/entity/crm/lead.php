<?php
namespace App\Integration\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use App\Base\Tools;
use App\Integration as Union;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\LeadContactTable;

Main\Loader::includeModule('crm');

class LeadTable extends Crm\LeadTable
{
    /**
     * @return array
     */
    public static function getMap()
    {
        /** @global \CUserTypeManager $USER_FIELD_MANAGER */
        global $USER_FIELD_MANAGER;

        $map = parent::getMap();

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
        
        $map['CONTACT'] = new Entity\ReferenceField(
            'CONTACT',
            '\App\Integration\Entity\Crm\ContactTable',
            [
                '=this.CONTACT_ID' => 'ref.ID'
            ]
        );

        $map['FM'] = new Entity\ReferenceField(
            'FM',
            '\Bitrix\Crm\FieldMultiTable',
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'LEAD']
            ]
        );

        $map['PHONES'] = new Main\Entity\ReferenceField(
            'PHONES',
            Crm\FieldMultiTable::getEntity(),
            [
                '=this.ID' => 'ref.ELEMENT_ID',
                '=ref.ENTITY_ID' => ['?', 'LEAD'],
                '=ref.TYPE_ID' => ['?', 'PHONE'],
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

	/**
	 * Поиск лида по номеру телефона
	 *
	 * @param $phone
	 * @param bool $select
	 *
	 * @return Main\ORM\Query\Query
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
    public static function getSqlForSearchByPhone($phone, $select = false){
        $sql = self::query();

        $sql->where('FM.TYPE_ID', '=', 'PHONE');
	    #$sql->where('FM.VALUE_TYPE', '=', Project\Custom\MultiField::$defaultPhoneType); // поиск тольк по Основному номеру телефона

        $statement = '%s';
        $simbols = [' ', '\(', '\)', '-', '*', '+'];

        $query = str_replace(array_merge($simbols, ['(', ')']), '', $phone);
        if(substr($query, 0, 1) == '8')
            $query = '7'.substr($query, 1);

        foreach($simbols as $symbol){
            $statement = "REPLACE($statement, '$symbol', '')";
        }

        $sql->registerRuntimeField(
            'LEAD_FM_VALUE',
            new Main\Entity\ExpressionField(
                'LEAD_FM_VALUE',
                $statement,
                'FM.VALUE'
            )
        );

        $statement = '%s';
        $statement = "(CASE WHEN SUBSTRING($statement, 1, 1) = '8' THEN CONCAT('7', SUBSTRING($statement, 2))ELSE $statement END)";

        $sql->registerRuntimeField(
            'LEAD_FM_CLEAR_VALUE',
            new Main\Entity\ExpressionField(
                'LEAD_FM_CLEAR_VALUE',
                $statement,
                ['LEAD_FM_VALUE', 'LEAD_FM_VALUE', 'LEAD_FM_VALUE']
            )
        );

        $sql->whereLike('LEAD_FM_CLEAR_VALUE', $query.'%');

        if(is_array($select))
            $sql->setSelect($select);

        return $sql;
    }

	public static function getSqlForSearchByEmail($email, $select = false){
		$sql = self::query();

		$sql->setFilter([
			'=FM.VALUE' => $email,
			'FM.TYPE_ID' => 'EMAIL',
		]);

		if(is_array($select))
			$sql->setSelect($select);

		return $sql;
	}

	public static function validateFields(&$fields)
	{
		$errors = [];
		$fields['EXPORT'] = 'Y';

		//проверяем привязку к контатку
		if(isset($fields['CONTACT_ID']) && !empty($fields['CONTACT_ID'])) {
			#$errors[] = 'Лид не может быть привязан к контакту';
		}

		$title = '';
		if(array_key_exists('LAST_NAME', $fields) && (strlen($fields['LAST_NAME']) > 0)){
			$title .= $fields['LAST_NAME']." ";
		}
		if(array_key_exists('NAME', $fields) && (strlen($fields['NAME']) > 0)){
			$title .= $fields['NAME']." ";
		}
		if(array_key_exists('SECOND_NAME', $fields) && (strlen($fields['SECOND_NAME']) > 0)){
			$title .= $fields['SECOND_NAME'];
		}

		if(strlen($title) > 0){
			$fields['TITLE'] = trim($title);
		}

		if(!empty($errors)){
			$fields['RESULT_MESSAGE'] = implode('<br />', $errors);
		}

		return empty($errors);
	}

	/**
	 * @param $fields
	 *
	 * @throws Main\ArgumentException
	 */
	public static function saveCustomFields($fields)
	{
		if(IntVal($fields['CONTACT_ID']) > 0){
			$obContact = new \CCrmContact(false);
			$newFields = [
				'FIRSTBIT_EVENT_HANDLERS_DISABLED' => true,
				'LEAD_ID' => $fields['ID'],
			];
			$obContact->Update((int)$fields['CONTACT_ID'], $newFields);

			LeadContactTable::bindContacts($fields['ID'], $fields['CONTACT_BINDINGS']);
		}
	}

	/**
	 * ID источника
	 *
	 * @param $name
	 *
	 * @return int|string
	 */
	public static function getSourceId($name)
	{
		$result = '';
		$sourceList = \CCrmStatus::getStatusList('SOURCE');
		foreach($sourceList as $key=>$value){
			if($name == $value){
				$result = $key;
				break;
			}
		}

		return $result;
	}
}
?>
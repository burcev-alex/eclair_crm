<?php

namespace App\Base\Entity;

use Bitrix\Main\Entity;

class UserFieldEnumTable extends Entity\DataManager
{
    /**
     * @return string
     */
    public static function getTableName()
    {
        return 'b_user_field_enum';
    }

    /**
     * @return array
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
            ]),
            new Entity\IntegerField('USER_FIELD_ID', [
                'required' => true,
            ]),
            new Entity\StringField('VALUE', [
                'required' => true,
            ]),
            new Entity\EnumField('DEF', [
                'values' => [
                    'N',
                    'Y',
                ],
                'default_value' => 'N',
            ]),
            new Entity\IntegerField('SORT', [
                'default_value' => 500,
            ]),
            new Entity\StringField('XML_ID', [
                'required' => true,
            ])
        ];
    }
}

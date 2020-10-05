<?php
namespace Studiobit\Project\Entity;

use \Studiobit\Base as Base;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('Bank');

/**
 сущность для HL-блока Тип оплат
 * @method static int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class BankTable extends \BankBaseTable
{
    /**
     * @param \Bitrix\Main\Entity\Base $entity
     *
     * @throws \Bitrix\Main\LoaderException
     */
    public static function prepareEntity(&$entity)
    {
        global $USER_FIELD_MANAGER;
        $userFields = $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_'.static::getEntityID());
        foreach ($userFields as $field) {
            if ($field['USER_TYPE_ID'] === 'enumeration') {
                $name = 'ENUM_' . substr($field['FIELD_NAME'], 3);
                $entity->addField(new \Bitrix\Main\Entity\ReferenceField($name,
                    '\Studiobit\Base\Entity\UserFieldEnumTable',
                    [
                        '=this.' . $field['FIELD_NAME'] => 'ref.ID',
                        'ref.USER_FIELD_ID' => new \Bitrix\Main\DB\SqlExpression('?i', $field['ID']),
                    ],
                    ['join_type' => 'left']
                ));
            }
        }
    }
}
?>
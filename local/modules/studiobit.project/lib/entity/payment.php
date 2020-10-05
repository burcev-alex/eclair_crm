<?php
namespace Studiobit\Project\Entity;

use \Studiobit\Base as Base;
use \Bitrix\Main\Loader as Loader;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('Payment');

/**
 сущность для HL-блока Избранное
 * @method static int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class PaymentTable extends \PaymentBaseTable
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

        $entity->addField(
            new \Bitrix\Main\Entity\ExpressionField(
                'DEAL_ID',
                'REPLACE(%s, \'D_\', \'\')',
                'UF_DEAL'
            )
        );

        $entity->addField(
            new \Bitrix\Main\Entity\ReferenceField(
                'DEAL',
                '\Studiobit\Project\Entity\Crm\DealTable',
                array('=this.DEAL_ID' => 'ref.ID'),
                array('join_type' => 'LEFT')
            )
        );
    }
}
?>
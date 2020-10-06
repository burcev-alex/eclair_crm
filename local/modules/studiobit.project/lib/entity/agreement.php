<?php
namespace Studiobit\Project\Entity;

use Bitrix\Main\Entity\AddResult;
use \Studiobit\Base as Base;
use \Bitrix\Main\Loader as Loader;
use Studiobit\Base\View;
use Studiobit\Matrix\Entity\Object;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('Agreement');

/**
 сущность для HL-блока Избранное
 * @method static int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class AgreementTable extends \AgreementBaseTable
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

    /**
     * @param $fields
     * @return AddResult
     */
    public static function add($fields){
        if(!empty($fields['UF_DEAL'])){
            $fields['UF_NUM']= self::generateNum($fields['UF_DEAL']);
        }

        return parent::add($fields);
    }

    public static function generateNum($dealId)
    {
        $num = '';

        if(Loader::includeModule('studiobit.matrix'))
        {
            $dealId = (int)str_replace('D_', '', $dealId);
            $arNumParts = [];
            if($dealId && $object = Object::getObjectByEntity('DEAL', $dealId)) {
                $building = $object->getBuilding();
                $section = $object->getSection();
                $data = $object->getData();

                if(is_object($building))
                    $arBuilding = $building->getData();
                else
                    $arBuilding = [];

                $arNumParts[] = $arBuilding['UF_PREFIX'];
                $arNumParts[] = $arBuilding['UF_NUMBER'];
                
                if(is_object($section))
                    $arNumParts[] .= ',' . $section->getName();

                $arNumParts[] = $object->getNum() . 'стр';
                $arNumParts[] = intval($object->getLevel()) . 'э';
                $arNumParts[] = $data['PROPERTIES']['ROOMS']['VALUE'];

                $num = implode('-', $arNumParts);

                if ($data['PROPERTIES']['LAYOUT']['VALUE'] == 'студия') {
                    $num .= 'ск';
                } else {
                    $num .= 'к';
                }
            }
        }

        return $num;
    }
}
?>
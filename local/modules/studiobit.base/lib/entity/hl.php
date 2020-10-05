<?php
namespace Studiobit\Base\Entity;

\Bitrix\Main\Loader::includeModule("highloadblock");

class HighloadBlockTable extends \Bitrix\Highloadblock\HighloadBlockTable
{
    public static function compileBaseClass($hlblock)
    {
        parent::compileEntity($hlblock);

        // get hlblock
        if (!is_array($hlblock))
        {
            if (is_numeric(substr($hlblock, 0, 1)))
            {
                // we have an id
                $hlblock = self::getById($hlblock)->fetch();
            }
            else
            {
                // we have a name
                $hlblock = self::query()->addSelect('*')->where('NAME', $hlblock)->exec()->fetch();
            }
        }

        // build datamanager class
        $entity_data_class = $hlblock['NAME'];

        if (!preg_match('/^[a-z0-9_]+$/i', $entity_data_class))
        {
            throw new \Studiobit\Base\Exception(sprintf(
                'Invalid entity name `%s`.', $entity_data_class
            ));
        }

        $entity_data_parent_class = $entity_data_class.'Table';
        $entity_data_class .= 'BaseTable';

        if (class_exists($entity_data_class))
        {
            \Bitrix\Main\Entity\Base::destroy($entity_data_class);
        }
        else
        {
            $eval = '
                class '.$entity_data_class.' extends \\'.$entity_data_parent_class.'
                {
                    public static function getEntity()
                    {
                        $entity = static::$entity[\'\\'.$entity_data_parent_class.'\'];
                        static::prepareEntity($entity);
                        return $entity;
                    }
                    
                    public static function prepareEntity(){
                    }
                    
                    public static function getEntityID(){
                        return '.$hlblock['ID'].';
                    }
                }
            ';

            eval($eval);
        }

        return $entity_data_class;
    }
}
?>
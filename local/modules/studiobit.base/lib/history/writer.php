<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Writer
{
    static $entity = false;

    public static function &getEntity(){
        if(empty($entity)) {
            Main\Loader::includeModule('crm');
            self::$entity = new \CCrmEvent();
        }

        return self::$entity;
    }

    public static function add($params){
        self::getEntity()->Add($params, false);
    }
}
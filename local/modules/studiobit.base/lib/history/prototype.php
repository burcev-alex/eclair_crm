<?
namespace Studiobit\Base\History;

use Studiobit\Base;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * История изменений базовый класс
 */

abstract class Prototype
{
    const CACHE_TIME = 3600;

    protected static $instance = [];
    protected static $cache = [];
    protected $arBeforeItem;
    protected $entityType;

    protected function __construct()
    {
        
    }

    public static function includeModules()
    {
    }
    
    public static function getInstance()
    {
        if(!is_object(static::$instance[static::class]))
            static::$instance[static::class] = new static();

        static::includeModules();

        return static::$instance[static::class];
    }

    public function getEntityType()
    {
        return '';
    }

    /**
     * добавление записи в историю
     * @param $params
     */
    public function add($params)
    {
        Writer::add([
            'ENTITY_TYPE'=> $params['ENTITY_TYPE'],
            'ENTITY_ID' => $params['ENTITY_ID'],
            'EVENT_TYPE' => 1,
            'EVENT_NAME' => $params['EVENT_NAME'],
            'EVENT_TEXT_1' => $params['EVENT_TEXT_1'],
            'EVENT_TEXT_2' => $params['EVENT_TEXT_2']
        ]);
    }

    /**
     * Название пользовательского поля
     * @param $params
     * @return string
     */
    abstract protected function getFieldName($params);

    /** html-представление пользовательского поля
     * @param $params
     * @return string
     */
    abstract protected function getFieldHtml($params);

    /**
     * Все пользовательские поля сущности
     * @param $param
     * @return mixed
     */
    abstract protected function getFields($param);
}
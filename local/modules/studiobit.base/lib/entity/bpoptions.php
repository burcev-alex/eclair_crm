<?php
namespace Studiobit\Base\Entity;

use Bitrix\Main\Application;
use Bitrix\Main\HttpContext;
use Studiobit\Base as Base;

\Studiobit\Base\Entity\HighloadBlockTable::compileBaseClass('BPOptions');

/**
 сущность для храненения настроек карточки бизнес-процесса
 * @method int getEntityID()
 * @mixin \Bitrix\Highloadblock\DataManager
 */

class BPOptionsTable extends \BPOptionsBaseTable
{
    protected static $uniqueId = false;
    protected static $errors = [];
    protected static $events = [];

    public static function setId($id){
        self::$uniqueId = $id;
    }

    public static function getId(){
        return self::$uniqueId;
    }

    public static function addError($error){
        if(self::getId()){
            if(!isset(self::$errors[self::getId()]))
                self::$errors[self::getId()] = [];
        }

        self::$errors[self::getId()][] = $error;
    }

    public static function getErrors($id = false){
        return self::$errors[$id];
    }

    public static function getAllErrors(){
        return self::$errors;
    }

    public static function clearErrors(){
        self::$errors[self::getId()] = [];
    }

    public static function clearAllErrors(){
        self::$errors = [];
    }

    public static function sendEvent($eventName, $params, $userId = false){
        if(!$userId)
            $userId = $GLOBALS['USER']->GetID();

        if(self::getId()){
            if(!isset(self::$events[self::getId()]))
                self::$events[self::getId()] = [];
        }

        self::$events[self::getId()][] = [
            'name' => $eventName,
            'params' => $params
        ];
    }

    public static function getAllEvents(){
        return self::$events;
    }

    public static function clearEvents(){
        self::$events[self::getId()] = [];
    }

    public static function clearAllEvents(){
        self::$events = [];
    }
}
?>
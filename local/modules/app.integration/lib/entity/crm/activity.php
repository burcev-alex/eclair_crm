<?php
namespace App\Integration\Entity\Crm;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Entity;
use App\Integration;

Main\Loader::includeModule('crm');

class ActivityTable extends Crm\ActivityTable
{

    public static function validateFields(&$fields)
    {
        $errors = [];
        
        return empty($errors);
    }
}
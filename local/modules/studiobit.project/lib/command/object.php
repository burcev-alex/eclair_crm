<?php

namespace Studiobit\Project\Command;

/**
 * команды для квартир
 */

class Object
{
    public static function importPrices()
    {
        $path = '/upload/1c/prices.xml';
        $path = $_SERVER['DOCUMENT_ROOT'] . $path;
        if (!file_exists($path)) {
            return;
        }

        //история
        copy($path, $_SERVER['DOCUMENT_ROOT'].'/upload/1c/history/prices/'.\ConvertTimeStamp(false, 'FULL').'.xml');

        $xml = simplexml_load_file($path);
        $prices = new \Studiobit\Project\Integration\Prices();

        // обработка и сохранение данных в системе
        $prices->save($xml);

        unlink($path);
    }

    public static function feed($template)
    {
        $GLOBALS['APPLICATION']->IncludeComponent(
            'studiobit.project:feed',
            $template,
            []
        );

        return '\Studiobit\Project\Command\Object::feed("'.$template.'");';
    }

    public static function overdue()
    {
        $GLOBALS['APPLICATION']->IncludeComponent(
            'studiobit.project:overdue',
            '',
            []
        );

        return '\Studiobit\Project\Command\Object::overdue();';
    }
}
?>
<?php

namespace Studiobit\Project\Command;

use Bitrix\Main\Loader;
use Studiobit\Project\Entity\Crm\ContactTable;

/**
 * команды для crm
 */

class Crm
{
    public static function saveImportFiles()
    {
        $path = $_SERVER['DOCUMENT_ROOT'].'/upload/1c/import/';
        $file = $_SERVER['DOCUMENT_ROOT'].'/upload/1c/1c_to_b24.xml';
        if(file_exists($file)){
            $timestmp = filemtime($file);
            $newfile = $path.(\ConvertTimeStamp($timestmp, 'FULL').'.xml');
            copy($file, $newfile);

            unlink($file);
        }
    }

    public static function export()
    {
        $GLOBALS['APPLICATION']->IncludeComponent(
            'studiobit.project:1c.export',
            '',
            []
        );
    }

    public static function import()
    {
        $GLOBALS['APPLICATION']->IncludeComponent(
            'studiobit.project:1c.import',
            '',
            []
        );
    }

    public static function checkOverdueContacts()
    {
        /*
        ЕСЛИ у контакта канал – «Агентство недвижимости» и разница между значением поля «Дата уведомления бинго» и текущей датой равна или более 14 дней, ТОГДА:
        Поле «Канал» - Самостоятельно
        Поле «Источник» - Просроченное уведомление
        так же должны очищаться поля Агенство и Риелтор в карточке КОНТАКТА
         */

        if(Loader::includeModule('crm')) {
            $channelSelf = ContactTable::getChannelByName('Самостоятельно');
            $sourceNotice = ContactTable::getSourceByName('Просроченное уведомление АН');
            $channelAgency = ContactTable::getChannelByName('Агентство недвижимости');

            if($channelSelf && $sourceNotice && $channelAgency) {
                $contact = new \CCrmContact(false);
                $fields = [
                    'UF_CRM_CHANNEL' => $channelSelf, //Канал = Самостоятельно
                    'UF_CRM_SOURCE'  => $sourceNotice, // Источник = Просроченное уведомление
                    'UF_CRM_AGENCY'  => 0,
                    'UF_CRM_REALTOR' => 0
                ];

                $time = new \DateTime();
                $time->setTime(0, 0, 0);
                $time->sub(new \DateInterval('P14D'));

                $rs = ContactTable::getList([
                    'filter' => [
                        'UF_CRM_CHANNEL'         => $channelAgency, //Канал = Агенство недвижимости,
                        '<=UF_BINGO_DATE_SET'    => \Bitrix\Main\Type\DateTime::createFromPhp($time)
                        /*'UF_IS_BINGO'            => 1,
                        [
                            'LOGIC' => 'OR',
                            '<=UF_BINGO_DATE_SET'    => \Bitrix\Main\Type\DateTime::createFromPhp($time),
                            'UF_BINGO_DATE_SET' => false
                        ]*/
                    ],
                    'order' => ['UF_BINGO_DATE_SET' => 'asc'],
                    'select' => ['ID', 'UF_BINGO_DATE_SET']
                ]);

                $cnt = 0;
                while ($ar = $rs->fetch()) {
                    $contact->Update($ar['ID'], $fields);
                    echo 'https://crm.gk-strizhi.ru/crm/contact/details/'.$ar['ID'].'/';
                    echo '<br />';
                    $cnt++;
                }
                p($cnt);
            }
        }
    }
}
?>
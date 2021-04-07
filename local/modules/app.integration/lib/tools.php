<?php

namespace App\Integration;

class Tools
{
    public static function siteURL()
    {
        $protocol = ((! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
        $domainName = $_SERVER['SERVER_NAME'];
        return $protocol.$domainName;
    }

    /**
     * Все родители конкретного раздела.
     *
     * @param int $section_id
     *
     * @return array
     */
    public static function getParentSection($section_id)
    {
        \CModule::IncludeModule('iblock');
        $result = [];

        if (! is_array($section_id)) {
            $nav = \CIBlockSection::GetNavChain(false, $section_id, ['ID']);
            while ($arSectionPath = $nav->GetNext()) {
                $result[] = $arSectionPath['ID'];
            }
        } else {
            foreach ($section_id as $sect) {
                $nav = \CIBlockSection::GetNavChain(false, $sect, ['ID']);
                while ($arSectionPath = $nav->GetNext()) {
                    $result[] = $arSectionPath['ID'];
                }
            }
        }

        $result = array_unique($result);

        return $result;
    }
}

<?php

namespace App\Integration;

use Bitrix\Main;
use Bitrix\Main\Loader;

class Constructor
{
    protected $siteId = '';
    protected $rootSectionId = 0;
    protected $iblockId = 0;

    public function __construct($sectionId, $siteId = '', $iblockId = 0)
    {
        Loader::includeModule('iblock');

        $this->rootSectionId = $sectionId;
        $this->iblockId = $iblockId;
        $this->siteId = $siteId;

        $this->init();
    }

    private function init()
    {
        // найти конфиг по родительскому разделу
        if (IntVal($this->rootSectionId) == 0 && strlen($this->siteId) == 0) {
            return true;
        }

        $config = $this->config();
        if (array_key_exists($this->rootSectionId, $config)) {
            $this->siteId = $config[$this->rootSectionId];
        } elseif (IntVal($this->iblockId) > 0) {
            // находим от обратного, если найден ID инфоблока в конфиге,
            // значит используется первая настройка
            $arrSites = self::getSites();

            foreach ($arrSites as $site) {
                if (strlen($this->siteId) > 0) {
                    continue;
                }

                $iblockString = Main\Config\Option::get('app.integration', 'iblock_external_id_'.$site, '');
                $iblockTmp = explode('|', $iblockString);

                foreach ($iblockTmp as $tmpIblockId) {
                    if (IntVal($tmpIblockId) === IntVal($this->iblockId)) {
                        $this->siteId = $site;
                    }
                }
            }
        }
    }

    public function get()
    {
        $result = [
            'host' => '',
            'url' => '',
            'token' => '',
            'format' => 'json',
            'iblock_external_id' => [
                'catalog' => 0,
                'sku' => 0
            ]
        ];

        if (strlen($this->siteId) > 0) {
            $iblockTmp = explode('|', Main\Config\Option::get('app.integration', 'iblock_external_id_'.$this->siteId, ''));
            $result['url'] = Main\Config\Option::get('app.integration', 'site_url_'.$this->siteId, '');
            $result['host'] = Main\Config\Option::get('app.integration', 'site_host_'.$this->siteId, '');
            $result['token'] = Main\Config\Option::get('app.integration', 'site_token_'.$this->siteId, '');
            $result['iblock_external_id'] = [
                'catalog' => $iblockTmp[0],
                'sku' => $iblockTmp[1]
            ];
        }

        return $result;
    }

    /**
     * Список сайтов доступных к обработке
     *
     * @return array
     */
    public static function getSites() {
        return [
            's1',
            's2',
            's3'
        ];
    }

    private function config()
    {
        return [
            133 => 's1', // ECLAIR.DELIVERY
            126 => 's2', // СЧАСТЬЕПЕЧЬ.РФ
            84 => 's3', // SWEET-ECLAIR.RU
        ];
    }

    public function getIblockExternalId($iblockId)
    {
        $config = $this->get();

        $externalId = 0;

        $arIblock = \CIBlock::GetByID($iblockId)->Fetch();

        if ($arIblock['CODE'] == 'offers') {
            $externalId = IntVal($config['iblock_external_id']['sku']);
        } elseif ($arIblock['CODE'] == 'catalog') {
            $externalId = IntVal($config['iblock_external_id']['catalog']);
        }

        return $externalId;
    }
}

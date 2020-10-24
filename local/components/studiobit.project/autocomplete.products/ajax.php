<?

define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Data\Cache;
use App\Base\Tools;


require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
Loc::loadMessages(__FILE__);

global $APPLICATION;

if (!Loader::includeModule('iblock')) {
    echo Loc::getMessage("BT_COMP_MLI_AJAX_ERR_MODULE_ABSENT");
    die();
}

if (!Loader::includeModule('catalog')) {
    echo Loc::getMessage("BT_COMP_MLI_AJAX_ERR_MODULE_ABSENT");
    die();
}

if (!Loader::includeModule('app.base')) {
    echo Loc::getMessage("BT_COMP_MLI_AJAX_ERR_MODULE_ABSENT");
    die();
}

CUtil::JSPostUnescape();

$APPLICATION->RestartBuffer();

$arResult = [];
$search = trim($_REQUEST['search']);

$cache = Cache::createInstance();
if ($cache->initCache(3600, "product_choice_".md5($search))) {
    $arResult = $cache->getVars();
} elseif ($cache->startDataCache()) {
    $filter['%NAME'] = $search;
    $filter['TYPE'] = [ProductTable::TYPE_PRODUCT, ProductTable::TYPE_OFFER];

    $params['select'] = ['ID', 'NAME' => 'IBLOCK_ELEMENT.NAME', 'PRICE', 'ELEMENT_PROPERTY'];
    $params['filter'] = $filter;
    $params['order'] = ['NAME' => 'ASC'];
    $params['runtime'] = [
        'PRICE' => [
            'data_type' => 'Bitrix\Catalog\PriceTable',
            'reference' => ['=this.ID' => 'ref.PRODUCT_ID'],
        ],
        new Reference(
            'ELEMENT_PROPERTY',
            ElementPropertyTable::class,
            Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
        ),
    ];

    $dbRes = ProductTable::getList(
        $params
    );
    while ($arItem = $dbRes->Fetch()) {
        $additional = [];
        $dbProperty = CIBlockElement::getProperty(
            Tools::GetIDByCode('offers'),
            $arItem['ID']
        );
        while ($arProperty = $dbProperty->Fetch()) {
            if (!empty($arProperty['VALUE_ENUM'])) {
                $additional[] = $arProperty['VALUE_ENUM'];
            }
        }

        $arResult['LIST'][$arItem["ID"]] =
            [
                'ID' => $arItem['ID'],
                'NAME' => $arItem['NAME'] . (empty($additional) ? '' : ' / ') . implode(
                        ' / ',
                        $additional
                    ) . ', ' . $arItem['CATALOG_PRODUCT_PRICE_PRICE'] . ' ' . $arItem['CATALOG_PRODUCT_PRICE_CURRENCY'],
                'PRICE' => $arItem['CATALOG_PRODUCT_PRICE_PRICE']
            ];
    }

    $cache->endDataCache($arResult);
}
$arResult['COUNT'] = count($arResult['LIST']);

header('Content-Type: application/json');
echo json_encode($arResult);
die();

<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

\CJSCore::Init(['studiobit_dialog', 'studiobit_loader', 'ui', 'studiobit_project_search_entity']);
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");

\Bitrix\Main\Page\Asset::getInstance()->addJs('/local/static/js/plugins/inputmask/jquery.inputmask.bundle.min.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs('/local/static/js/plugins/inputmask-multi/jquery.inputmask-multi.js');
\Bitrix\Main\Page\Asset::getInstance()->addJs($this->GetFolder().'/search.js');
?>
<div id="crm-entity-search" data-entity="CONTACT">
    <form method="post">
        <?echo bitrix_sessid_post();?>
        <div class="crm-entity-card-widget-edit">
            <div class="crm-entity-card-widget-title">
                <span class="crm-entity-card-widget-title-text">Введите имя или номер телефона</span>
            </div>
            <div class="crm-entity-widget-content">
                <div class="crm-entity-widget-content-block">
                    <div class="crm-entity-widget-content-block-inner">
                        <div class="crm-entity-widget-content-block-field-container">
                            <label><input type="radio" checked="checked" value="PHONE" name="TYPE"/> номер телефона</label>
                        </div>
                        <div class="crm-entity-widget-content-block-field-container">
                            <label><input type="radio" value="NAME" name="TYPE"/> имя</label>
                        </div>
                        <div class="crm-entity-widget-content-block-field-container">
                            <input name="QUERY" class="crm-entity-widget-content-input" type="text" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="crm-entity-card-widget-edit">
        <div class="crm-entity-card-widget-title">
            <span class="crm-entity-card-widget-title-text">Результаты поиска</span>
        </div>
        <div class="crm-entity-widget-content">
            <div class="crm-entity-widget-content-block">
                <div class="crm-entity-widget-content-block-inner">
                    <div class="crm-entity-widget-content-block-field-container" id="crm-entity-search-result">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {
        var search = new $.Studiobit.Project.searchContact({
            guid: '<?=$arParams['GUID']?>'
        });
    });
</script>

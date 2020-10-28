<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */
?>
<div class="crm-entity-widget-content-block-autocomplete-container">
    <div class="crm-entity-widget-content-block-title">
        <span class="crm-entity-widget-content-block-title-text">Введите название товара</span>
    </div>
    <div class="crm-entity-widget-content-block-inner">
        <div class="crm-entity-widget-content-block-field-container">
            <input name="TITLE"
                   class="crm-entity-widget-content-input"
                   id="crm-entity-widget-content-products-choice"
                   type="text" value="">
        </div>
    </div>
    <div class="crm-entity-widget-content-block-result"></div>
</div>

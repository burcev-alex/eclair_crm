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
?>
<script>
    $(function(){
        if(!$.Studiobit)
            $.Studiobit = {};

        if(!$.Studiobit.Project)
            $.Studiobit.Project = {};

        $.Studiobit.Project.Define = <?=\CUtil::PhpToJSObject($arResult['DATA']);?>;
    });
</script>

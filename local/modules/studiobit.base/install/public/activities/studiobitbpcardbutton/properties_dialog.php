<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_STAGE") ?>:</td>
	<td valign="top">
        <select name="stage_id" id="stage_id">
        <?foreach(\CCrmStatus::GetEntityTypes() as $key => $type):?>
            <?if(strpos($key, $documentType[2].'_STAGE') !== false):?>
                <optgroup label="<?=$type['NAME']?>">
                <?foreach(\CCrmStatus::GetStatusListEx($key) as $stageId => $stageName):?>
                    <option value="<?=$stageId?>"<?if($arCurrentValues["stage_id"] == $stageId):?> selected="selected"<?endif;?>>
                        <?=$stageName?>
                    </option>
                <?endforeach?>
                </optgroup>
            <?endif;?>
        <?endforeach;?>
	</td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_BUTTON_TITLE") ?>:</td>
    <td valign="top">
        <input type="text" name="button_title" id="title" value="<?= htmlspecialcharsbx($arCurrentValues["button_title"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('button_title', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_BUTTON_DISABLE") ?>:</td>
    <td valign="top">
        <input type="checkbox" name="button_disable" id="button_disable" value="Y"<?if($arCurrentValues["button_disable"] == 'Y'):?> checked="checked"<?endif;?> />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_BUTTON_SHOW") ?>:</td>
    <td valign="top">
        <input type="checkbox" name="button_show" id="button_show" value="Y"<?if($arCurrentValues["button_show"] == 'Y'):?> checked="checked"<?endif;?> />
    </td>
</tr>
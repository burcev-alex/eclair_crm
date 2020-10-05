<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_OBJECT_FROM_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="object_from_id" id="object_from_id" value="<?= htmlspecialcharsbx($arCurrentValues["object_from_id"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('object_from_id', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_OBJECT_TO_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="object_to_id" id="object_to_id" value="<?= htmlspecialcharsbx($arCurrentValues["object_to_id"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('object_to_id', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_DELETE") ?>:</td>
    <td valign="top">
        <input type="text" name="delete_from" id="delete_from" value="<?= htmlspecialcharsbx($arCurrentValues["delete_from"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('delete_from', 'string');" />
    </td>
</tr>
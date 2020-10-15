<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_TASK_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="task_id" id="task_id" value="<?= htmlspecialcharsbx($arCurrentValues["task_id"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('task_id', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_TAGS") ?>:</td>
    <td valign="top">
        <input type="text" name="tags" id="tags" value="<?= htmlspecialcharsbx($arCurrentValues["tags"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('tags', 'string');" />
    </td>
</tr>
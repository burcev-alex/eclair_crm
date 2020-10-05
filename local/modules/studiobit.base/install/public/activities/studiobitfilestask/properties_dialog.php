<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_TASK_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="task_id" id="task_id" value="<?= htmlspecialcharsbx($arCurrentValues["task_id"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('task_id', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_FILES") ?>:</td>
    <td valign="top">
        <input type="text" name="files" id="files" value="<?= htmlspecialcharsbx($arCurrentValues["files"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('files', 'string');" />
    </td>
</tr>

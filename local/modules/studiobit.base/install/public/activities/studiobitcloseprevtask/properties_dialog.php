<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_TASK_ID") ?>:</td>
	<td valign="top">
		<input type="text" name="object_id" id="object_id" value="<?= htmlspecialcharsbx($arCurrentValues["object_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('object_id', 'string');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_AGREE_PD_TASK_TYPE") ?>:</td>
	<td valign="top">
		<input type="text" name="type" id="type" value="<?= htmlspecialcharsbx($arCurrentValues["type"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('type', 'string');" />
	</td>
</tr>
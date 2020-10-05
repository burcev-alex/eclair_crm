<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_AGREE_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="agree_id" id="agree_id" value="<?= htmlspecialcharsbx($arCurrentValues["agree_id"]) ?>" size="20" />
        <input type="button" value="..." onclick="BPAShowSelector('agree_id', 'integer');" />
    </td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_ENTITY_ID") ?>:</td>
	<td valign="top">
		<input type="text" name="entity_id" id="entity_id" value="<?= htmlspecialcharsbx($arCurrentValues["entity_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('entity_id', 'integer');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_ENTITY_TYPE") ?>:</td>
	<td valign="top">
		<input type="text" name="entity_type" id="entity_type" value="<?= htmlspecialcharsbx($arCurrentValues["entity_type"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('entity_type', 'string');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_OBJECT_ID") ?>:</td>
	<td valign="top">
		<input type="text" name="object_id" id="object_id" value="<?= htmlspecialcharsbx($arCurrentValues["object_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('object_id', 'integer');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_TEMPLATE_ID") ?>:</td>
	<td valign="top">
		<input type="text" name="template_id" id="template_id" value="<?= htmlspecialcharsbx($arCurrentValues["template_id"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('template_id', 'integer');" />
	</td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_FILE_NAME") ?>:</td>
	<td valign="top">
		<input type="text" name="file_name" id="file_name" value="<?= htmlspecialcharsbx($arCurrentValues["file_name"]) ?>" size="20" />
		<input type="button" value="..." onclick="BPAShowSelector('file_name', 'string');" />
	</td>
</tr>
<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="buttonId" id="buttonId" value="<?= htmlspecialcharsbx($arCurrentValues["buttonId"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('buttonId', 'string');" />
    </td>
</tr>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_HTML") ?>:</td>
	<td valign="top">
		<textarea name="html" id="html" cols="50" rows="4"><?= htmlspecialcharsbx($arCurrentValues["html"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('html', 'string');" />
	</td>
</tr>
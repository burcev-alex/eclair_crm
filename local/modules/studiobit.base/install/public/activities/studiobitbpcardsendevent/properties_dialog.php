<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_EVENT") ?>:</td>
	<td valign="top">
		<input type="text" name="event" id="event" value="<?= htmlspecialcharsbx($arCurrentValues["event"]) ?>" />
		<input type="button" value="..." onclick="BPAShowSelector('event', 'string');" />
	</td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_PARAMS") ?>:</td>
    <td valign="top">
        <input type="text" name="params" id="params" value="<?= htmlspecialcharsbx($arCurrentValues["params"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('params', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_USER") ?>:</td>
    <td valign="top">
        <input type="text" name="user" id="user" value="<?= htmlspecialcharsbx($arCurrentValues["user"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('user', 'user');" />
    </td>
</tr>
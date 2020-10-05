<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right"><?= GetMessage("STUDIOBIT_ENTITY_TYPE") ?>:</td>
	<td valign="top">
		<input type="text" name="entityType" id="entityType" value="<?= htmlspecialcharsbx($arCurrentValues["entityType"]) ?>" />
		<input type="button" value="..." onclick="BPAShowSelector('entityType', 'string');" />
	</td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_ENTITY_ID") ?>:</td>
    <td valign="top">
        <input type="text" name="entityId" id="entityId" value="<?= htmlspecialcharsbx($arCurrentValues["entityId"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('entityId', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_EVENT_NAME") ?>:</td>
    <td valign="top">
        <input type="text" name="eventName" id="eventName" value="<?= htmlspecialcharsbx($arCurrentValues["eventName"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('eventName', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_EVENT_TEXT1") ?>:</td>
    <td valign="top">
        <input type="text" name="eventText1" id="eventText1" value="<?= htmlspecialcharsbx($arCurrentValues["eventText1"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('eventText1', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_EVENT_TEXT2") ?>:</td>
    <td valign="top">
        <input type="text" name="eventText2" id="eventText2" value="<?= htmlspecialcharsbx($arCurrentValues["eventText2"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('eventText2', 'string');" />
    </td>
</tr>
<tr>
    <td align="right"><?= GetMessage("STUDIOBIT_USER") ?>:</td>
    <td valign="top">
        <input type="text" name="user" id="user" value="<?= htmlspecialcharsbx($arCurrentValues["user"]) ?>" />
        <input type="button" value="..." onclick="BPAShowSelector('user', 'user');" />
    </td>
</tr>
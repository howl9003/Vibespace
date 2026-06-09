<script language="JavaScript" type="text/javascript">
<!--
function resetHidden()
{
    var form = document.mapform;
    var tmp = '';
    for (i = 0; i < form.selectData.length; i++) {
        tmp += form.selectData[i].value;
        if (i < form.selectData.length - 1)
            tmp += "\t";
    }
    form.dataKeys.value = tmp;
    tmp = '';
    for (i = 0; i < form.selectApp.length; i++) {
        tmp += form.selectApp[i].value;
        if (i < form.selectApp.length - 1)
            tmp += "\t";
    }
    form.appKeys.value = tmp;
}

function addPair()
{
    var form = document.mapform;
    if (form.select1.selectedIndex >= 0 && form.select2.selectedIndex >= 0) {
        form.selectData[form.selectData.length] = new Option(form.select1[form.select1.selectedIndex].text, form.select1[form.select1.selectedIndex].value);
        form.selectApp[form.selectApp.length] = new Option(form.select2[form.select2.selectedIndex].text, form.select2[form.select2.selectedIndex].value);
        form.select1[form.select1.selectedIndex] = null;
        form.select2[form.select2.selectedIndex] = null;
    }
    resetHidden();
}

function removePair()
{
    var form = document.mapform;
    if (form.selectData.selectedIndex >= 0 || form.selectApp.selectedIndex >= 0) {
        form.select1[form.select1.length] = new Option(form.selectData[form.selectData.selectedIndex].text, form.selectData[form.selectData.selectedIndex].value);
        form.select2[form.select2.length] = new Option(form.selectApp[form.selectApp.selectedIndex].text, form.selectApp[form.selectApp.selectedIndex].value);
        form.selectData[form.selectData.selectedIndex] = null;
        form.selectApp[form.selectApp.selectedIndex] = null;
    }
    resetHidden();
}
//-->
</script>

<form method="post" name="mapform" enctype="multipart/form-data" action="<?php echo $actionUrl ?>">
<input type="hidden" name="actionID" value="<?php echo HORDE_IMPORT ?>">
<input type="hidden" name="importID" value="<?php echo IMPORT_MAPPED ?>">
<input type="hidden" name="import_type" value="<?php echo $importID ?>">
<input type="hidden" name="cacheID" value="<?php echo $cacheID ?>">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
    <td>
        <table cellpadding="3" cellspacing="0" border="0" width="100%">
        <tr><td class="header" align="center"><?php echo _("Map Import Fields") ?></td>
        </tr></table>
    </td>
</tr>
<tr>
    <td>
        <table cellpadding="2" cellspacing="0" border="0" width="100%">
        <tr><td class="item">&nbsp;</td></tr>
        <tr><td class="smallheader"><b><?php echo _("Select two matching fields.") ?></b></td></tr>
        </table>
    </td>
</tr>
<tr>
    <td class="item">
    <table border="0"><tr><td>
        <table border="0" cellpadding="0" cellspacing="0"><tr>
            <td>
                <?php echo _("Imported fields:") ?><br />
                <select name="select1" size="10">
<?php foreach ($importData[0] as $key => $value): ?>
<?php if (!isset($appFields[$key])): ?>
                    <option value="<?php echo $key ?>"><?php echo ($header) ? $key : $value ?></option>
<?php endif; ?>
<?php endforeach; ?>
                </select>
            </td>
            <td>
                <?php echo _("Available fields:") ?><br />
                <select name="select2" size="10">
<?php foreach ($appFields as $key => $value): ?>
<?php if (!isset($importData[0][$key])): ?>
                    <option value="<?php echo $key ?>"><?php echo $value ?></option>
<?php endif; ?>
<?php endforeach; ?>
                </select>
            </td>
        </tr></table>
        <input type="button" value="<?php echo _("Add pair") ?>" class="button" onclick="addPair()" />
    </td></tr></table>
    </td
</tr>
<tr>
    <td>
        <table cellpadding="2" cellspacing="0" border="0" width="100%">
        <tr><td class="item">&nbsp;</td></tr>
        <tr><td class="smallheader"><b><?php echo _("Matching fields") ?></b></td></tr>
        </table>
    </td>
</tr>
<tr>
    <td class="item">
    <table border="0"><tr><td>
        <input type="hidden" name="dataKeys">
        <input type="hidden" name="appKeys">
        <select name="selectData" size="10" onchange="document.mapform.selectApp[this.selectedIndex].selected=true">
<?php
$matching = array();
foreach ($importData[0] as $key => $value):
    if (isset($appFields[$key])):
        $matching[] = array($key, $appFields[$key]);
?>
                    <option value="<?php echo $key ?>"><?php echo ($header) ? $key : $value ?></option>
<?php endif; ?>
<?php endforeach; ?>
        </select>
        <select name="selectApp" size="10" onchange="document.mapform.selectData[this.selectedIndex].selected=true">
<?php foreach ($matching as $pair): ?>
                    <option value="<?php echo $pair[0] ?>"><?php echo $pair[1] ?></option>
<?php endforeach; ?>
        </select><br />
        <input type="button" value="<?php echo _("Remove pair") ?>" class="button" onclick="removePair()" /><br /><br />
        <input type="submit" value="<?php echo _("Import") ?>" class="button" />
    </td></tr></table>
    </td>
</tr>
</table>
</form>
<script language="JavaScript" type="text/javascript">
<!--
resetHidden();
//-->
</script>

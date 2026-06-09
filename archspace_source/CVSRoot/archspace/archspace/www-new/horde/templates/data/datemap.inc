<form method="post" name="dateform" enctype="multipart/form-data" action="<?php echo $actionUrl ?>">
<input type="hidden" name="actionID" value="<?php echo HORDE_IMPORT ?>">
<input type="hidden" name="importID" value="<?php echo IMPORT_DATETIME ?>">
<input type="hidden" name="import_type" value="<?php echo $import_type ?>">
<input type="hidden" name="cacheID" value="<?php echo $cacheID ?>">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
    <td>
        <table cellpadding="3" cellspacing="0" border="0" width="100%">
        <tr><td class="header" align="center"><?php echo _("Map Date and Time Fields") ?></td>
        </tr></table>
    </td>
</tr>
<?php foreach ($dates as $key => $date): ?>
<?php if (count($date['values']) > 0): ?>
<tr>
    <td>
        <table cellpadding="2" cellspacing="0" border="0" width="100%">
        <tr><td class="item">&nbsp;</td></tr>
        <tr><td class="smallheader"><b><?php echo sprintf(_("Imported field: %s"), $appFields[$key]) ?></b></td></tr>
        </table>
    </td>
</tr>
<tr>
    <td class="item">
    <table border="0">
    <tr>
        <td><?php echo _("Example values:") ?></td>
        <td>
            <select>
<?php foreach ($date['values'] as $val): ?>
                <option><?php echo $val ?></option>
<?php endforeach; ?>
            </select>
        </td>
    </tr>
<?php if ($date['type'] == 'date'): ?>
<?php
$i = 0;
while ($i < count($date['values']) && empty($date['values'][$i])) {
    $i++;
}
if (strpos($date['values'][$i], '/') !== false) {
    $del = '/';
} elseif (strpos($date['values'][$i], '.') !== false) {
    $del = ".";
} elseif (strpos($date['values'][$i], '-') !== false) {
    $del = "-";
} else {
    $del = "";
}
?>
    <tr>
        <td><?php echo _("Select the date delimiter:") ?></td>
        <td>
            <select name="delimiter[<?php echo $key ?>]">
                <option value="/"<?php echo $del == '/' ? ' selected="selected"' : '' ?>>/</option>
                <option value="."<?php echo $del == '.' ? ' selected="selected"' : '' ?>>.</option>
                <option value="-"<?php echo $del == '-' ? ' selected="selected"' : '' ?>>-</option>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo _("Select the date format:") ?></td>
        <td>
            <select name="format[<?php echo $key ?>]">
                <option value="mday/month/year"><?php echo _("DD") . $del . _("MM") . $del . _("YY") ?></option>
                <option value="month/mday/year"><?php echo _("MM") . $del . _("DD") . $del . _("YY") ?></option>
                <option value="year/month/mday"><?php echo _("YY") . $del . _("MM") . $del . _("DD") ?></option>
                <option value="year/mday/month"><?php echo _("YY") . $del . _("DD") . $del . _("MM") ?></option>
            </select>
        </td>
    </tr>
<?php elseif ($date['type'] == 'time'): ?>
<?php
$i = 0;
while ($i <= count($date['values']) && empty($date['values'][$i])) {
    $i++;
}
if (strpos($date['values'][$i], ':') !== false) {
    $del = ':';
} elseif (strpos($date['values'][$i], '.') !== false) {
    $del = ".";
} else {
    $del = "";
}
$ampm = (strpos(strtolower($date['values'][0]), 'am') !== false || strpos(strtolower($date['values'][0]), 'pm') !== false);
?>
    <tr>
        <td><?php echo _("Select the time delimiter:") ?></td>
        <td>
            <select name="delimiter[<?php echo $key ?>]">
                <option value=":"<?php echo $del == ':' ? ' selected="selected"' : '' ?>>:</option>
                <option value="."<?php echo $del == '.' ? ' selected="selected"' : '' ?>>.</option>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo _("Select the time format:") ?></td>
        <td>
            <select name="format[<?php echo $key ?>]">
                <option value="24hr"<?php echo $ampm ? '' : ' selected="selected"' ?>><?php echo _("24 hours") ?></option>
                <option value="ampm"<?php echo $ampm ? ' selected="selected"' : '' ?>><?php echo _("AM/PM") ?></option>
            </select>
        </td>
    </tr>
<?php elseif ($type == 'datetime'): ?>
<?php endif; ?>
    </table>
    </td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
<tr>
    <td>
        <table cellpadding="2" cellspacing="0" border="0" width="100%">
        <tr><td class="item"><input type="submit" class="button" value="<?php echo _("Import") ?>" /></td></tr>
        </table>
    </td>
</tr>
</table>
</form>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>

<?php $count = 0; foreach ($columns as $key => $col): ?>
    <td width="<?php echo $span ?>%">
        <table cellpadding="3" cellspacing="0" border="0" width="100%">
        <tr><td class="header" align="center"><b><?php echo $key ?></b></td></tr>
        </table>
    </td>

<?php $count++; if ($count < count($columns)): ?>
    <td width="1%" rowspan="5">&nbsp;</td>
<?php endif; ?>
<?php endforeach; ?>

</tr>
<tr valign="top">

<?php foreach ($columns as $key => $column): ?>
    <td width="<?php echo $span ?>%">
        <table cellpadding="2" cellspacing="0" border="0" width="100%">

<?php foreach ($column as $group => $gvals): ?>
<?php if (groupIsEditable($group)): ?>
<?php if (!empty($gvals['url'])): ?>
            <tr><td class="control"><?php echo Horde::link(Horde::url($gvals['url']), $gvals['label'], 'widget') ?><b><?php echo $gvals['label'] ?></b></a></td></tr>
<?php else: ?>
            <tr><td class="control"><?php echo Horde::link(Horde::addParameter(Horde::selfURL(), 'group=' . $group), $gvals['label'], 'widget') ?><b><?php echo $gvals['label'] ?></b></a></td></tr>
<?php endif; ?>
            <tr><td class="item"><?php echo $gvals['desc'] ?><br /><br /></td></tr>
<?php endif; ?>
<?php endforeach; ?>

        </table>
    </td>
<?php endforeach; ?>

</tr>
</table>
<br />

<table class="menu" border="0" width="100%" cellpadding="2" cellspacing="0">
<tr valign="middle">
  <td class="header" align="left" width="100%">
    &nbsp;<b><?php echo sprintf(_("Welcome, %s"), $fullname) ?></b>
  </td>
<?php if (($conf['prefs']['driver'] != '') && ($conf['prefs']['driver'] != 'none')): ?>
  <?php echo Menu::printItem(Horde::applicationUrl('prefs.php'), _("Options"), 'prefs.gif') ?>
<?php endif; ?>
  <?php echo Menu::printItem(Horde::applicationUrl('login.php?reason=logout'), _("Log out"), 'logout.gif') ?>
</tr>
</table>

<br />

<?php if (@is_readable('./config/motd.php')) { include './config/motd.php'; echo '<br />'; } ?>

<?php if (count($columns) > 0): ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr>

<?php $current_column = 0; ?>
<?php foreach ($columns as $column): ?>
  <?php if ($current_column % $pref_num_columns == 0): ?>
    <tr>
  <?php else: ?>
    <td>&nbsp;&nbsp;&nbsp;&nbsp;</td>
  <?php endif; ?>
    <td class="text" valign="top" width="<?php echo $width ?>%"><?php echo $column ?></td>
  <?php if ($current_column % $pref_num_columns == $pref_num_columns - 1 && $current_column != 0): ?>
    </tr><tr height=10></tr>
  <?php endif; $current_column++; ?>
<?php endforeach; ?>
</tr></table>
<?php endif; ?>

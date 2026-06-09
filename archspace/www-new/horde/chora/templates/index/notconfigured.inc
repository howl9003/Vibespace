<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo _("Chora is not properly configured") ?></title>
<style type="text/css">
<!--
<?php include CHORA_BASE . '/templates/index/css.inc'; ?>
-->
</style>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="4">
<tr><td colspan="2" class="header"><b><?php echo _("Some of Chora's configuration files are missing:") ?></b></td></tr>

<?php if (!@is_readable('./config/conf.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>conf.php</b></td>
  <td class="light"><?php echo _("This is the main Chora configuration file. It contains paths and options for all Chora scripts.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/cvsroots.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>cvsroots.php</b></td>
  <td class="light"><?php echo _("This file defines all of the cvs repositories that you wish Chora to display.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/mime_drivers.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>mime_drivers.php</b></td>
  <td class="light"><?php echo _("This file defines any special MIME-type handling for Chora.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/html.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>html.php</b></td>
  <td class="light"><?php echo _("This file controls the stylesheet that is used to set colors and fonts in addition to or overriding Horde defaults.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/prefs.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>prefs.php</b></td>
  <td class="light"><?php echo _("This file controls the default preferences for Chora, and also controls which preferences users can alter.") ?></td>
</tr>
<?php endif; ?>

</table>
</body>
</html>

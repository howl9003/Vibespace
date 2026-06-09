<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo _("Horde is not properly configured") ?></title>
<?php include HORDE_BASE . '/templates/index/css.inc'; ?>
</head>
<body>
<table border="0" align="center" width="500" cellpadding="2" cellspacing="4">
<tr><td colspan="2" class="header"><b><?php echo _("Some of Horde's configuration files are missing:") ?></b></td></tr>

<?php if (!@is_readable('./config/horde.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>horde.php</b></td>
  <td class="light"><?php echo _("This is the main Horde configuration file. It contains paths and basic items that apply to the core framework and all Horde applications.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/html.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>html.php</b></td>
  <td class="light"><?php echo _("This file controls the stylesheet that is used to set colors and fonts for the Horde framework and all applications that do not provide their own settings.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/mime_drivers.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>mime_drivers.php</b></td>
  <td class="light"><?php echo _("This file controls the global set of MIME drivers for the Horde framework, allowing applications to make use of programs such as enscript or mswordview to render content into HTML for viewing in a browser.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/mime_mapping.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>mime_mapping.php</b></td>
  <td class="light"><?php echo _("This file maps file extensions to logical MIME types, enabling Horde applications to figure out how to handle some content such as email attachments or CVS checkouts.") ?></td>
</tr>
<?php endif; ?>

<?php if (!@is_readable('./config/registry.php')): ?>
<tr>
  <td align="right" class="smallheader"><b>registry.php</b></td>
  <td class="light"><?php echo _("The registry is how Horde applications find out how to talk to each other. You should list any installed Horde applications that you have here.") ?></td>
</tr>
<?php endif; ?>

</table>
</body>
</html>

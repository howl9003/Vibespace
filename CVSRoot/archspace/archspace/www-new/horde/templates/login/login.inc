<script language="JavaScript" type="text/javascript">
<!--
function setFocus()
{
    if (document.horde_login.horde_user.value == "") {
        document.horde_login.horde_user.focus();
    } else {
        document.horde_login.horde_pass.focus();
    }
}
function checkLoginForm()
{
    if (document.horde_login.horde_user.value == "") {
        alert("<?php echo addslashes(_("Please provide your username and password")) ?>");
        document.horde_login.horde_user.focus();
        return false;
    } else if (document.horde_login.horde_pass.value == "") {
        alert("<?php echo addslashes(_("Please provide your username and password")) ?>");
        document.horde_login.horde_pass.focus();
        return false;
    } else {
        return true;
    }
}
//-->
</script>

<script language="JavaScript1.2" type="text/javascript">
<!--
function enter_key_trap(e)
{
    var keyPressed;

    if (document.layers) {
        keyPressed = String.fromCharCode(e.which);
    } else if (document.all) {
        keyPressed = String.fromCharCode(window.event.keyCode);
    } else if (document.getElementById) {
        keyPressed = String.fromCharCode(e.keyCode);
    }

    if ((keyPressed == "\r" || keyPressed == "\n") && (checkLoginForm())) {
        document.horde_login.submit();
    }
}

// Setup the enter keytrap code
if (window.document.captureEvents != null) {
    window.document.captureEvents(Event.KEYPRESS);
    window.document.onkeypress = enter_key_trap;
}
//-->
</script>

<form name="horde_login" method="post" action="<?php echo Horde::selfURL() ?>">
<input type="hidden" name="url" value="<?php echo Horde::getFormData('url') ?>" />
<table align="center">
<tr>
  <td align="center" colspan="2" class="header"><b><?php printf(_("Welcome to %s"), $registry->getParam('name')) ?></b></td>
</tr>
<?php if (!empty($reasons[Horde::getFormData('reason')])): ?>
<tr>
  <td align="center" colspan="2" class="light"><p class="light"><?php echo $reasons[Horde::getFormData('reason')] ?></p></td>
</tr>
<?php endif; ?>
<tr>
  <td align="right" class="light"><b><?php echo _("Username") ?></b>&nbsp;</td>
  <td><input class="fixed" type="text" name="horde_user" value="<?php echo Horde::getFormData('horde_user') ?>" /></td>
</tr>
<tr>
  <td align="right" class="light"><b><?php echo _("Password") ?></b>&nbsp;</td>
  <td><input class="fixed" type="password" name="horde_pass" value="" /></td>
</tr>
<tr>
  <td></td>
  <td class="light"><input class="button" value="<?php echo _("Log in") ?>" type="submit" onclick="return checkLoginForm();" /></td>
</tr>
</table>
</form>
<?php if (@is_readable('./config/motd.php')) include './config/motd.php'; ?>

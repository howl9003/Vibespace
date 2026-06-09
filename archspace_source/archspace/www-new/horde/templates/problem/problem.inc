<form method="post" name="problem_report">
<input type="hidden" name="actionID" value="<?php echo HORDE_CANCEL_PROBLEM_REPORT ?>" />
<?php if (isset($return_url)): ?><input type="hidden" name="return_url" value="<?php echo $return_url ?>" /><?php endif; ?>

<table border="0" align="center" cellpadding="3" cellspacing="2">
<tr class="header">
    <td colspan="2" class="header"><b><?php echo $label ?></b></td>
</tr>

<tr>
    <td class="light" align="right"><?php echo _("Your Name:") ?></td>
    <td class="item"><input type="text" tabindex="1" name="name" value="<?php echo $name ?>" size="70" /></td>
</tr>

<tr>
    <td class="light" align="right"><?php echo _("Your Email Address:") ?></td>
    <td class="item"><input type="text" tabindex="2" name="email" value="<?php echo $email ?>" size="70" /></td>
</tr>

<tr>
    <td class="light" align="right"><?php echo _("Short Summary:") ?></td>
    <td class="item"><input type="text" tabindex="3" name="subject" value="<?php echo $subject ?>" size="70" /></td>
</tr>

<tr>
    <td colspan="2" class="smallheader"><b><?php echo _("Full Description:") ?></b></td>
</tr>
<tr>
    <td colspan="2" align="center"><textarea tabindex="4" name="message" rows="20" cols="80" wrap="hard"><?php echo $message ?></textarea></td>
</tr>

<tr align="center">
  <td align="center" colspan="2" class="light">
    <input class="button" type="submit" name="formsubmit" onclick="return validate();" value="<?php echo _("Send Problem Report") ?>" />
    <input class="button" type="submit" name="formsubmit" value="<?php echo _("Cancel Problem Report") ?>" />
  </td>
</tr>

</table>
</form>

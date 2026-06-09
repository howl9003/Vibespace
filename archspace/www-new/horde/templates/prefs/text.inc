<?php echo $_prefs[$pref]['desc'] ?><br />
<input name="<?php echo $pref ?>" size="30" maxlength="60" class="fixed" value="<?php echo htmlspecialchars($prefs->getValue($pref)) ?>" /><br />

<?php echo $_prefs[$pref]['desc'] ?><br />
<div class="fixed">
<textarea wrap="hard" name="<?php echo $pref ?>" rows="6" cols="80" class="fixed"><?php echo htmlspecialchars($prefs->getValue($pref)) ?></textarea><br />
</div>

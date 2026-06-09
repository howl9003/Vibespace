<input type="checkbox" id="<?php echo $pref ?>" name="<?php echo $pref ?>"<?php echo $prefs->getValue($pref) ? ' checked="checked"' : '' ?> /><label for="<?php echo $pref ?>">
<?php echo $_prefs[$pref]['desc'] ?></label><br />

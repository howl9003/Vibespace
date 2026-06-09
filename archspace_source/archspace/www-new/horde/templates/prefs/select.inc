<?php echo $_prefs[$pref]['desc'] ?><br />
<select name="<?php echo $pref ?>">
    <option value=""<?php if (!$prefs->getValue($pref)) echo ' selected="selected"'; ?>><?php echo _("Use Default Value") ?></option>
<?php $select_var = $pref . '_options'; foreach ($GLOBALS[$select_var] as $select_key => $select_val): ?>
    <option value="<?php echo $select_key ?>"<?php if ($select_key == $prefs->getValue($pref)) echo ' selected="selected"' ?>><?php echo $select_val ?></option>
<?php endforeach; ?>
</select>
<br />

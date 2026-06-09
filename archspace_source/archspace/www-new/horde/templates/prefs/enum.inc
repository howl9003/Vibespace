<?php echo $_prefs[$pref]['desc'] ?><br />
<select name="<?php echo $pref ?>">
<?php foreach ($_prefs[$pref]['enum'] as $key => $val): ?>
<option<?php echo $prefs->getValue($pref) == $key ? ' selected="selected" ' : '' ?> value="<?php echo $key ?>"><?php echo $val ?></option>
<?php endforeach ?>
</select>
<br />

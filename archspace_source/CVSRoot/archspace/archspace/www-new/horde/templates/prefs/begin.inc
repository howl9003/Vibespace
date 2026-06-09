<form method="post" name="prefs" action="<?php Horde::pselfURL() ?>">
<?php Horde::pformInput() ?>
<input type="hidden" name="actionID" value="<?php echo UPDATE_PREFS ?>" />
<input type="hidden" name="group" value="<?php echo $group ?>" />

<table border="0" align="center" width="100%" cellspacing="0" cellpadding="3">
<tr><td class="header" align="left"><b><?php echo $prefGroups[$group]['label'] ?></b></td></tr>
<tr><td class="item"><table border="0"><tr valign="top"><td class="item">

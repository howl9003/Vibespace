<br />

<table class="outline-lite" width="100%" border="0" cellspacing="1" cellpadding="0">
<tr><td>
<table width="100%" border="0" cellspacing="1" cellpadding="2">
 <tr class="directory">
  <th class="<?php echo $acts['sbt'] == CVSLIB_SORT_NAME ? 'selected' : '' ?>" align="left">
    <a href="<?php echo $url['name'] ?>"><?php echo Horde::img($acts['ord'] ? 'up.gif' : 'down.gif', 'width="13" height="13" alt="' . _("Sort Order") . '"') ?></a>&nbsp;
    <a href="<?php echo $url['name'] ?>"><?php echo _("File") ?></a>
  </th>
  <th class="<?php echo $acts['sbt']==CVSLIB_SORT_REV ? 'selected' : '' ?>" align="left" nowrap="nowrap">
    <a href="<?php echo $url['rev'] ?>"><?php echo Horde::img($acts['ord'] ? 'up.gif' : 'down.gif', 'width="13" height="13" alt="' . _("Sort Order") . '"') ?></a>&nbsp;
    <a href="<?php echo $url['rev'] ?>"><?php echo _("Rev") ?></a>
  </th>
  <th class="<?php echo $acts['sbt']==CVSLIB_SORT_AUTHOR ? 'selected' : '' ?>" align="left" nowrap="nowrap">
    <a href="<?php echo $url['author'] ?>"><?php echo Horde::img($acts['ord'] ? 'up.gif' : 'down.gif', 'width="13" height="13" alt="'. _("Sort Order") . '"') ?></a>&nbsp;
    <a href="<?php echo $url['author'] ?>"><?php echo _("Author") ?></a>
  </th>
  <th class="<?php echo $acts['sbt']==CVSLIB_SORT_AGE ? 'selected' : '' ?>" align="left" nowrap="nowrap">
    <a href="<?php echo $url['age'] ?>"><?php echo Horde::img($acts['ord'] ? 'up.gif' : 'down.gif', 'width="13" height="13" alt="' . _("Sort Order") . '"') ?></a>&nbsp;
    <a href="<?php echo $url['age'] ?>"><?php echo _("Date") ?></a>
  </th>
  <th align="left"><?php echo _("Last Log") ?></th>
 </tr>

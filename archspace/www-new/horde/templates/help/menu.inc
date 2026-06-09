<?php
    $show_topics = Horde::url($registry->getParam('webroot', 'horde') . '/help.php');
    $show_topics = Horde::addParameter($show_topics, 'module=' . $module);
    $show_topics = Horde::addParameter($show_topics, 'show=topics');
?>
<table width="100%" cellspacing="0" cellpadding="4" border="0">
<tr>
  <td class="menu" align="left">
    <?php echo Horde::link($show_topics, _("List Help Topics"), 'menuitem', 'help_main') ?><?php echo _("List Help Topics") ?></a>
  </td>
  <td class="menu" align="right">
    <?php echo Horde::link('#', _("Close Help Window"), 'menuitem', '', 'parent.close(); return false;') ?><?php echo _("Close Help") ?></a>
  </td>
</tr>
</table>

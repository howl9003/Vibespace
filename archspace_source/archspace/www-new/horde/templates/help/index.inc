<?php
    $main_url = Horde::url($registry->getParam('webroot', 'horde') . '/help.php');
    $main_url = Horde::addParameter($main_url, 'show=entry');
    $main_url = Horde::addParameter($main_url, 'module=' . $module);
    $main_url = Horde::addParameter($main_url, 'topic=' . $topic);

    $menu_url = Horde::url($registry->getParam('webroot', 'horde') . '/help.php');
    $menu_url = Horde::addParameter($menu_url, 'module=' . $module);
    $menu_url = Horde::addParameter($menu_url, 'show=menu');
?>
<html>
<head>
    <title><?php echo _("Help") ?></title>
</head>

<frameset rows="30,*" frameborder="no" border="0" framespacing="0">
    <frame name="help_menu" scrolling="no" marginheight="0" marginwidth="0" src="<?php echo $menu_url ?>" />
    <frame name="help_main" resizeable="yes" marginheight="2" marginwidth="2" src="<?php echo $main_url ?>" />
</frameset>

</html>

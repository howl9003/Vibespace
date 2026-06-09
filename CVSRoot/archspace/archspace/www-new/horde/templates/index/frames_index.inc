<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "DTD/xhtml1-frameset.dtd">
<html>
<head>
    <link rel="SHORTCUT ICON" href="<?php echo $registry->getParam('graphics')?>/favicon.ico" type="image/x-icon" />
    <title><?php echo $registry->getParam('name'); ?></title>
</head>

<frameset rows="*,55" border="0">
    <frame name="horde_main" src="<?php echo $main_page ?>" scrolling="<?php echo $scrollbar ?>" noresize="noresize" frameborder="0" />
    <frame name="horde_menu" src="<?php echo Horde::applicationUrl('menu.php') ?>" scrolling="no" marginheight="0" marginwidth="0" noresize="noresize" frameborder="0" />
</frameset>

</html>

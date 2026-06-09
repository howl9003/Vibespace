<html>
<head>
<script language="javascript" type="text/javascript">
<!--
var ypos = screen.availHeight - 100;
<?php if ($browser->isBrowser('mozilla')): ?>
window.open('<?php echo Horde::applicationUrl('menu.php') ?>','horde_bar','directories=no,menubar=no,toolbar=no,titlebar=no,dependent=yes,screenX=0,screenY=' + ypos + ',innerHeight=80,innerWidth=<?php echo $flmenu_width ?>');
<?php else: ?>
window.open('<?php echo Horde::applicationUrl('menu.php') ?>','horde_bar','directories=no,menubar=no,toolbar=no,titlebar=no,Height=80,Width=<?php echo $flmenu_width ?>,top=' + ypos + ',left=0');
<?php endif; ?>
self.location.href='<?php echo $main_page ?>';
//-->
</script>
</head>
</html>

<?php
if (isset($language) && isset($nls['charsets'][$language])) {
    header('Content-type: text/html; charset=' . $nls['charsets'][$language]);
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!-- Horde: Copyright 2000, The Horde Project. Horde is under the LGPL. -->
<!--                  Horde Project: http://horde.org/                  -->
<!-- GNU Library Public License: http://www.fsf.org/copyleft/lgpl.html  -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->getParam('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\" />\n";
}

?>
<title><?php echo $page_title ?></title>
<link href="<?php echo $registry->getParam('webroot', 'horde') ?>/css.php" rel="stylesheet" type="text/css" />
<link rel="SHORTCUT ICON" href="<?php echo $registry->getParam('graphics')?>/favicon.ico" type="image/x-icon" />
</head>

<body<?php if (!empty($js_onLoad)) echo ' onload="' . $js_onLoad . '"'; if (!empty($bodyStyle)) echo ' class="' . $bodyStyle . '"' ?>>

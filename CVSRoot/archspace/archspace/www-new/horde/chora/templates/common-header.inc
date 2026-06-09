<?php
if (isset($language) && isset($nls['charsets'][$language])) {
    header('Content-type: text/html; charset=' . $nls['charsets'][$language]);
    header('Vary: Accept-Language');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<!--          Chora: Copyright 2000-2004, The Horde Project.           -->
<!-- Horde Project: http://horde.org/ | Chora: http://horde.org/chora/ -->
<!--     GNU Public License: http://www.fsf.org/copyleft/gpl.html      -->
<?php echo !empty($language) ? '<html lang="' . strtr($language, '_', '-') . '">' : '<html>' ?>
<head>
<?php

$page_title = $registry->getParam('name');
if (!empty($title)) $page_title .= ' :: ' . $title;
if (!empty($refresh_time) && ($refresh_time > 0) && !empty($refresh_url)) {
    echo "<meta http-equiv=\"refresh\" content=\"$refresh_time;url=$refresh_url\">\n";
}

?>
<title><?php echo $page_title ?></title>
<link href="<?php echo $registry->getParam('webroot', 'horde') ?>/css.php?app=chora" rel="stylesheet" type="text/css" />
</head>

<body<?php if (!empty($js_onLoad)) echo ' onload="' . $js_onLoad . '"'; if (!empty($bodyStyle)) echo ' class="' . $bodyStyle . '"' ?>>

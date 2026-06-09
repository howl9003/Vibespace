<table border="0" cellpadding="2" cellspacing="0" width="100%">
<tr>
    <td class="menu">

<table border="0" width="100%">
<tr>
<?php

/* Chora menu items */
Menu::printItem(Horde::applicationUrl('cvs.php'), $registry->getParam('name'), 'chora.gif', $registry->getParam('graphics', 'chora'));

if (($conf['prefs']['driver'] != '') && ($conf['prefs']['driver'] != 'none')) {
    Menu::printItem(Horde::applicationUrl('prefs.php'), _("Options"), 'prefs.gif', $registry->getParam('graphics', 'horde'));
}

/* Horde menu items */
if ($conf['problems']['enabled']) {
    Menu::printItem(Horde::url($registry->getParam('webroot', 'horde') . '/problem.php?return_url=' . urlencode(Horde::url($_SERVER['PHP_SELF'], true))), _("Problem?"), 'problem.gif', $registry->getParam('graphics', 'horde'));
}

/* Additional site-specific menu items. */
if (!empty($additional_items)) echo $additional_items;

/* Online Help */
if ($conf['user']['online_help'] && $browser->hasFeature('javascript')) {
    Menu::printItem(Help::listLink('chora'), _("Help"), 'help.gif', $registry->getParam('graphics', 'horde'));
}

/* Links to other Horde applications. */
if (isset($conf['menu']['apps']) && is_array($conf['menu']['apps'])) {
    foreach ($conf['menu']['apps'] as $app) {
        Menu::printItem(Horde::url($registry->getInitialPage($app)),
                        $registry->getParam('name', $app), $registry->getParam('icon', $app), '');
    }
}

?>
<td width="100%">&nbsp;</td><!-- spacer -->
</tr></table></td></tr></table>
<br />

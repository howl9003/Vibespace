<table border="0" cellpadding="2" cellspacing="0" width="100%"><tr><td class="menu">
<table border="0" width="100%">
<tr>

<?php
/* Horde navbar items. */
if (Auth::getAuth()) {
    Menu::printItem(Horde::applicationUrl('login.php'), _("Home"), 'home.gif');

    if (($conf['prefs']['driver'] != '') && ($conf['prefs']['driver'] != 'none')) {
        Menu::printItem(Horde::applicationUrl('prefs.php'), _("Options"), 'prefs.gif');
    }
} else {
    Menu::printItem(Horde::url('login.php'), _("Log in"), 'login.gif');
}

/* Online Help. */
if ($conf['user']['online_help'] && $browser->hasFeature('javascript')) {
    Menu::printItem(Help::listLink('horde'), _("Help"), 'help.gif');
}
?>
<td width="100%">&nbsp;</td><!-- spacer -->
</tr>
</table>
</td></tr></table>

<br />

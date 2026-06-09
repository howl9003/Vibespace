<?php
/*
 * $Horde: horde/menu.php,v 2.14.2.12 2003/01/03 12:57:45 jan Exp $
 *
 * Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/base.php';
require_once HORDE_BASE . '/lib/Menu.php';

/* Define target */
$opener = false;
if ($conf['menu']['floating_bar'] && (!$browser->hasQuirk('avoid_popup_windows'))) {
    $opener = true;
    $title = _("Horde System");
}

$js_onLoad = null;
require HORDE_TEMPLATES . '/common-header.inc';

/* Build the menu out of the modules array */
$moduletext = '';

/*
 * Now print a link for each module (no links are fine since there
 * will be other stuff here)
 */
foreach ($registry->applications as $service => $params) {
    if ($params['status'] == 'active') {
        if (!$opener) {
            $moduletext .= Menu::createItem(Horde::url($params['webroot'] . '/' . (isset($params['initial_page']) ? $params['initial_page'] : '')),
                                            gettext($params['name']), $params['icon'], '', 'horde_main');
        } else {
            $moduletext .= Menu::createItem('', gettext($params['name']), $params['icon'],
                                            '', 'horde_main',
                                            'window.opener.location.href=\'' . Horde::url($params['webroot']) . '\'; return false;');
        }
    }
}

/* Add a logout link */
$moduletext .= Menu::createItem(Horde::applicationUrl('login.php?reason=logout'), _("Log out"), 'logout.gif', null, 'horde_main');

require HORDE_TEMPLATES . '/horde/modules.inc';
require HORDE_TEMPLATES . '/common-footer.inc';

?>

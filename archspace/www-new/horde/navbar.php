<?php
/**
 * $Horde: horde/navbar.php,v 2.4.2.1 2003/02/08 12:10:13 jan Exp $
 *
 * Copyright 2002-2003 Charles J. Hagenbuch, <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once HORDE_BASE . '/lib/Menu.php';
require_once HORDE_BASE . '/lib/Help.php';

require HORDE_TEMPLATES . '/navbar/menu.inc';
$notification->notify();

/* Include the JavaScript for the help system (if enabled). */
if ($conf['user']['online_help'] && $browser->hasFeature('javascript')) {
    Help::javascript();
}

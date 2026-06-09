<?php
/**
 * $Horde$
 *
 * Copyright 2001-2004 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (GPL). If you did
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 */

require_once HORDE_BASE . '/lib/Menu.php';

/* Check for additional site-specific menu items (in config/menu.php). */
$additional_items = '';
if (@is_readable(CHORA_BASE . '/config/menu.php')) {
    include_once CHORA_BASE . '/config/menu.php';
    foreach ($_menu as $item) {
        $additional_items .= Menu::customItem($item);
    }
}

require CHORA_TEMPLATES . '/menu/menu.inc';

/* Include the JavaScript for the help system (if enabled). */
if ($conf['user']['online_help'] && $browser->hasFeature('javascript')) {
    Help::javascript();
}

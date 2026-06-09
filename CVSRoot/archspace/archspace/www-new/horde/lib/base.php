<?php
/*
 * $Horde: horde/lib/base.php,v 1.3.2.6 2003/01/03 12:48:38 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/*
 * Horde base inclusion file.
 *
 * This file brings in all of the dependencies that Horde framework-level
 * scripts will need, and sets up objects that all scripts use.
 *
 * NB: this base file does _not_ check authentication, so as to avoid
 * an infinite loop on the Horde login page. You'll need to do it
 * yourself in framework-level pages.
 */

// Find the base file path of Horde
@define('HORDE_BASE', dirname(__FILE__) . '/..');

// Registry
require_once HORDE_BASE . '/lib/Registry.php';
$registry = &Registry::singleton();
$registry->pushApp('horde');
$conf = &$GLOBALS['conf'];
@define('HORDE_TEMPLATES', $registry->getParam('templates'));

// Horde base libraries
require_once HORDE_BASE . '/lib/Horde.php';
require_once HORDE_BASE . '/lib/Auth.php';

// Browser detection object
require_once HORDE_BASE . '/lib/Browser.php';
$browser = new Browser();

// Notification System
require_once HORDE_BASE . '/lib/Notification.php';
$notification = &Notification::singleton();
$notification->attach('status');

?>

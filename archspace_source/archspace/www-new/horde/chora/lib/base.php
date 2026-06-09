<?php
// $Horde: chora/lib/base.php,v 1.66.2.6 2002/12/19 12:24:08 jan Exp $

/*
 * Chora base inclusion file.
 *
 * This file brings in all of the dependencies that every Chora script
 * will need, and sets up objects that all scripts use.
 */

// Find the base file path of Horde
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Find the base file path of Chora
@define('CHORA_BASE', dirname(__FILE__) . '/..');

// Horde base libraries
require_once HORDE_BASE . '/lib/Horde.php';
require_once HORDE_BASE . '/lib/Auth.php';
require_once HORDE_BASE . '/lib/Text.php';
require_once HORDE_BASE . '/lib/Help.php';

// Browser detection library
require_once HORDE_BASE . '/lib/Browser.php';
$browser = new Browser();

// Registry
require_once HORDE_BASE . '/lib/Registry.php';
$registry = &Registry::singleton();
$registry->pushApp('chora');
$conf = &$GLOBALS['conf'];
@define('CHORA_TEMPLATES', $registry->getParam('templates'));

// Chora libraries and config
require_once CHORA_BASE . '/config/cvsroots.php';
require_once CHORA_BASE . '/config/mime_drivers.php';
require_once CHORA_BASE . '/lib/Chora.php';
require_once CHORA_BASE . '/lib/version.php';

// Don't allow access unless there is a Horde login, or guests are
// allowed.

// modified to work with archspace
include 'libportal.php';
$is_admin = get_is_admin( "localhost 5000");

if (!(Auth::getAuth() || $registry->allowGuests()) || !($is_admin=="YES")) {
   // header('Location: ' . Horde::url($registry->getParam('webroot', 'horde') . '/login.php?url=' . urlencode(Horde::selfUrl()), true));
    header("Location: /no_access.html");
    echo "\n";
    exit;
}

if (Chora::isRestricted($where)) {
    fatal('403 Forbidden', "$where: Forbidden by server configuration");
}

/* Start compression, if requested. */
if ($conf['compress_pages']) {
    ob_start('ob_gzhandler');
}

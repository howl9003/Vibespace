<?php
/*
 * $Horde: horde/prefs.php,v 2.1.2.7 2003/02/08 12:12:45 jan Exp $
 *
 * Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/**
 * Call PrefsUI::groupIsEditable() here because we can rely on it being present
 * in Horde 2.1 but the prefs template still calls groupIsEditable().
 */
function groupIsEditable($group)
{
    return PrefsUI::groupIsEditable($group);
}

function prefs_callback()
{
    global $prefs, $js_onLoad;

    if ($prefs->isDirty('menu_view')) {
        $js_onLoad = 'if (window.parent.frames.horde_menu) window.parent.frames.horde_menu.location.reload();';
    }
}

define('HORDE_BASE', dirname(__FILE__));
require_once HORDE_BASE . '/lib/base.php';
require_once HORDE_BASE . '/lib/PrefsUI.php';
require HORDE_BASE . '/config/prefs.php';

if (!Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl('login.php'), true);
    exit;
}

$js_onLoad = null;

/* See if we have a preferences group set. */
$group = Horde::getFormData('group');

if (PrefsUI::handleForm($group)) {
    $group = null;
    include HORDE_BASE . '/config/prefs.php';
}

Horde::compressOutput();
if (!$prefs->isLocked('initial_application')) {
    $initial_application_options = array();
    $apps = $registry->listApps();
    foreach ($apps as $app) {
        $initial_application_options[$app] = $registry->getParam('name', $app);
    }
}



$title = _("User Options");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_BASE . '/navbar.php';

/* Assign variables for select lists. */
if (!$prefs->isLocked('timezone')) {
    $timezone_options = &$tz;
}

/* Show the UI. */
PrefsUI::generateUI($group);

$registry->shutdown();
require HORDE_TEMPLATES . '/common-footer.inc';

<?php
/*
* $Horde: horde/maintenance.php,v 1.3.2.7 2003/01/03 12:57:44 jan Exp $
*
* Copyright 1999-2003 Charles J. Hagenbuch <chuck@horde.org>
* Copyright 1999-2003 Jon Parise <jon@horde.org>
* Copyright 2001-2003 Michael Slusarz <slusarz@bigworm.colorado.edu>
*
* See the enclosed file COPYING for license information (LGPL).  If you
* did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
*/

include_once './lib/base.php';
include_once HORDE_BASE . '/lib/Maintenance.php';

/* Make sure there is a user logged in. */
if (!Auth::getAuth()) {
    header('Location: ' . Horde::url($registry->getParam('webroot', 'horde') . '/login.php?url=' . urlencode(Horde::selfUrl()), true));
    echo "\n";
    exit;
}

$module = basename(Horde::getFormData('module', ''));
/* If no 'module' parameter passed in, return error. */
if (!$module) {
    Horde::fatal(new PEAR_Error(_("Do not directly access maintenance.php")), __FILE__, __LINE__);
}

Horde::compressOutput();

/* Load the module specific maintenance class now. */
include_once $registry->getParam('fileroot', $module) . '/lib/Maintenance/' . $module . '.php';
$class = 'Maintenance_' . $module;
$maint = new $class;

/* Print top part of confirmation page. */
/* TODO: Allow each application to override the maintenance templates with
         their own templates stored in their directories. */
$js_onLoad = null;
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/maintenance/confirm_top.inc';
if ($browser->hasFeature('javascript')) {
    include HORDE_TEMPLATES . '/maintenance/javascript.inc';
}

/* Get list of maintenance tasks that need to be confirmed for this login. */
$tasks = $maint->confirmationMaintenance();

/* Go through list of tasks and print out confirmation messages.
   $pref, $descrip, & $checked need to be set for the templates. */
foreach ($tasks as $pref) {
    list($descrip, $checked) = $maint->infoMaintenance($pref);
    include HORDE_TEMPLATES . '/maintenance/confirm_middle.inc';
}

/* Print bottom of confirmation page. */
require HORDE_TEMPLATES . '/maintenance/confirm_bottom.inc';
require HORDE_TEMPLATES . '/common-footer.inc';

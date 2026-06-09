<?php
/**
 * $Horde$
 *
 * Copyright 2001-2004 Jon Parise <jon@horde.org>
 * Copyright 2002-2004 Jan Schneider <jan@horde.org>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/**
 * Encapsulate the logic saying whether or not a preferences group is
 * editable.
 */
function groupIsEditable($group)
{
    global $prefs, $prefGroups;
    static $results;

    if (!isset($results)) $results = array();

    if (!isset($results[$group])) {
        $results[$group] = false;
        foreach ($prefGroups[$group]['members'] as $pref) {
            if (!$prefs->isLocked($pref)) {
                $results[$group] = true;
                return true;
            }
        }
    } else {
        return $results[$group];
    }

    return false;
}

define('CHORA_BASE', dirname(__FILE__));
require_once CHORA_BASE . '/lib/base.php';
require CHORA_BASE . '/config/prefs.php';

/* See if we have a preferences group set. */
$group = Horde::getFormData('group');

/* Run through the action handlers */
$actionID = Horde::getFormData('actionID', NO_ACTION);
switch ($actionID) {

 case UPDATE_PREFS:
     if (isset($group) && groupIsEditable($group)) {
         $updated = false;

         foreach ($prefGroups[$group]['members'] as $pref) {
             if (!$prefs->isLocked($pref) ||
                 $_prefs[$pref]['type'] == 'special') {
                 switch ($_prefs[$pref]['type']) {

                     // These either aren't set or are set in other parts
                     // of the UI.
                 case 'implicit':
                 case 'link':
                     break;

                 case 'select':
                 case 'text':
                 case 'textarea':
                     $prefs->setValue($pref, Horde::getFormData($pref));
                     $updated = true;
                     break;

                 case 'enum':
                     $val = Horde::getFormData($pref);
                     if (isset($_prefs[$pref]['enum'][$val])) {
                         $prefs->setValue($pref, $val);
                         $updated = true;
                     } else {
                         Horde::raiseMessage(_("An illegal value was specified."), HORDE_ERROR);
                     }
                     break;

                 case 'number':
                     $num = Horde::getFormData($pref);
                     if (intval($num) != $num) {
                         Horde::raiseMessage(_("This value must be a number."), HORDE_ERROR);
                     } elseif ($num == 0) {
                         Horde::raiseMessage(_("This number must be at least one."), HORDE_ERROR);
                     } else {
                         $prefs->setValue($pref, $num);
                         $updated = true;
                     }
                     break;

                 case 'checkbox':
                     $val = Horde::getFormData($pref);
                     $prefs->setValue($pref, isset($val) ? 1 : 0);
                     $updated = true;
                     break;

                 case 'special':
                     // Code for special elements must be written
                     // specifically for each application.
                     break;

                 }
             }
         }

         // Do anything that you need to do as a result of certain
         // preferences changing.
         if ($prefs->isDirty('language')) {
             Lang::setLang($prefs->getValue('language'));
             Lang::setDomain('chora', CHORA_BASE . '/locale', $registry->getCharset());
             include CHORA_BASE . '/config/prefs.php';
         }

         if ($updated) {
             $prefs->store();
             Horde::raiseMessage(_("Your options have been updated."), HORDE_MESSAGE);
             $group = null;
         }
     }

     break;
}

/*
 * Assign variables to hold select lists
 */
if (!$prefs->isLocked('language')) {
    $language_options = &$nls['languages'];
}

$title = _("User Options");
$js_onLoad = null;
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_BASE . '/menu.php';
require CHORA_BASE . '/status.php';

if (!empty($group) &&
    groupIsEditable($group)) {
    include $registry->getParam('templates', 'horde') . '/prefs/begin.inc';
    foreach ($prefGroups[$group]['members'] as $pref) {
        if (!$prefs->isLocked($pref)) {
            switch ($_prefs[$pref]['type']) {
             case 'implicit':
                break;

             case 'special':
                include CHORA_TEMPLATES . "/prefs/$pref.inc";
                break;

             default:
                include $registry->getParam('templates', 'horde') . '/prefs/' . $_prefs[$pref]['type'] . '.inc';
                break;
            }
        }
    }
    include $registry->getParam('templates', 'horde') . '/prefs/end.inc';
} else {
    $columns = array();
    foreach ($prefGroups as $group => $gvals) {
        $col = $gvals['column'];
        unset($gvals['column']);
        $columns[$col][$group] = $gvals;
    }
    $span = round(100 / count($columns));
    include $registry->getParam('templates', 'horde') . '/prefs/overview.inc';
}

$registry->shutdown();
require CHORA_TEMPLATES . '/common-footer.inc';

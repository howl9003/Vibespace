<?php
/**
 * Class for auto-generating the preferences user interface and
 * processing the forms.
 *
 *
 * $Horde: horde/lib/PrefsUI.php,v 1.2.2.8 2003/02/18 00:07:56 jan Exp $
 *
 * Copyright 2002-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.1
 * @package horde.prefs
 */
class PrefsUI {

    /**
     * Determine whether or not a preferences group is editable.
     *
     * @access public
     *
     * @param string $group  The preferences group to check.
     *
     * @return boolean  Whether or not the group is editable.
     */
    function groupIsEditable($group)
    {
        global $prefs, $prefGroups;
        static $results;

        if (!isset($results)) {
            $results = array();
        }

        if (!array_key_exists($group, $results)) {
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

    /**
     * Handle a preferences form submission if there is one, updating
     * any preferences which have been changed.
     *
     * @param optional string $group  The preferences group that was edited.
     *
     * @return boolean  Whether preferences have been updated.
     */
    function handleForm($group = null)
    {
        global $prefs, $prefGroups, $_prefs, $notification, $registry;

        $updated = false;

        /* Run through the action handlers */
        $actionID = Horde::getFormData('actionID', NOOP);
        switch ($actionID) {

        case HORDE_UPDATE_PREFS:
            if (isset($group) && PrefsUI::groupIsEditable($group)) {
                $updated = false;

                foreach ($prefGroups[$group]['members'] as $pref) {
                    if (!$prefs->isLocked($pref) ||
                        ($_prefs[$pref]['type'] == 'special')) {
                        switch ($_prefs[$pref]['type']) {

                        /* These either aren't set or are set in other parts
                           of the UI. */
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
                                $notification->push(_("An illegal value was specified."), 'horde.error');
                            }
                            break;

                        case 'number':
                            $num = Horde::getFormData($pref);
                            if (intval($num) != $num) {
                                $notification->push(_("This value must be a number."), 'horde.error');
                            } elseif ($num == 0) {
                                $notification->push(_("This number must be at least one."), 'horde.error');
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
                            /* Code for special elements must be written
                               specifically for each application. */
                            if (function_exists('handle_' . $pref)) {
                                $updated = call_user_func('handle_' . $pref, $updated);
                            }
                            break;

                        }
                    }
                }

                // Do anything that you need to do as a result of certain
                // preferences changing.
                if ($prefs->isDirty('language')) {
                    Lang::setLang($prefs->getValue('language'));
                    Lang::setTextdomain($registry->getApp(), $registry->getParam('fileroot') . '/locale', Lang::getCharset());
                }

                if ($updated) {
                    if (function_exists('prefs_callback')) {
                        prefs_callback();
                    }
                    $prefs->store();
                    $notification->push(_("Your options have been updated."), 'horde.message');
                    $group = null;
                }
            }
            break;
        }

        return $updated;
    }

    /**
     * Generate the UI for the preferences interface, either for a
     * specific group, or the group selection interface.
     *
     * @param optional string $group  The group to generate the UI for.
     */
    function generateUI($group = null)
    {
        global $prefs, $prefGroups, $_prefs, $registry;

        /* Assign variables to hold select lists. */
        if (!$prefs->isLocked('language')) {
            $GLOBALS['language_options'] = &$GLOBALS['nls']['languages'];
        }

        if (!empty($group) &&
            PrefsUI::groupIsEditable($group)) {
            include $registry->getParam('templates', 'horde') . '/prefs/begin.inc';
            foreach ($prefGroups[$group]['members'] as $pref) {
                if (!$prefs->isLocked($pref)) {
                    switch ($_prefs[$pref]['type']) {

                    case 'implicit':
                        break;

                    case 'special':
                        include $registry->getParam('templates', $registry->getApp()) . "/prefs/$pref.inc";
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
    }

}

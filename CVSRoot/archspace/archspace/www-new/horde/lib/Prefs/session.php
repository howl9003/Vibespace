<?php
/*
 * $Horde: horde/lib/Prefs/session.php,v 1.11.2.4 2003/01/03 12:48:42 jan Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('session_start', true,
    'Prefs_session: Required session functions were not found.');

/**
 * Preferences storage implementation for PHP's session implementation.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3.4
 * @package horde.prefs
 */
class Prefs_session extends Prefs {

    /**
     * Constructs a new session preferences object.
     *
     * @param string $user     The user who owns these preferences.
     * @param string $password The password associated with $user. (unused)
     * @param string $scope    The current preferences scope.
     * @param array  $params   A hash containing connection parameters. (unused)
     * @param boolean $caching  (optional) Should caching be used?
     */
    function Prefs_session($user, $password = '', $scope = '',
        $params = array(), $caching = false)
    {
        $this->user = $user;
        $this->scope = $scope;
        $this->caching = $caching;
    }

    /**
     * Retrieves the requested set of preferences from the current session.
     *
     * @param optional array $prefs  An array listing the preferences to
     *                     retrieve. If not specified, retrieve all of the
     *                     preferences listed in the $prefs hash.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function retrieve($prefs = array())
    {
        if (!session_is_registered('horde_prefs')) {
            return (new PEAR_Error(_("No preferences are available.")));
        }

        /* Retrieve global and local preferences from the session variable. */
        $global_prefs = array();
        if (isset($_SESSION['horde_prefs']['horde'])) {
            $global_prefs =
                $_SESSION['horde_prefs']['horde'];
        }
        $local_prefs = array();
        if (isset($_SESSION['horde_prefs'][$this->scope])) {
            $local_prefs =
                $_SESSION['horde_prefs'][$this->scope];
        }

        /* Retrieve and store the local and global preferences. */
        $this->prefs = array_merge($global_prefs, $local_prefs);

        return true;
    }

    /**
     * Stores preferences in the current session.
     *
     * @param array $prefs  (optional) An array listing the preferences to be
     *                      stored. If not specified, store all of the
     *                      preferences listed in the $prefs hash.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function store($prefs = array())
    {
        /* Create and register the preferences array, if necessary. */
        if (!isset($_SESSION['horde_prefs'])) {
            global $horde_prefs;

            $horde_prefs = array();
            $_SESSION['horde_prefs'] = &$horde_prefs;
            if (!session_register('horde_prefs')) {
                Horde::fatal(_("Unable to register preferences in session."), __FILE__, __LINE__);
            }
        }

        /* Copy the current preferences into the session variable. */
        $GLOBALS['horde_prefs'] = &$_SESSION['horde_prefs'];
        foreach ($this->prefs as $name => $pref) {
            $scope = $this->getScope($name);
            $_SESSION['horde_prefs'][$scope][$name] = $pref;
        }

        return true;
    }

    /**
     * Perform cleanup operations.
     *
     * @param boolean  $all    (optional) Cleanup all Horde preferences.
     */
    function cleanup($all = false)
    {
        /* Perform a Horde-wide cleanup? */
        if ($all) {
            $_SESSION['horde_prefs'] = null;
            session_unregister('horde_prefs');
        } else {
            unset($_SESSION['horde_prefs'][$this->scope]);
            $_SESSION['horde_prefs']['_filled'] = false;
        }

        parent::cleanup($all);
    }
}
?>

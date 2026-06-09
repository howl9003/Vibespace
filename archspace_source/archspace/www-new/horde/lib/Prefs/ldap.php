<?php
/*
 * $Horde: horde/lib/Prefs/ldap.php,v 1.14.2.20 2003/05/09 14:49:32 chuck Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once 'PEAR.php';
Horde::functionCheck('ldap_connect', true,
    'Prefs_ldap: Required LDAP functions were not found.');

/**
 * Preferences storage implementation for PHP's LDAP extention.
 *
 * Required values for $params:
 *      'hostspec'      The hostname of the LDAP server.
 *      'port'          The port of the LDAP server.
 *      'basedn'        The base DN for the LDAP server.
 *      'uid'           The username search key.
 *
 * Optional values for $varams:
 *      'version'       The version of the LDAP protocol to use.
 *      'rootdn'        The DN of the root (administrative) account.
 *      'username'      The user as which to bind for write operations.
 *      'password'      'username's password for bind authentication.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.prefs
 */
class Prefs_ldap extends Prefs {

    /** Hash containing connection parameters.
        @var array $params */
    var $params = array();

    /** Handle for the current LDAP connection.
        @var int $connection */
    var $connection;

    /** Boolean indicating whether or not we're connected to the LDAP server.
        @var boolean $connected */
    var $_connected = false;

    /** String holding the user's DN.
        @var string $dn */
    var $dn = '';


    /**
     * Constructs a new LDAP preferences object.
     *
     * @param string $user      The user who owns these preferences.
     * @param string $password  The password associated with $user.
     * @param string $scope     The current application scope.
     * @param array  $params    A hash containing connection parameters.
     * @param boolean $caching  (optional) Should caching be used?
     */
    function Prefs_ldap($user, $password, $scope = '', $params = array(),
        $caching = false)
    {
        $this->user = $user;
        $this->scope = $scope;
        $this->params = $params;
        $this->caching = $caching;

        /* If a valid server port has not been specified, set the default. */
        if (!isset($this->params['port']) || !is_int($this->params['port'])) {
            $this->params['port'] = 389;
        }

        /*
         * If $params['username'] is empty, set it to the name of the
         * current user.  Also, set $params['password'] to the current
         * user's password.
         *
         * Note: This assumes the user is allowed to modify their own LDAP
         *       entry.
         */
        if (empty($this->params['username']) &&
            empty($this->params['rootdn'])) {
            /* then */
            $this->params['username'] = $user;
            $this->params['password'] = $password;
        }
    }

    /**
     * Opens a connection to the LDAP server.
     *
     * @return mixed         True on success or a PEAR_Error object on failure.
     */
    function _connect()
    {
        if (!is_array($this->params)) {
            Horde::fatal(new PEAR_Error(_("No configuration information specified for LDAP Preferences.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['hostspec'])) {
            Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in preferences configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['basedn'])) {
            Horde::fatal(new PEAR_Error(_("Required 'basedn' not specified in preferences configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['uid'])) {
            Horde::fatal(new PEAR_Error(_("Required 'uid' not specified in preferences configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['username']) && !isset($this->params['rootdn'])) {
            Horde::fatal(new PEAR_Error(_("Required 'username' not specified in preferences configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['password'])) {
            Horde::fatal(new PEAR_Error(_("Required 'password' not specified in preferences configuration.")), __FILE__, __LINE__);
        }

        /* Connect to the LDAP server anonymously. */
        $conn = ldap_connect($this->params['hostspec'], $this->params['port']);
        if (!$conn) {
            Horde::logMessage(
                sprintf('Failed to open an LDAP connection to %s.',
                        $this->params['hostspec']),
                __FILE__, __LINE__);
            return false;
        }

        /* Set the LDAP protocol version. */
        if (array_key_exists('version', $this->params)) {
            if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION,
                                 $this->params['version'])) {
                Horde::logMessage(
                    sprintf('Set LDAP protocol version to %d failed: [%d] %s',
                            $this->params['version'],
                            ldap_errno($this->connection),
                            ldap_error($this->connection)),
                            __FILE__, __LINE__);
            }
        }

        /* Register our callback function to handle referrals. */
        if (function_exists('ldap_set_rebind_proc') &&
            !ldap_set_rebind_proc($conn, array($this, '_rebind_proc'))) {
            /* then */
            Horde::logMessage(
                sprintf('Set rebind proc failed: [%d] %s',
                        ldap_errno($this->connection),
                        ldap_error($this->connection)),
                __FILE__, __LINE__);
            return false;
        }

        /* Define the DN of the current user */
        $this->dn = sprintf('%s=%s,%s', $this->params['uid'],
                            $this->user,
                            $this->params['basedn']);

        /* And the DN of the authenticating user (may be the same as above) */
        if (!empty($this->params['rootdn'])) {
            $bind_dn = $this->params['rootdn'];
        } else {
            $bind_dn = sprintf('%s=%s,%s', $this->params['uid'],
                               $this->params['username'],
                               $this->params['basedn']);
        }

        /* Store the connection handle at the instance level. */
        $this->connection = $conn;
        $this->_connected = true;

        /* Bind to the LDAP server as the authenticating user. */
        $bind = @ldap_bind($this->connection, $bind_dn,
                           $this->params['password']);
        if (!$bind) {
            Horde::logMessage(
                sprintf('Bind to server %s:%d with DN %s failed: [%d] %s',
                        $this->params['hostspec'],
                        $this->params['port'],
                        $bind_dn,
                        ldap_errno($this->connection),
                        ldap_error($this->connection)),
                __FILE__, __LINE__);
            return false;
        }

        /* Search for the user's full DN. */
        $search = ldap_search($this->connection, $this->params['basedn'],
                              $this->params['uid'] . '=' . $this->user,
                              array('dn'));
        if ($search) {
            $result = ldap_get_entries($this->connection, $search);
            if ($result && !empty($result[0]['dn'])) {
                $this->dn = $result[0]['dn'];
            }
        } else {
            Horde::logMessage(
                sprintf('Failed to retrieve user\'s DN: [%d] %s',
                        ldap_errno($this->connection),
                        ldap_error($this->connection)),
                __FILE__, __LINE__);
            return false;
        }

        return true;
    }

    /**
     * Disconnect from the LDAP server and clean up the connection.
     *
     * @return boolean     true on success, false on failure.
     */
    function _disconnect()
    {
        $this->dn = '';
        $this->_connected = false;

        return ldap_close($this->connection);
    }

    /**
     * Callback function for LDAP referrals.  This function is called when an
     * LDAP operation returns a referral to an alternate server.
     *
     * @access private
     *
     * @return integer  1 on error, 0 on success.
     *
     * @since Horde 2.1
     */
    function _rebind_proc($conn, $who)
    {
        /* Strip out the hostname we're being redirected to. */
        $who = preg_replace(array('|^.*://|', '|:\d*$|'), '', $who);

        /* Figure out the DN of the authenticating user. */
        if (!empty($this->params['rootdn'])) {
            $bind_dn = $this->params['rootdn'];
        } else {
            $bind_dn = sprintf('%s=%s,%s', $this->params['uid'],
                               $this->params['username'],
                               $this->params['basedn']);
        }

        /*
         * Make sure the server we're being redirected to is in our list of
         * valid servers.
         */
        if (!strstr($this->params['hostspec'], $who)) {
            Horde::logMessage(
                sprintf('Referral target %s for DN %s is not in the authorized server list!', $who, $bind_dn),
                __FILE__, __LINE__);
            return 1;
        }

        /* Bind to the new server. */
        $bind = @ldap_bind($conn, $bind_dn, $this->params['password']);
        if (!$bind) {
            Horde::logMessage(
                sprintf('Rebind to server %s:%d with DN %s failed: [%d] %s',
                        $this->params['hostspec'],
                        $this->params['port'],
                        $bind_dn,
                        ldap_errno($this->connection),
                        ldap_error($this->connection)),
                __FILE__, __LINE__);
        }

        return 0;
    }

    /**
     * Retrieve a value or set of values for a specified user.
     *
     * @access public
     *
     * @param          string $user     The user to retrieve prefs for.
     * @param          mixed  $values   A string or array with the preferences
     *                                  to retrieve.
     * @param optional string $scope    The preference scope to look in.
     *                                  Defaults to horde.
     *
     * @return mixed    If a single value was requested, the value for that
     *                  preference.  Otherwise, a hash, indexed by pref names,
     *                  with the requested values.
     *
     * @since Horde 2.2
     */
    function getPref($user, $retrieve, $scope = 'horde')
    {
        /* Make sure we're connected. */
        if (!$this->_connected) {
            $this->_connect();
        }

        if (is_array($retrieve)) {
            return array();
        } else {
            return null;
        }
    }

    /**
     * Retrieves the requested set of preferences from the user's LDAP
     * entry.
     *
     * @param optional array $prefs  An array listing the preferences to
     *                     retrieve. If not specified, retrieve all of the
     *                     preferences listed in the $prefs hash.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function retrieve($prefs = array())
    {
        /*
         * If a list of preferences to retrieve hasn't been provided in
         * $prefs, assume all preferences are desired.
         */
        if (count($prefs) == 0) {
            $prefs = Prefs::listAll();
        }
        if (!is_array($prefs) || (count($prefs) == 0)) {
            return (new PEAR_Error(_("No preferences are available.")));
        }

        /* Attempt to pull the values from the session cache first. */
        if ($this->cacheLookup($prefs)) {
            return true;
        }

        /* If we're not already connected, invoke the connect() method. */
        if (!$this->_connected) {
            $this->_connect();
        }

        /*
         * Search for the multi-valued field containing the array of
         * preferences.
         */
        $search = ldap_search($this->connection, $this->params['basedn'],
                              $this->params['uid'] . '=' . $this->user,
                              array($this->scope . 'Prefs', 'hordePrefs'));
        if ($search) {
            $result = ldap_get_entries($this->connection, $search);
        } else {
            Horde::logMessage('Failed to connect to LDAP preferences server.', __FILE__, __LINE__);
        }

        /* ldap_get_entries() converts attribute indexes to lowercase. */
        $field = strtolower($this->scope . 'prefs');

        if (isset($result[0]['hordeprefs'])) {
            $prefs = array();

            /*
             * Set the requested values in the $this->prefs hash based on
             * the contents of the LDAP result.
             *
             * Preferences are stored as colon-separated name:value pairs.
             * Each pair is stored as its own attribute off of the multi-
             * value attribute named in: $this->scope . 'Prefs'
             *
             * Note that Prefs::setValue() can't be used here because of the
             * check for the "changeable" bit.  We want to override that
             * check when populating the $this->prefs hash from the LDAP
             * server.
             */

            /* Merge $this->scope prefs with shared prefs, if necessary. */
            if (strcmp($this->scope, 'horde') != 0) {
                if (isset($result[0][$field])) {
                    $prefs = array_merge($result[0][$field],
                                         $result[0]['hordeprefs']);
                } else {
                    if (isset($result[0]['hordeprefs'])) {
                        $prefs = $result[0]['hordeprefs'];
                    }
                }
            } else {
                if (isset($result[0][$field])) {
                    $prefs = $result[0][$field];
                }
            }

            foreach ($prefs as $prefstr) {

                /* If the string doesn't contain a colon delimeter, skip it. */
                if (substr_count($prefstr, ':') == 0) {
                    continue;
                }

                /* Split the string into its name:value components. */
                list($pref, $val) = split(':', $prefstr, 2);

                /* Retrieve this preference. */
                if (isset($this->prefs[$pref])) {
                    $this->prefs[$pref]['val'] = base64_decode($val);
                    $this->prefs[$pref]['default'] = false;
                }
            }
        } else {
            Horde::logMessage('No preferences were retrieved.', __FILE__, __LINE__);
            return;
        }

        /* Update the session cache. */
        $this->cacheUpdate();

        return true;
    }

    /**
     * Stores preferences to the LDAP server.
     *
     * @param array $prefs (optional) An array listing the preferences to be
     *                     stored.  If not specified, store all of the
     *                     preferences listed in the $prefs hash.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function store($prefs = array())
    {
        /*
         * If a list of preferences to store hasn't been provided in
         * $prefs, assume all preferences are desired.
         */
        if (count($prefs) == 0) {
            $prefs = Prefs::listAll();
        }
        if (!is_array($prefs)) {
            return (new PEAR_Error(_("No preferences are available.")));
        }

        /* Check for any "dirty" preferences. */
        $dirty_prefs = array();
        foreach ($prefs as $pref) {
            if (Prefs::isDirty($pref)) {
                $dirty_prefs[] = $pref;
            }
        }

        /*
         * If no "dirty" preferences were found, there's no need to update
         * the LDAP server.  Exit successfully.
         */
        if (count($dirty_prefs) == 0) {
            return true;
        }

        /* If we're not already connected, invoke the connect(). */
        if (!$this->_connected) {
            $this->_connect();
        }

        /*
         * Build a hash of the preferences and their values that need to be
         * stored in the LDAP server.  Because we have to update all of the
         * values of a multi-value entry wholesale, we can't just pick out
         * the dirty preferences; we must update everything.
         */
        $new_values = array();
        foreach($prefs as $pref) {
            $entry = $pref . ':' . base64_encode(Prefs::getValue($pref));
            $field = $this->getScope($pref) . 'Prefs';

            $new_values[$field][] = $entry;
        }

        /* Send the hash to the LDAP server. */
        $updated = true;
        if (ldap_mod_replace($this->connection, $this->dn, $new_values)) {
            foreach($dirty_prefs as $pref) {
                $this->setDirty($pref, false);
            }
        } else {
            Horde::logMessage(
                sprintf('Unable to modify preferences: [%d] %s',
                        ldap_errno($this->connection),
                        ldap_error($this->connection)),
                __FILE__, __LINE__);
            $updated = false;
        }

        /* Attempt to cache the preferences in the session. */
        $this->cacheUpdate();

        return $updated;
    }

    /**
     * Perform cleanup operations.
     *
     * @param boolean  $all    (optional) Cleanup all Horde preferences.
     */
    function cleanup($all = false)
    {
        /* Close the LDAP connection. */
        if (isset($this->connection)) {
            ldap_close($this->connection);
        }

        parent::cleanup($all);
    }

}

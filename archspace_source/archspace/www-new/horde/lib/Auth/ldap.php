<?php
/*
 * $Horde: horde/lib/Auth/ldap.php,v 1.5.2.6 2003/01/03 12:48:22 jan Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('ldap_connect', true,
    'Auth_ldap: Required LDAP functions were not found.');

/**
 * The Auth_ldap class provides an LDAP implementation of the Horde
 * authentication system.
 *
 * Required values for $params:
 *      'hostspec'      The hostname of the LDAP server.
 *      'basedn'        The base DN for the LDAP server.
 *      'uid'           The username search key.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth_ldap extends Auth {

    /** An array of capabilities, so that the driver can report which
        operations it supports and which it doesn't.
        @var array $capabilities */
    var $capabilities = array('add'         => true,
                              'update'      => false,
                              'remove'      => false,
                              'list'        => true,
                              'transparent' => false,
                              'loginscreen' => false);

    /**
     * Hash containing connection parameters.
     * @var array $params
     */
    var $params = array();


    /**
     * Constructs a new LDAP permissions object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_ldap($params = array())
    {
        if (isset($params['hostspec'])) {
            $this->params['hostspec'] = $params['hostspec'];
        }
        if (isset($params['basedn'])) {
            $this->params['basedn'] = $params['basedn'];
        }
        if (isset($params['uid'])) {
            $this->params['uid'] = $params['uid'];
        }
        if (isset($params['username'])) {
            $this->params['username'] = $params['username'];
        }
        if (isset($params['password'])) {
            $this->params['password'] = $params['password'];
        }
    }


    /**
     * Find out if the given set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  An array of login credentials.
     *
     * @return boolean  True on success or a PEAR_Error object on failure.
     */
    function authenticate($userID, $credentials)
    {
        if (Auth::checkAuth($userID)) {
            return true;
        }

        /* Ensure we've been provided with all of the necessary parameters. */
        if (!isset($this->params['hostspec'])) {
            Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in authentication configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['basedn'])) {
            Horde::fatal(new PEAR_Error(_("Required 'basedn' not specified in authentication configuration.")), __FILE__, __LINE__);
        }
        if (!isset($this->params['uid'])) {
            Horde::fatal(new PEAR_Error(_("Required 'uid' not specified in authentication configuration.")), __FILE__, __LINE__);
        }
        if (!isset($credentials['password'])) {
            Horde::fatal(new PEAR_Error(_("Required 'password' not specified in authentication configuration.")), __FILE__, __LINE__);
        }

        /* Connect to the LDAP server. */
        $ldap = @ldap_connect($this->params['hostspec']);
        if (!$ldap) {
            Horde::fatal(new PEAR_Error(_("Failed to connect to LDAP server.")), __FILE__, __LINE__);
        }

        /* Search for the user's full DN. */
        $search = @ldap_search($ldap, $this->params['basedn'],
            $this->params['uid'] . '=' . $userID, array($this->params['uid']));
        $result = @ldap_get_entries($ldap, $search);
        if (is_array($result) && (count($result) > 1)) {
            $dn = $result[0]['dn'];
        } else {
            return (new PEAR_Error(_("Empty result.")));
        }

        /* Attempt to bind to the LDAP server as the user. */
        $bind = @ldap_bind($ldap, $dn, $credentials['password']);
        if ($bind != false) {
            @ldap_close($ldap);
            Auth::setAuth($userID, $credentials);
            return true;
        }

        @ldap_close($ldap);
        return false;
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userID       The userID to add.
     * @param array  $credentials  The credentials to be set.
     */
    function addUser($userID, $credentials)
    {
        $ldap = @ldap_connect($this->params['hostspec']);

        $binddn = $this->params['uid'] . '=' . $this->params['username'] . ',' . $this->params['basedn'];
        $bind = @ldap_bind($ldap, $binddn, $this->params['password']);

        $dn = $this->params['uid'] . '=' . $userID . ',' . $this->params['basedn'];
        $entry['objectClass'][0 ] = 'top';
        $entry['objectClass'][1 ] = 'person';
        $entry['cn'] = $userID;
        $entry['sn'] = $userID;
        $entry['userpassword'] = $credentials['password'];
        @ldap_add($ldap, $dn, $entry);
        return AUTH_OK;
    }

    /**
     * List Users
     *
     * @return array of Users
     */
    function listUsers()
    {
        $ldap = @ldap_connect($this->params['hostspec']);

        $dn = $this->params['uid'] . '=' . $this->params['username'] . ',' . $this->params['basedn'];
        $bind = @ldap_bind($ldap, $dn, $this->params['password']);

        $search = ldap_search($ldap, $this->params['basedn'],
                              'objectClass=person');
        $entries = ldap_get_entries($ldap, $search);
        $userlist = array();
        for ($i = 0; $i < $entries['count']; $i++) {
            $userlist[$i] = $entries[$i]['cn'][0];
        }
        return $userlist;
    }

}
?>

<?php
/*
 * $Horde: horde/lib/Auth/mcal.php,v 1.4.2.5 2003/01/03 12:48:23 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('mcal_open', true, 'Auth_mcal: Required MCAL functions were not found.');

/**
 * The Auth_mcal class provides an MCAL implementation of the Horde
 * authentication system.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth_mcal extends Auth {

    /** An array of capabilities, so that the driver can report which
        operations it supports and which it doesn't.
        @var array $capabilities */
    var $capabilities = array('add'         => false,
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
     * Constructs a new MCAL authentication object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_mcal($params = array())
    {
        $this->params = $params;
    }


    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  An array of login credentials. For MCAL, this must contain a password entry.
     *
     * @return boolean Whether or not the credentials are valid.
     */
    function authenticate($userID, $credentials)
    {
        if (Auth::checkAuth($userID)) {
            return true;
        }

        $mcal = @mcal_open($this->params['calendar'], $userID, $credentials['password']);

        if ($mcal) {
            @mcal_close($mcal);
            Auth::setAuth($userID, $credentials);
            return true;
        }

        @mcal_close($mcal);
        return false;
    }

    /**
     * List all users in the system.
     *
     * @return mixed   The array of userIDs, or a PEAR_Error object on failure.
     */
    function listUsers()
    {
        $lines = @file('/etc/mpasswd');
        if (!$lines || !is_array($lines)) {
            return (new PEAR_Error('Unable to list users.'));
        }

        $users = array();
        foreach ($lines as $line) {
            $users[] = substr($line, 0, strpos($line, ':'));
        }

        return $users;
    }

}
?>

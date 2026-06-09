<?php
/*
 * $Horde: horde/lib/Auth/ftp.php,v 1.4.2.5 2003/01/03 12:48:23 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Max Kalika <max@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

Horde::functionCheck('ftp_connect', true,
    'Auth_ftp: Required FTP functions were not found.');

/**
 * The Auth_ftp class provides an FTP implementation of the Horde
 * authentication system.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Max Kalika <max@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth_ftp extends Auth {

    /**
     * Hash containing connection parameters.
     * @var array $params
     */
    var $params = array();


    /**
     * Constructs a new FTP permissions object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_ftp($params = array())
    {
        $this->setParams($params);
    }


    /**
     * Take a hash and build the connection string out of it.
     *
     * @param array $params   A hash specifying an FTP server and port.
     */
    function setParams($params)
    {
        if (!isset($params['hostspec']))
            $params['hostspec'] = 'localhost';

        if (!isset($params['port']))
            $params['port'] = '21';

        $this->params = $params;
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  An array of login credentials. For FTP, this must contain a password entry.
     *
     * @return boolean Whether or not the credentials are valid.
     */
    function authenticate($userID, $credentials)
    {
        if (Auth::checkAuth($userID)) {
            return true;
        }

        $ftp = @ftp_connect($this->params['hostspec'], $this->params['port']);

        if ($ftp && @ftp_login($ftp, $userID, $credentials['password'])) {
            @ftp_quit($ftp);
            Auth::setAuth($userID, $credentials);
            return true;
        }

        @ftp_quit($ftp);
        return false;
    }

}
?>

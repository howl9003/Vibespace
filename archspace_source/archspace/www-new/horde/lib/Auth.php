<?php

require_once dirname(__FILE__) . '/Secret.php';

/**
 * The Auth:: class provides a common abstracted interface into the
 * various backends for the Horde authentication system.
 *
 * $Horde: horde/lib/Auth.php,v 1.22.2.11 2003/02/18 00:33:00 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth {

    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array $capabilities
     */
    var $capabilities = array('add'         => false,
                              'update'      => false,
                              'remove'      => false,
                              'list'        => false,
                              'transparent' => false,
                              'loginscreen' => false);

    /**
     * Authentication error message.
     *
     * @var string $authError
     */
    var $authError = '';


    /**
     * Attempts to return a concrete Auth instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Auth subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Auth/ for the subclass
     *                       implementation named $driver[1].php.
     * @param array  $params (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Auth   The newly created concrete Auth instance, or false
     *                       on an error.
     */
    function &factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        $driver = strtolower(basename($driver));

        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Auth;
        }

        if (!empty($app)) {
            include_once $GLOBALS['registry']->getParam('fileroot', $app) . '/lib/Auth/' . $driver . '.php';
        } elseif (@file_exists(dirname(__FILE__) . '/Auth/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/Auth/' . $driver . '.php';
        } else {
            @include_once 'Horde/Auth/' . $driver . '.php';
        }
        $class = 'Auth_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Auth instance
     * based on $driver. It will only create a new instance if no Auth
     * instance with the same parameters currently exists.
     *
     * This should be used if multiple authentication sources (and,
     * thus, multiple Auth instances) are required.
     *
     * This method must be invoked as: $var = &Auth::singleton()
     *
     * @param string $driver The type of concrete Auth subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included.
     * @param array  $params (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Auth  The concrete Auth reference, or false on an error.
     */
    function &singleton($driver, $params = array())
    {
        static $instances;
        if (!isset($instances)) {
            $instances = array();
        }

        if (is_array($driver)) {
            $drivertag = implode(':', $driver);
        } else {
            $drivertag = $driver;
        }
        $signature = md5(strtolower($drivertag) . '][' . @implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Auth::factory($driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID       The userID to check
     * @param array  $credentials  The credentials to check
     *
     * @return boolean Whether or not the credentials are valid.
     */
    function authenticate($userID, $credentials)
    {
        return Auth::checkAuth($userID);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userID       The userID to add.
     * @param array  $credentials  The credentials to use.
     *
     * @return mixed        True on success or a PEAR_Error object on failure.
     */
    function addUser($userID, $credentials)
    {
        return new PEAR_Error('unsupported');
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID        The old userID.
     * @param string $newID        The new userID.
     * @param array  $credentials  The new credentials
     *
     * @return mixed        True on success or a PEAR_Error object on failure.
     */
    function updateUser($oldID, $newID, $credentials)
    {
        return new PEAR_Error('unsupported');
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userID  The userID to delete.
     *
     * @return mixed        True on success or a PEAR_Error object on failure.
     */
    function removeUser($userID)
    {
        return new PEAR_Error('unsupported');
    }

    /**
     * List all users in the system.
     *
     * @return mixed   The array of userIDs, or a PEAR_Error object on failure.
     */
    function listUsers()
    {
        return new PEAR_Error('unsupported');
    }

    /**
     * Automatic authentication: Find out if the client matches an
     * allowed IP block.
     *
     * @access public
     *
     * @return boolean  Whether or not the client is allowed.
     */
    function transparent()
    {
        return false;
    }

    /**
     * Checks if there is a session with valid auth information.
     *
     * @param string $userID          The userID we are expecting in the session.
     *
     * @return boolean                Whether or not the user is authenticated.
     */
    function checkAuth($userID)
    {
        return (Auth::getAuth() === $userID);
    }

    /**
     * Return the currently logged in user, if there is one.
     *
     * @return mixed $userID The userID of the current user, or false
     *                       if no user is logged in.
     */
    function getAuth()
    {
        if (isset($_SESSION['__auth'])) {
            if (!empty($_SESSION['__auth']['authenticated']) &&
                !empty($_SESSION['__auth']['userID'])) {
                return $_SESSION['__auth']['userID'];
            }
        }

        // Try transparent authentication now.
        global $conf;
        $auth = &Auth::singleton($conf['auth']['driver'], $conf['auth']['params']);
        if ($auth->hasCapability('transparent') &&
            $auth->transparent()) {
            return $_SESSION['__auth']['userID'];
        }

        return false;
    }

    /**
     * Return a credential of the currently logged in user, if there is one.
     *
     * @param          string $credential  The credential to retrieve.
     *
     * @return mixed   The requested credential, or false
     *                 if no user is logged in.
     */
    function getCredential($credential)
    {
        if (!empty($_SESSION['__auth']) &&
                   !empty($_SESSION['__auth']['authenticated'])) {
            $credentials = unserialize(Secret::read(Secret::getKey('auth'), $_SESSION['__auth']['credentials']));
        } else {
            return false;
        }

        if (isset($credentials[$credential])) {
            return $credentials[$credential];
        } else {
            return false;
        }
    }

    /**
     * Set a variable in the session saying that authorization has
     * succeeded, note which userID was authorized, and note when the
     * login took place.
     *
     * @param          string $userID       The userID who has been authorized.
     * @param          array  $credentials  The credentials of the user.
     */
    function setAuth($userID, $credentials)
    {
        if (!isset($_SESSION['__auth'])) {
            session_register('__auth');
        }
        $GLOBALS['__auth'] = &$_SESSION['__auth'];

        $credentials = Secret::write(Secret::getKey('auth'), serialize($credentials));
        $auth = array('authenticated' => true,
                      'userID' => $userID,
                      'credentials' => $credentials,
                      'timestamp' => time());

        $GLOBALS['__auth'] = $auth;
    }

    /**
     * Clear any authentication tokens in the current session.
     */
    function clearAuth()
    {
        if (isset($_SESSION['__auth'])) {
            $GLOBALS['__auth'] = &$_SESSION['__auth'];
            $GLOBALS['__auth'] = array();
            $GLOBALS['__auth']['authenticated'] = null;
        }
    }

    /**
     * Is the current user a guest user?
     *
     * @return boolean   Whether or not this is a guest user.
     */
    function isGuest()
    {
        return false;
    }

    /**
     * Is the current user an administrator?
     *
     * @return boolean   Whether or not this is an admin user.
     *
     * @since Horde 2.2
     */
    function isAdmin()
    {
        global $conf;
        if (@is_array($conf['auth']['admins'])) {
            if (Auth::getAuth() &&
                in_array(Auth::getAuth(), $conf['auth']['admins'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Query the current Auth object to find out if it supports the
     * given capability.
     *
     * @param string $capability  The capability to test for.
     * @return boolean            Whether or not the capability is supported.
     */
    function hasCapability($capability)
    {
        return !empty($this->capabilities[$capability]);
    }

    /**
     * Sets the error message for an invalid authentication.
     *
     * @access private
     *
     * @param string $error  The error message/reason for invalid
     *                       authentication.
     */
    function _setAuthError($error)
    {
        $this->authError = $error;
    }

    /**
     * Gets the last error message for an invalid authentication.
     *
     * @access public
     *
     * @return string  The error message/reason for invalid authentication.
     */
    function getAuthError()
    {
        return $this->authError;
    }

}

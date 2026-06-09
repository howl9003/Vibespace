<?php
/*
 * $Horde: horde/lib/Token.php,v 1.6.2.8 2003/04/28 19:59:08 jan Exp $
 *
 * Copyright 1999-2003 Max Kalika <max@horde.org>
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

// Return codes
/** @constant TOKEN_OK Operation succeeded. */
define('TOKEN_OK', 0);

/** @constant TOKEN_ERROR Operation failed. */
define('TOKEN_ERROR', -1);

/** @constant TOKEN_ERROR_PARAMS Bad or missing parameters ($params). */
define('TOKEN_ERROR_PARAMS', -2);

/** @constant TOKEN_ERROR_CONNECT Connection failure. */
define('TOKEN_ERROR_CONNECT', -3);

/** @constant TOKEN_ERROR_AUTH Authentication failure. */
define('TOKEN_ERROR_AUTH', -4);

/** @constant TOKEN_ERROR_EMPTY Empty retrieval result. */
define('TOKEN_ERROR_EMPTY', -5);


/**
 * The Token:: class provides a common abstracted interface into the
 * various token generation mediums. It also includes all of the
 * functions for retrieving, storing, and checking tokens.
 *
 * @author  Max Kalika <max@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.token
 */
class Token {

    /**
     * Attempts to return a concrete Token instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Token subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Token/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Token The newly created concrete Token instance, or
     *                      false an error.
     */
    function &factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        /* Return a base Token object if no driver is specified. */
        $driver = strtolower($driver);
        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Token($params);
        }

        if (!empty($app)) {
            include_once $GLOBALS['registry']->getParam('fileroot', $app) . '/lib/Token/' . $driver . '.php';
        } elseif (@file_exists(dirname(__FILE__) . '/Token/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/Token/' . $driver . '.php';
        } else {
            @include_once 'Horde/Token/' . $driver . '.php';
        }
        $class = 'Token_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Token instance
     * based on $driver. It will only create a new instance if no
     * Token instance with the same parameters currently exists.
     *
     * This should be used if multiple types of token generators (and,
     * thus, multiple Token instances) are required.
     *
     * This method must be invoked as: $var = &Token::singleton()
     *
     * @param mixed $driver  The type of concrete Token subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Token/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Token  The concrete Token reference, or false on an
     *                       error.
     *
     * @since Horde 2.2
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
        $signature = md5(strtolower($drivertag) . '][' . implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Token::factory($driver, $params);
        }

        return $instances[$signature];
    }

    function hexRemoteAddr()
    {
        $addr = explode('.', $_SERVER['REMOTE_ADDR']);
        return sprintf("%02x%02x%02x%02x", $addr[0], $addr[1], $addr[2], $addr[3]);
    }

    /**
     * Generates a connection id and returns it.
     *
     * @return string   The generated id string.
     */
    function generateID()
    {
        return md5(time() . '][' . Token::hexRemoteAddr());
    }

    /**
     * Checks if the given token has been previously used.  First
     * purges all expired tokens. Then retreives current tokens for
     * the given ip address. If the specified token was not found,
     * adds it.
     *
     * @param string $token  The value of the token to check.
     *
     * @return boolean       True if the token has not been used,
     *                       false otherwise.
     */
    function verify($token)
    {
        $this->purge();
        if ($this->exists($token)) {
            return false;
        }
        else {
            $this->add($token);
            return true;
        }
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded ($driver == 'none').
     */
    function exists()
    {
        return false;
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded ($driver == 'none').
     */
    function add()
    {
        return true;
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded ($driver == 'none').
     */
    function purge()
    {
        return true;
    }
}
?>

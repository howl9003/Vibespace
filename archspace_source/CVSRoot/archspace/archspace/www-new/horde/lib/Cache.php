<?php
/*
 * $Horde: horde/lib/Cache.php,v 1.2.2.7 2003/04/28 19:59:06 jan Exp $
 *
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/** @constant CACHE_IMS If Modified Since Cache request */
define('CACHE_IMS', -1);

/**
 * The Cache:: class provides a common abstracted interface into the
 * various caching backends.  It also provides functions for checking
 * in, retrieving, and flushing a cache.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.cache
 */
class Cache {

    /**
     * Attempts to return a concrete Cache instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Cache subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Cache/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array $params  (optional) A hash containing any additional
     *                       configuration or connection parameters a
     *                       subclass might need.
     *
     * @return object Cache   The newly created concrete Cache instance, or
     *                        false on error.
     */
    function &factory($driver, $params = array())
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        /* Return a base Cache object if no driver is specified. */
        $driver = strtolower(basename($driver));
        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Cache;
        }

        if (!empty($app)) {
            include_once $GLOBALS['registry']->getParam('fileroot', $app) . '/lib/Cache/' . $driver . '.php';
        } elseif (@file_exists(dirname(__FILE__) . '/Cache/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/Cache/' . $driver . '.php';
        } else {
            @include_once 'Horde/Cache/' . $driver . '.php';
        }
        $class = 'Cache_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Cache instance
     * based on $driver. It will only create a new instance if no
     * Cache instance with the same parameters currently exists.
     *
     * This should be used if multiple cache backends (and, thus,
     * multiple Cache instances) are required.
     *
     * This method must be invoked as: $var = &Cache::singleton()
     *
     * @param mixed $driver  The type of concrete Cache subclass to return.
     *                       This is based on the storage driver ($driver). The
     *                       code is dynamically included. If $driver is an array,
     *                       then we will look in $driver[0]/lib/Cache/ for
     *                       the subclass implementation named $driver[1].php.
     * @param array  $params (optional) A hash containing any additional
     *                       configuration or connection parameters a subclass
     *                       might need.
     *
     * @return object Cache  The concrete Cache reference, or false on an error.
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
        $signature = md5(strtolower($drivertag) . '][' . @implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Cache::factory($driver, $params);
        }

        return $instances[$signature];
    }

    /**
     * Attempts to store an object in the cache.
     *
     * @param string $oid  Object ID used as the caching key.
     * @param mixed $data  Data to store in the cache.
     * @return boolean     True on success, false on failure.
     */
    function store($oid, &$data)
    {
        return true;
    }

    /**
     * Attempts to retrieve a cached object.
     *
     * @param string $oid  Object ID to query.
     * @param enum $type   Expiration heuristic.
     *                      CACHE_IMS: Expire if supplied value is greater than
     *                      cached time
     * @param int $val     Value to supply for the expiration heuristic.
     * @return mixed       Cached data, or false if none was found.
     */
    function query($oid, $type = CACHE_IMS, $val = 0)
    {
        return false;
    }

}

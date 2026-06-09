<?php

require_once HORDE_BASE . '/lib/Horde.php';

// Preference constants (bitmasks)
/** @constant _PREF_LOCKED Preference is administratively locked. */
define('_PREF_LOCKED', 1);

/** @constant _PREF_SHARED Preference is shared amongst applications. */
define('_PREF_SHARED', 2);

/** @constant _PREF_DIRTY Preference value has been changed. */
define('_PREF_DIRTY', 4);

/** @constant _PREF_DEFAULT Preference value is the application default. */
define('_PREF_DEFAULT', 8);


/**
 * The Prefs:: class provides a common abstracted interface into the
 * various preferences storage mediums.  It also includes all of the
 * functions for retrieving, storing, and checking preference values.
 *
 * $_prefs[*pref name*] = array(
 *     'value'  => *Default value*,
 *     'locked' => *boolean*,
 *     'shared' => *boolean*,
 *     'type'   => 'checkbox'
 *                 'text'
 *                 'textarea'
 *                 'select'
 *                 'number'
 *                 'implicit'
 *                 'special'
 *                 'link' - There must be a field named either 'url'
 *                          (internal application link) or 'xurl'
 *                          (external application link) if this type is used.
 *                 'enum'
 *     'enum'   => TODO,
 *     'desc'   => _(*Description string*),
 *     'help'   => *Name of the entry in the XML help file*
 * );
 *
 * $Horde: horde/lib/Prefs.php,v 1.57.2.14 2003/05/09 14:49:31 chuck Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.prefs
 */
class Prefs {

    /**
     * Hash holding all of the user's preferences. Each preference is
     * itself a hash, so this will ultimately be multi-dimensional.
     * @var array $prefs
     */
    var $prefs = array();

    /**
     * String containing the name of this scope. This is used to
     * maintain the application scope between sets of preferences. By
     * default, all preferences belong to the "global" (Horde) scope.
     * @var string $scope
     */
    var $scope = 'horde';

    /**
     * String containing the current username. This indicates the
     * owner of the preferences.
     * @var string $user
     */
    var $user = '';

    /**
     * Boolean indicating whether preference caching should be used.
     * @var boolean $caching
     */
    var $caching = false;

    /**
     * Array of boolean flags indicating whether the default preferences
     * stored in the given file have been loaded.
     * @var boolean $defaults
     */
    var $defaults = array();

    /**
     * Attempts to return a concrete Prefs instance based on $driver.
     *
     * @param mixed $driver     The type of concrete Prefs subclass to return.
     *                          This is based on the storage driver ($driver). The
     *                          code is dynamically included. If $driver is an array,
     *                          then we will look in $driver[0]/lib/Prefs/ for
     *                          the subclass implementation named $driver[1].php.
     * @param string $scope     The scope for this set of preferences.
     * @param string $user      (optional) The name of the user who owns this
     *                          set of preferences.
     * @param string $password  (optional) The password associated with $user.
     * @param array $params     (optional) A hash containing any additional
     *                          configuration or connection parameters a
     *                          subclass might need.
     * @param boolean $caching  (optional) Should caching be used?
     *
     * @return object Prefs     The newly created concrete Prefs instance, or
     *                          false on error.
     */
    function &factory($driver, $scope = 'horde', $user = '', $password = '',
                      $params = array(), $caching = true)
    {
        if (is_array($driver)) {
            list($app, $driver) = $driver;
        }

        // Attempt to register (cache) the $prefs hash in session
        // storage.
        if ($caching) {
            if (!session_is_registered('prefs_cache')) {
                global $prefs_cache;

                $prefs_cache = array();
                $prefs_cache['_filled'] = array();

                $_SESSION['prefs_cache'] = &$prefs_cache;

                /* If we fail to register the cache, continue on without it. */
                if (!@session_register('prefs_cache')) {
                    unset($prefs_cache);
                    $caching = false;
                }
            } elseif (!isset($GLOBALS['prefs_cache'])) {
                $GLOBALS['prefs_cache'] = &$_SESSION['prefs_cache'];
            }
        }

        /* Return a base Prefs object if no driver is specified. */
        $driver = strtolower(basename($driver));
        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Prefs;
        }

        /* If $params['user_hook'] is defined, use it to retrieve the
         * value to use for the username ($this->user). Otherwise,
         * just use the value passed in the $user parameter. */
        if (!empty($params['user_hook']) &&
            function_exists($params['user_hook'])) {
            $user = call_user_func($params['user_hook'], $user);
        }

        if (!empty($app)) {
            include_once $GLOBALS['registry']->getParam('fileroot', $app) . '/lib/Prefs/' . $driver . '.php';
        } elseif (@file_exists(dirname(__FILE__) . '/Prefs/' . $driver . '.php')) {
            include_once dirname(__FILE__) . '/Prefs/' . $driver . '.php';
        } else {
            @include_once 'Horde/Prefs/' . $driver . '.php';
        }
        $class = 'Prefs_' . $driver;
        if (class_exists($class)) {
            return new $class($user, $password, $scope, $params, $caching);
        } else {
            return PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }
    }

    /**
     * Attempts to return a reference to a concrete Prefs instance based on
     * $driver. It will only create a new instance if no Prefs instance
     * with the same parameters currently exists.
     *
     * This should be used if multiple preference sources (and, thus,
     * multiple Prefs instances) are required.
     *
     * This method must be invoked as: $var = &Prefs::singleton()
     *
     * @param mixed $driver     The type of concrete Prefs subclass to return.
     *                          This is based on the storage driver ($driver). The
     *                          code is dynamically included. If $driver is an array,
     *                          then we will look in $driver[0]/lib/Prefs/ for
     *                          the subclass implementation named $driver[1].php.
     * @param string $scope     The scope for this set of preferences.
     * @param string $user      (optional) The name of the user who owns this
     *                          set of preferences.
     * @param string $password  (optional) The password associated with $user.
     * @param array $params     (optional) A hash containing any additional
     *                          configuration or connection parameters a
     *                          subclass might need.
     * @param boolean $caching  (optional) Should caching be used?
     *
     * @return object Prefs     The concrete Prefs reference, or false on an
     *                          error.
     */
    function &singleton($driver, $scope = 'horde', $user = '', $password = '',
                        $params = array(), $caching = true)
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
        $signature = md5(strtolower($drivertag) . '][' . $user . '][' .
                         implode('][', $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Prefs::factory($driver, $scope, $user,
                                                     $password, $params, $caching);
        }

        return $instances[$signature];
    }

    /**
     * Populates the $prefs hash with new entries and externally defined
     * default values.
     *
     * @param string $filename  The filename of the file from which to read the
     *                          list of preferences and their default values.
     */
    function setDefaults($filename)
    {
        /* Ensure that the defaults from this file are only read once. */
        if (!empty($this->defaults[$filename])) {
            return;
        }

        if (!@is_readable($filename)) return;

        /* Read the configuration file. The $_prefs array, which will
         * be in local scope, is assumed to hold the default
         * values.
         */
        include $filename;

        foreach ($_prefs as $pref => $pvals) {
            if (isset($pvals['value']) && isset($pvals['locked']) &&
                isset($pvals['shared']) && ($pvals['type'] != 'link') &&
                ($pvals['type'] != 'special')) {
                $pref = str_replace('.', '_', $pref);
                $mask = 0;
                if ($pvals['locked']) {
                    $mask |= _PREF_LOCKED;
                }
                if ($pvals['shared']) {
                    $mask |= _PREF_SHARED;
                }
                $mask &= ~_PREF_DIRTY;
                $mask |= _PREF_DEFAULT;

                $this->add($pref, $pvals['value'], $mask);
            }
        }

        /* Indicate that the defaults from this file have been loaded. */
        $this->defaults[$filename] = true;

        /* If the cache is already filled, don't overwrite it with the
           defaults we've just read in. */
        if (session_is_registered('prefs_cache')) {
            global $prefs_cache;
            if (!empty($prefs_cache['_filled'][$this->scope])) {
                return;
            }
        }

        /* Update the preferences cache with the defaults. */
        $this->cacheUpdate();
    }

    /**
     * Updates the session-based preferences cache (if available) with the
     * current set of preferences.
     */
    function cacheUpdate()
    {
        /* Return immediately if caching is disabled. */
        if ($this->caching == false) return;

        if (session_is_registered('prefs_cache')) {
            global $prefs_cache;

            /* Place each preference in the cache according to itsscope. */
            foreach ($this->prefs as $name => $pref) {
                $prefs_cache[$this->getScope($name)][$name] = $pref;
            }
        }
    }

    /**
     * Tries to find the requested preferences in the cache.  If they exist,
     * update the $prefs hash with the cached values.
     *
     * @param array $prefs      The preferences to find.
     *
     * @return boolean          True on success, false on failure.
     */
    function cacheLookup($prefs = array())
    {
        /* Return immediately if caching is disabled. */
        if ($this->caching == false) return false;

        if (session_is_registered('prefs_cache')) {
            global $prefs_cache;

            /* If we haven't filled the cache yet, force a retrieval. */
            if (empty($prefs_cache['_filled'][$this->scope])) {
                $prefs_cache['_filled'][$this->scope] = true;
                return false;
            }

            /* Restore the global preferences. */
            if (isset($prefs_cache['horde'])) {
                foreach ($prefs_cache['horde'] as $name => $pref) {
                    $this->prefs[$name] = $pref;
                }
            }

            /* Restore the application-specifiec preferences. */
            if (isset($prefs_cache[$this->scope])) {
                foreach ($prefs_cache[$this->scope] as $name => $pref) {
                    $this->prefs[$name] = $pref;
                }
            }

            /* Return success if all of the requested preferences are cached. */
            return (array_intersect(array_keys($this->prefs), $prefs) == $prefs);
        }

        return false;
    }

    /**
     * Adds a new preference entry to the $prefs hash.
     *
     * @param string  $pref    The name of the preference to add.
     * @param string  $val     (optional) The initial value of the preference.
     * @param integer $mask    (optional) The initial bitmask of the preference.
     */
    function add($pref, $val = '', $mask = 0)
    {
        if (is_array($this->prefs)) {
            $this->prefs[$pref] = array('val' => $val, 'mask' => $mask);
        }
    }

    /**
     * Removes a preference entry from the $prefs hash.
     *
     * @param string $pref     The name of the preference to remove.
     */
    function remove($pref)
    {
        if (is_array($this->prefs)) {
            unset($this->prefs[$pref]);
        }
    }

    /**
     * Returns an array containing the names of all of the preferences
     * stored in the $prefs hash.
     *
     * @return array            An array containiing the names of the
     *                          preferences in the $prefs hash.
     */
    function listAll()
    {
        return array_keys($this->prefs);
    }

    /**
     * Sets the given preferences to the specific value, if the preference
     * is modifiable.
     *
     * @param string $pref      The name of the preference to modify.
     * @param string $val       The new value for this preference.
     *
     * @return boolean  True if the value was successfully set, false on a
     *                  failure.
     */
    function setValue($pref, $val)
    {
        /* Exit early if this preference is locked or doesn't exist. */
        if (!isset($this->prefs[$pref]) || $this->isLocked($pref)) {
            return false;
        }

        /*
         * If the preference's value is already equal to $val, don't bother
         * changing it.  Changing it would set the "dirty" bit, causing an
         * unnecessary update later on in the storage routine.
         */
        if ($this->prefs[$pref]['val'] == $val && !$this->isDefault($pref)) {
            return true;
        }

        /*
         * Assign the new value, unset the "default" bit, and set the
         * "dirty" bit.
         */
        $this->prefs[$pref]['val'] = $val;
        $this->setDefault($pref, false);
        $this->setDirty($pref, true);

        return true;
    }

    /**
     * Returns the value of the requested preference.
     *
     * @param string $pref      The name of the preference to retrieve.
     *
     * @return string           The value of the preference.
     */
    function getValue($pref)
    {
        if (isset($this->prefs[$pref]['val'])) {
            return $this->prefs[$pref]['val'];
        } else {
            return null;
        }
    }

    /**
     * Modifies the "locked" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "locked" bit.
     */
    function setLocked($pref, $bool)
    {
        if (isset($this->prefs[$pref])) {
            if ($bool != $this->isLocked($pref)) {
                if ($bool) {
                    $this->prefs[$pref]['mask'] |= _PREF_LOCKED;
                } else {
                    $this->prefs[$pref]['mask'] &= ~_PREF_LOCKED;
                }
            }
        }
    }

    /**
     * Returns the state of the "locked" bit for the given preference.
     *
     * @param string $pref      The name of the preference the check.
     *
     * @return boolean          The boolean state of $pref's "locked" bit.
     */
    function isLocked($pref)
    {
        if (isset($this->prefs[$pref]['mask'])) {
            return ($this->prefs[$pref]['mask'] & _PREF_LOCKED);
        }

        return false;
    }

    /**
     * Modifies the "shared" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "shared" bit.
     */
    function setShared($pref, $bool)
    {
        if (isset($this->prefs[$pref])) {
            if ($bool != $this->isShared($pref)) {
                if ($bool) {
                    $this->prefs[$pref]['mask'] |= _PREF_SHARED;
                } else {
                    $this->prefs[$pref]['mask'] &= ~_PREF_SHARED;
                }
            }
        }
    }

    /**
     * Returns the state of the "shared" bit for the given preference.
     *
     * @param string $pref      The name of the preference the check.
     *
     * @return boolean          The boolean state of $pref's "shared" bit.
     */
    function isShared($pref)
    {
        if (isset($this->prefs[$pref]['mask'])) {
            return ($this->prefs[$pref]['mask'] & _PREF_SHARED);
        }

        return false;
    }

    /**
     * Returns the scope of the given preference.
     *
     * @param string $pref      The name of the preference to examine.
     *
     * @return string           The scope of the $pref.
     */
    function getScope($pref)
    {
        if ($this->isShared($pref)) {
            return 'horde';
        } else {
            return $this->scope;
        }
    }

    /**
     * Modifies the "dirty" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "dirty" bit.
     */
    function setDirty($pref, $bool)
    {
        if (isset($this->prefs[$pref])) {
            if ($bool != $this->isDirty($pref)) {
                if ($bool) {
                    $this->prefs[$pref]['mask'] |=  _PREF_DIRTY;
                } else {
                    $this->prefs[$pref]['mask'] &= ~_PREF_DIRTY;
                }
            }
        }
    }

    /**
     * Returns the state of the "dirty" bit for the given preference.
     *
     * @param string $pref      The name of the preference the check.
     *
     * @return boolean          The boolean state of $pref's "dirty" bit.
     */
    function isDirty($pref)
    {
        if (isset($this->prefs[$pref]['mask'])) {
            return ($this->prefs[$pref]['mask'] & _PREF_DIRTY);
        }

        return false;
    }

    /**
     * Determines whether the current preference is empty.
     *
     * @param string $pref      The name of the preference the check.
     *
     * @return boolean          True if the preference is empty.
     */
    function isEmpty($pref)
    {
        return empty($this->prefs[$pref]['val']);
    }

    /**
     * Modifies the "default" bit for the given preference.
     *
     * @param string $pref      The name of the preference to modify.
     * @param boolean $bool     The new boolean value for the "default" bit.
     *
     * @since Horde 2.1
     */
    function setDefault($pref, $bool)
    {
        if (isset($this->prefs[$pref])) {
            if ($bool != $this->isDefault($pref)) {
                if ($bool) {
                    $this->prefs[$pref]['mask'] |=  _PREF_DEFAULT;
                } else {
                    $this->prefs[$pref]['mask'] &= ~_PREF_DEFAULT;
                }
            }
        }
    }

    /**
     * Determines if the current preference value is the default
     * value from prefs.php or a user defined value
     *
     * @param string $pref      The name of the preference to check.
     *
     * @return boolean          True if the preference is the application
     *                          default value.
     *
     * @since Horde 2.1
     */
    function isDefault($pref)
    {
        if (isset($this->prefs[$pref]['mask'])) {
            return ($this->prefs[$pref]['mask'] & _PREF_DEFAULT);
        }

        return false;
    }

    /**
     * Retrieve a value or set of values for a specified user.
     *
     * @access public
     * @param          string $user     The user to retrieve prefs for.
     * @param          mixed  $values   A string or array with the preferences
     *                                  to retrieve.
     * @param optional string $scope    The preference scope to look in.
     *                                  Defaults to 'horde'.
     *
     * @return mixed    If a single value was requested, the value for that
     *                  preference.  Otherwise, a hash, indexed by pref names,
     *                  with the requested values.
     *
     * @since Horde 2.2
     */
    function getPref($user, $retrieve, $scope = 'horde')
    {
        if (is_array($retrieve)) {
            return array();
        } else {
            return null;
        }
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded ($driver == 'none').
     *
     * @abstract
     */
    function retrieve()
    {
        return true;
    }

    /**
     * This is basically an abstract method that should be overridden by a
     * subclass implementation. It's here to retain code integrity in the
     * case that no subclass is loaded ($driver == 'none').
     *
     * @abstract
     */
    function store()
    {
        return true;
    }

    /**
     * This function provides common cleanup functions for all of the driver
     * implementations.
     *
     * @param boolean  $all    (optional) Clean up all Horde preferences.
     */
    function cleanup($all = false)
    {
        /* Remove this scope from the preferences cache. */
        global $prefs_cache;
        $prefs_cache = array();
        if (isset($prefs_cache[$this->scope])) {
            unset($prefs_cache[$this->scope]);
        }
        if (isset($prefs_cache['_filled'][$this->scope])) {
            unset($prefs_cache['_filled'][$this->scope]);
        }

        /* Perform a Horde-wide cleanup? */
        if ($all) {
            /* Destroy the contents of the preferences hash. */
            $this->prefs = array();

            /* Destroy the contents of the preferences cache. */
            if (session_is_registered('prefs_cache')) {
                global $prefs_cache;
                $prefs_cache = array();
                $prefs_cache['_filled'] = array();
                session_unregister('prefs_cache');
            }
        }
    }

}

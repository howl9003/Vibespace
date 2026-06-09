<?php
/*
 * $Horde: horde/lib/Registry.php,v 1.59.2.25 2003/08/18 15:57:26 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 * Copyright 1999-2003 Anil Madhavapeddy <anil@recoil.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

require_once 'PEAR.php';
require_once dirname(__FILE__) . '/Horde.php';

/* Turn PHP stuff off that can really screw things up. */
ini_set('magic_quotes_sybase', 0);
ini_set('magic_quotes_runtime', 0);

/**
 * The Registry:: class provides a set of methods for communication
 * between Horde applications and keeping track of application
 * configuration information.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde
 */
class Registry {

    /**
     * Hash representing the actual registry structure.
     * @var array $registry
     */
    var $registry = array();

    /**
     * Hash storing all of the known services and callbacks.
     * @var array $services
     */
    var $services = array();

    /**
     * Hash storing information on each registry-aware application.
     * @var array $applications
     */
    var $applications = array();

    /**
     * Stack of in-use applications.
     * @var array $appStack
     */
    var $appStack = array();

    /**
     * Cache of $prefs objects
     * @var array $prefsCache
     */
    var $prefsCache = array();

    /**
     * Cache of application configurations.
     * @var array $confCache
     */
    var $confCache = array();

    /**
     * List of implemented services.
     * @var array $used
     */
    var $used;

    /**
     * Returns a reference to the global Registry object, only
     * creating it if it doesn't already exist.
     *
     * This method must be invoked as: $registry = &Registry::singleton()
     *
     * @return object The Horde Registry instance.
     */
    function &singleton()
    {
        static $registry;

        if (!isset($registry)) {
            $registry = new Registry();
        }

        return $registry;
    }

    /**
     * Create a new registry instance. Should never be called except
     * by &Registry::singleton().
     *
     * @access private
     */
    function Registry()
    {
        $base = dirname(__FILE__) . '/..';

        // read in and evaluate the registry file
        include_once $base . '/config/registry.php';

        /* Initialize the localization routines and variables  */
        include_once $base . '/lib/Lang.php';
        Lang::setLang();
        Lang::setTextdomain('horde', $base . '/locale', Lang::getCharset());

        /* Set the default cookie_path to the Horde webroot */
        if (!isset($this->applications['horde']['cookie_path'])) {
            $this->applications['horde']['cookie_path'] = $this->applications['horde']['webroot'];
        }

        /* Make sure the cookie path isn't empty - use '/' instead. */
        if (empty($this->applications['horde']['cookie_path'])) {
            $this->applications['horde']['cookie_path'] = '/';
        }

        /* Set other common defaults like templates and graphics */
        foreach (array_keys($this->applications) as $appName) {
            // Simply discard applications marked as inactive.
            if (array_key_exists('active', $this->applications[$appName]) &&
                !$this->applications[$appName]['active']) {
                unset($this->applications[$appName]);
                continue;
            }

            $app = &$this->applications[$appName];
            if (!isset($app['templates'])) {
                $app['templates'] = $app['fileroot'] . '/templates';
            }
            if (!isset($app['graphics'])) {
                $app['graphics'] = $app['webroot'] . '/graphics';
            }
        }
    }

    /**
     * Return a list of the installed and registered applications.  If
     * no applications are defined return a null array.
     *
     * @param array $filter  (optional) An array of the statuses that
     *                       should be returned. Defaults to non-hidden.
     *
     * @return array List of apps registered with Horde
     *
     * @since Horde 2.2
     */
    function listApps($filter = null)
    {
        $apps = array();
        if (is_null($filter)) {
            $filter = array('notoolbar', 'active');
        }
        foreach ($this->applications as $app => $params) {
            if (in_array($params['status'], $filter)) {
                $apps[] = $app;
            }
        }

        return $apps;
    }

    /**
     * Determine if guests should be allowed access to the specified
     * application. If no application is specified, check the current
     * application.
     *
     * @param string $app (optional) The application to check
     *
     * @return boolean Should guests be allowed?
     */
    function allowGuests($app = null)
    {
        if (!isset($app)) $app = $this->getApp();

        return (!empty($this->applications[$app]['allow_guests']));
    }

    /**
     * Determine if a method has been registered with the registry.
     *
     * @param string $method The full name of the method to check for.
     *
     * @return boolean Whether or not the method is registered.
     */
    function hasMethod($method, $app = null)
    {
        list($type, $subtype) = explode('/', $method);
        if (is_null($app)) {
            return !empty($this->registry[$type][$subtype]);
        } else {
            return isset($this->services[$app][$type][$subtype]);
        }
    }

    /**
     * Returns the handler for the given method.
     *
     * @param string $method The full name of the method to check for.
     *
     * @return string The handler for the method.
     */
    function getMethod($method)
    {
        list($type, $subtype) = explode('/', $method);
        return !empty($this->registry[$type][$subtype]) ? $this->registry[$type][$subtype] : null;
    }

    /**
     * Return the hook corresponding to the default package that
     * provides the functionality requested by the $method
     * parameter. $method is a string consisting of
     * "packagetype/methodname".
     *
     * @param string $method       The method to call.
     * @param optional array $args Arguments to the method.
     */
    function call($method, $args = array())
    {
        if (!$this->hasMethod($method)) {
            // no callback defined
            return PEAR::raiseError('The method "' . $method . '" is not defined in the Horde registry,');
        }

        list($type, $subtype) = explode('/', $method);
        return $this->callByPackage($this->registry[$type][$subtype], $method, $args);
    }

    /**
     * Echo the output of call()
     *
     * @param string $method       The method to call.
     * @param optional array $args Arguments to the method.
     */
    function pcall($method, $args = array())
    {
        echo $this->call($method, $args);
    }

    /**
     * Output the hook corresponding to the specific package named.
     *
     * @param string $package      The desired package.
     * @param string $method       The method to call.
     * @param optional array $args Arguments to the method.
     */
    function callByPackage($package, $method, $args = array())
    {
        list($type, $subtype) = explode('/', $method);
        if (empty($this->services[$package][$type][$subtype]['file']) ||
            empty($this->services[$package][$type][$subtype]['function'])) {
            // Either the file or the function definition is missing
            return PEAR::raiseError('The method "' . $method . '" is not defined in the Horde registry,');
        }

        $file = $this->services[$package][$type][$subtype]['file'];
        $function = $this->services[$package][$type][$subtype]['function'];

        // Make sure we've got a full path to the file.
        if (isset($this->applications[$package]['fileroot'])) {
            $file = str_replace('%application%', $this->applications[$package]['fileroot'], $file);
        }

        if (!@is_readable($file)) {
            return PEAR::raiseError('The file defining the ' . $method . ' call (' . $file . ') is not readable.');
        }

        // Switch application contexts, if necessary, now, before
        // including any files which might do it for us.
        $pushed = $this->pushApp($package);

        include_once $file;
        if (!function_exists($function)) {
            return PEAR::raiseError('The function implementing ' . $method . ' (' . $function . ') is not defined in ' . $file . '.');
        }

        $res = call_user_func_array($function, $args);

        // If we changed application context in the course of this
        // call, undo that change now.
        if ($pushed) {
            $this->popApp();
        }

        return $res;
    }

    /**
     * Echo the output of callByPackage()
     *
     * @param string $package      The desired package.
     * @param string $method       The method to call.
     * @param optional array $args Arguments to the method.
     */
    function pcallByPackage($package, $method, $args = array())
    {
        echo $this->callByPackage($package, $method, $args);
    }

    /**
     * Return the hook corresponding to the default package that
     * provides the functionality requested by the $method
     * parameter. $method is a string consisting of
     * "packagetype/methodname".
     *
     * @param string $method        The method to link to.
     * @param optional array $args  Arguments to the method.
     * @param optional mixed $extra Extra, non-standard arguments to the method.
     */
    function link($method, $args = array(), $extra = '')
    {
        if (!$this->hasMethod($method)) {
            // no link defined
            return null;
        }

        list($type, $subtype) = explode('/', $method);
        return $this->linkByPackage($this->registry[$type][$subtype], $method, $args, $extra);
    }

    /**
     * Echo the output of link()
     *
     * @param string $method        The method to link to.
     * @param optional array $args  Arguments to the method.
     * @param optional mixed $extra Extra, non-standard arguments to the method.
     */
    function plink($method, $args = array(), $extra = '')
    {
        echo $this->link($method, $args, $extra);
    }

    /**
     * Output the hook corresponding to the specific package named.
     *
     * @param string $package the desired package.
     * @param string $method        The method to link to.
     * @param optional array $args  Arguments to the method.
     * @param optional mixed $extra Extra, non-standard arguments to the method.
     */
    function linkByPackage($package, $method, $args = array(), $extra = '')
    {
        list($type, $subtype) = explode('/', $method);
        if (empty($this->services[$package][$type][$subtype]['link'])) {
            // no link defined
            return null;
        }

        // mark this service as used so that we can output support
        // functions at the end of the page
        $this->used[$method] = true;

        $link = $this->services[$package][$type][$subtype]['link'];

        // fill in html-encoded arguments
        foreach ($args as $key => $val) {
            $link = str_replace('%' . $key . '%', htmlentities($val), $link);
        }
        if (isset($this->applications[$package]['webroot'])) {
            $link = str_replace('%application%', $this->applications[$package]['webroot'], $link);
        }

        // repace urlencoded arguments that haven't been specified with an
        // empty string (this is where the default would be substituted in
        // a stricter registry).
        $link = preg_replace("|%.+%|U", '', $link);

        // fill in urlencoded arguments
        foreach ($args as $key => $val) {
            $link = str_replace('|' . strtolower($key) . '|', urlencode($val), $link);
        }

        // append any extra, non-standard arguments
        if (is_array($extra)) {
            $extra_args = '';
            foreach ($extra as $key => $val) {
                $key = urlencode($key);
                $val = urlencode($val);
                $extra_args .= "&$key=$val";
            }
        } else {
            $extra_args = $extra;
        }
        $link = str_replace('|extra|', $extra_args, $link);

        // repace html-encoded arguments that haven't been specified with
        // an empty string (this is where the default would be substituted
        // in a stricter registry).
        $link = preg_replace('|\|.+\||U', '', $link);

        return htmlspecialchars($link);
    }

    /**
     * Echo the output of linkByPackage()
     *
     * @param string $package the desired package.
     * @param string $method        The method to link to.
     * @param optional array $args  Arguments to the method.
     * @param optional mixed $extra Extra, non-standard arguments to the method.
     */
    function plinkByPackage($package, $method, $args = array(), $extra = '')
    {
        echo $this->linkByPackage($package, $method, $args, $extra);
    }

    /**
     * Go through the list of methods that have been used, and
     * evaluate/include any support files needed.
     */
    function shutdown()
    {
        /* Don't make config files global $registry themselves. */
        global $registry;

        if (!empty($this->used) && is_array($this->used)) {
            foreach ($this->used as $key => $val) {
                list($type, $subtype) = explode('/', $key);
                $application = $this->registry[$type][$subtype];
                if (!empty($this->services[$application][$type][$subtype]['includeFile'])) {
                    include_once $this->applicationFilePath($this->services[$application][$type][$subtype]['includeFile'], $application);
                }
            }
        }
    }

    /**
     * Include dependency files for a method immediately, instead of
     * putting inclusion off until shutdown.
     *
     * @param string $application  The application providing the method.
     * @param string $method       The method to perform includes for.
     */
    function includeFiles($application, $method)
    {
        list($type, $subtype) = explode('/', $method);
        if (!empty($this->services[$application][$type][$subtype]['includeFile'])) {
            include_once $this->applicationFilePath($this->services[$application][$type][$subtype]['includeFile'], $application);
        }
    }

    /**
     * Replace any %application% strings with the filesystem path to
     * the application.
     */
    function applicationFilePath($path, $app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }
        if (!isset($this->applications[$app])) {
            Horde::fatal(new PEAR_Error(sprintf(_("'%s' is not configured in the Horde Registry."), $app)), __FILE__, __LINE__);
        }
        return str_replace('%application%', $this->applications[$app]['fileroot'], $path);
    }

    /**
     * Replace any %application% strings with the web path to the
     * application.
     */
    function applicationWebPath($path, $app = null)
    {
        if (!isset($app)) {
            $app = $this->getApp();
        }

        return str_replace('%application%', $this->applications[$app]['webroot'], $path);
    }

    /**
     * Set the current application, adding it to the top of the Horde
     * application stack. If this is the first application to be
     * pushed, retrieve session information as well.
     *
     * pushApp() also reads the application's configuration file and
     * sets up its global $conf hash.
     *
     * @param string $app The name of the application to push.
     * @return boolean Whether or not the appStack was modified.
     */
    function pushApp($app)
    {
        static $session_started;

        if ($app != $this->getApp()) {
            array_push($this->appStack, $app);

            /* Import Horde's configuration values. */
            $this->importConfig('horde');

            /* Start the session if this is the first application to
               be loaded. */
            if (!isset($session_started)) {
                $session_started = true;
                Horde::setupSessionHandler();
                @session_start();
            }

            /* Load preferences after the configuration has been
               loaded and the session started, to make sure we have
               all the information we need. */
            $this->loadPrefs($app);

            /* Reset the language in case that there is a different one
               selected in the preferences. */
            include_once HORDE_BASE . '/lib/Lang.php';
            $language = '';
            if (isset($this->prefsCache[$app]) && isset($this->prefsCache[$app]->prefs['language'])) {
                $language = $this->prefsCache[$app]->getValue('language');
            }
            Lang::setLang($language);
            Lang::setTextdomain($app, $this->applications[$app]['fileroot'] . '/locale', Lang::getCharset());

            /* Import this application's configuration values. */
            $this->importConfig($app);

            return true;
        }

        return false;
    }

    /**
     * Remove the current app from the application stack, setting the
     * current app to whichever app was current before this one took
     * over.
     *
     * @return string The name of the application that was popped.
     */
    function popApp()
    {
        /* Pop the current application off of the stack. */
        $previous = array_pop($this->appStack);

        /* Import the new active application's configuration values
           and set the gettext domain and the preferred language. */
        $current = $this->getApp();
        if ($current) {
            $this->importConfig($current);
            $this->loadPrefs($current);
            include_once HORDE_BASE . '/lib/Lang.php';
            $language = $GLOBALS['prefs']->getValue('language');
            if (isset($language)) {
                Lang::setLang($language);
            }
            Lang::setTextdomain($current, $this->applications[$current]['fileroot'] . '/locale', Lang::getCharset());
        }

        return $previous;
    }

    /**
     * Return the current application - the app at the top of the
     * application stack.
     *
     * @return string The current application.
     */
    function getApp()
    {
        if (count($this->appStack) > 0) {
            return $this->appStack[count($this->appStack) - 1];
        } else {
            return null;
        }
    }

    /**
     * Reads the configuration values for the given application and
     * imports them into the global $conf variable.
     *
     * @param string $app       The name of the application.
     */
    function importConfig($app)
    {
        /* Don't make config files global $registry themselves. */
        global $registry;

        /* Cache config values so that we don't re-read files on every
           popApp() call. */
        if (!isset($this->confCache[$app])) {
            if (!isset($this->confCache['horde'])) {
                $conf = array();
                include HORDE_BASE . '/config/horde.php';

                // Initial Horde-wide settings
                // Set the error reporting level in accordance with the config settings.
                error_reporting($conf['debug_level']);

                // Set the maximum execution time in accordance with the config settings.
                @set_time_limit($conf['max_exec_time']);

                // set the umask according to config settings
                if (isset($conf['umask'])) {
                    umask($conf['umask']);
                }
            } else {
                $conf = $this->confCache['horde'];
            }

            if ($app !== 'horde') {
                @include $this->applications[$app]['fileroot'] . '/config/conf.php';
            }

            $this->confCache[$app] = &$conf;
        }

        $GLOBALS['conf'] = &$this->confCache[$app];
    }

    /**
     * Loads the preferences for the current user for the current
     * application and imports them into the global $prefs variable.
     *
     * @param string $app       The name of the application.
     */
    function loadPrefs($app = null)
    {
        /* If there is no logged in user, return an empty Prefs::
         * object with just default preferences. */
        include_once HORDE_BASE . '/lib/Auth.php';
        include_once HORDE_BASE . '/lib/Prefs.php';

        if (!isset($app)) {
            $app = $this->getApp();
        }

        /* Don't load an actual prefs driver or cache guest
         * preferences. */
        if (!Auth::getAuth()) {
            $prefs = &Prefs::factory('none', $app, '', '', array());
            $prefs->setDefaults($this->getParam('fileroot', 'horde') . '/config/prefs.php');
            $prefs->setDefaults($this->getParam('fileroot') . '/config/prefs.php');

            $GLOBALS['prefs'] = &$prefs;
            return;
        }

        /* Cache prefs objects so that we don't re-load them on every
           popApp() call. */
        if (!isset($this->prefsCache[$app])) {
            global $conf;
            $prefs = &Prefs::factory($conf['prefs']['driver'], $app,
                                     Auth::getAuth(), Auth::getCredential('password'),
                                     $conf['prefs']['params']);
            $prefs->setDefaults($this->getParam('fileroot', 'horde') . '/config/prefs.php');
            $prefs->setDefaults($this->getParam('fileroot') . '/config/prefs.php');
            $prefs->retrieve();

            $this->prefsCache[$app] = &$prefs;
        }

        $GLOBALS['prefs'] = &$this->prefsCache[$app];
    }

    /**
     * Query the name of any registered Horde application.
     *
     * @param string $app The name of the application
     * @return string The registered name of the application
     */
    function getName($app = '')
    {
        return $this->applications[(empty($app) ? $this->getApp() : $app)]['name'];
    }

    /**
     * Query the path to the templates directory for any registered
     * Horde application.
     *
     * @param string $app The name of the application
     * @return string Fully qualified path to the templates directory of the application
     */
    function getTemplatePath($app = '')
    {
        return $this->applications[(empty($app) ? $this->getApp() : $app)]['templates'];
    }

    /**
     * Query the path to the graphics webpath for any registered
     * Horde application.
     *
     * @param string $app The name of the application
     * @return string URL pointing to the graphics directory of the application
     */
    function getGraphicsPath($app = '')
    {
        return $this->applications[(empty($app) ? $this->getApp() : $app)]['graphics'];
    }

    /**
     * Query the path to the webpath root for any registered
     * Horde application.
     *
     * @param optional string $app The name of the application
     * @return string URL pointing to the webroot of the application
     */
    function getWebRoot($app = '')
    {
        return $this->applications[(empty($app) ? $this->getApp() : $app)]['webroot'];
    }

    /**
     * Return the requested configuration parameter for the specified
     * application. If no application is specified, the value of
     * $this->getApp() (the current application) is used. However, if
     * the parameter is not present for that application, the
     * Horde-wide value is used instead. If that is not present, we
     * return null.
     *
     * @param          string $parameter The configuration value to retrieve.
     * @param optional string $app       The application to get the value for.
     *
     * @return string The requested parameter, or null if it is not set.
     */
    function getParam($parameter, $app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }

        if (isset($this->applications[$app][$parameter])) {
            $ret = $this->applications[$app][$parameter];
        } else {
            $ret = isset($this->applications['horde'][$parameter]) ?
                $this->applications['horde'][$parameter] : null;
        }
        if ($parameter == 'name') {
            return gettext($ret);
        } else {
            return $ret;
        }
    }

    /**
     * Query the initial page for an application - the webroot, if
     * there is no initial_page set, and the initial_page, if it is
     * set.
     *
     * @param optional string $app The name of the application.
     * @return string URL pointing to the inital page of the application.
     */
    function getInitialPage($app = null)
    {
        if (is_null($app)) {
            $app = $this->getApp();
        }
        if (!isset($this->applications[$app])) {
            Horde::fatal(new PEAR_Error(sprintf(_("'%s' is not configured in the Horde Registry."), $app)), __FILE__, __LINE__);
        }
        return $this->applications[$app]['webroot'] . '/' . (isset($this->applications[$app]['initial_page']) ? $this->applications[$app]['initial_page'] : '');
    }

    /**
     * Query the filesystem path to any registered Horde application.
     *
     * @param string $app The name of the application
     * @return string Fully qualified path to the root of the application
     */
    function getFileRoot($app = '')
    {
        return $this->applications[(empty($app) ? $this->getApp() : $app)]['fileroot'];
    }

    /**
     * Return the charset for the current language.
     *
     * @return string The character set that should be used with the
     * current locale settings.
     *
     * @deprecated since Horde 2.1
     * @see Lang::getCharset()
     */
    function getCharset()
    {
        return !empty($GLOBALS['nls']['charsets'][$GLOBALS['language']]) ? $GLOBALS['nls']['charsets'][$GLOBALS['language']] : $GLOBALS['nls']['defaults']['charset'];
    }

}
?>

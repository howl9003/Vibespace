<?php
/* $Horde: horde/lib/Maintenance.php,v 1.2.2.11 2003/02/12 23:07:21 jan Exp $
 *
 * Copyright 2001-2003 Michael Slusarz <slusarz@bigworm.colorado.edu>
 *
 * See the enclosed file COPYING for license information (LGPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/** @const MAINTENANCE_YEARLY Do task yearly (First login after/on January 1). */
define('MAINTENANCE_YEARLY', 1);

/** @const MAINTENANCE_MONTHLY Do task monthly (First login after/on first of month). */
define('MAINTENANCE_MONTHLY', 2);

/** @const MAINTENANCE_WEEKLY Do task weekly (First login after/on a Sunday). */
define('MAINTENANCE_WEEKLY', 3);

/** @const MAINTENANCE_DAILY Do task daily (First login of the day). */
define('MAINTENANCE_DAILY', 4);

/** @const MAINTENANCE_EVERY Do task every login. */
define('MAINTENANCE_EVERY', 5);


/* Intervals hash - used to build select tables in preferences menu. */
$intervals[MAINTENANCE_YEARLY]  = _("Yearly");
$intervals[MAINTENANCE_MONTHLY] = _("Monthly");
$intervals[MAINTENANCE_WEEKLY]  = _("Weekly");
$intervals[MAINTENANCE_DAILY]   = _("Daily");
$intervals[MAINTENANCE_EVERY]   = _("Every Login");
$GLOBALS['intervals'] = &$intervals;


/**
* The Maintenance:: class provides a set of methods for dealing with
* maintenance operations run upon login to Horde applications.
*
* @author  Michael Slusarz <slusarz@bigworm.colorado.edu>
* @version $Revision: 1.1.1.1 $
* @since   Horde 1.3.5
* @package horde
*/
class Maintenance {

    /**
     * Hash holding maintenance preference names.
     * Syntax:  PREFNAME => interval
     * Valid intervals are: MAINTENANCE_YEARLY, MAINTENANCE_MONTHLY,
     *                      MAINTENANCE_WEEKLY, MAINTENANCE_DAILY,
     *                      MAINTENANCE_EVERY
     * Operations will be run in the order they appear in the array -
     *   MAKE SURE FUNCTIONS ARE IN THE CORRECT ORDER!
     * Operations can appear more than once - they will only be run once per
     *   login though (the operation will run the first time it is seen in
     *   the array).
     *
     * This array should be filled in for each Horde module that extends
     *   the Maintenance class.
     *
     * @var array $maint_tasks
     */
    var $maint_tasks = array();

    /**
     * UNIX timestamp of the last login time for user.
     *
     * @var integer $last_login
     */
    var $last_login;

    /**
     * Has the confirmation page been shown yet?
     *
     * @var boolean $confirm_page
     */
    var $confirm_page = false;

    /**
     * Cache variable for the available maintenance function.
     *
     * @var array $available
     */
    var $available;

    /**
     * Array to store task module references.
     *
     * @var array $modules
     */
    var $modules = array();

    /**
     * The URL of the web page to run after the maintenance page.
     *
     * This value should be filled in for each Horde module that extends
     *   the Maintenance class.
     *
     * @var string $postpage
     */
    var $postpage;

    /**
     * The Horde module name.
     *
     * @var string $horde_module
     */
    var $horde_module;

    /**
     * Constructor
     *
     * @access public
     */
    function Maintenance()
    {
        global $prefs, $registry;

        /* Set the class variable $last_login. */
        $this->last_login = $prefs->getValue('last_login');
        if (!(int)$this->last_login) {
            $this->last_login = null;
        }

        /* Store whether the confirm maintenance page has been shown yet. */
        if (Horde::getFormData('confirm_maintenance') ||
            !$prefs->getValue('confirm_maintenance')) {
            $this->confirm_page = true;
        }

        /* Store the module name. */
        if (Horde::getFormData('module')) {
            $this->horde_module = Horde::getFormData('module');
        } else {
            $this->horde_module = $registry->getApp();
        }
    }

    /**
     * Do maintenance operations needed for this login.
     *
     * @access public
     */
    function runMaintenance()
    {
        global $prefs, $registry;

        /* Get the list of tasks available during this login.
           If no tasks present, we can return now. */
        $tasks = $this->availableMaintenance();
        if (empty($tasks)) {
            return;
        }

        /* Go to the confirmation page if it has not already been
           displayed AND there is at least one configuration option
           that is user changable. */
        if (!$this->confirm_page) {
            $confirmation = $this->confirmationMaintenance();
            if (!empty($confirmation)) {
                if (!empty($_SERVER['QUERY_STRING'])) {
                    $getvars = urlencode($_SERVER['QUERY_STRING']);
                } else {
                    $getvars = '';
                }
                header('Location: ' . Horde::url($registry->getParam('webroot', 'horde') . '/maintenance.php?module=' . $this->horde_module . '&getvars=' . $getvars, true));
                exit;
            }
        }

        /* Go through the task list and do any tasks that have been
           confirmed. */
        $confirmed = $this->confirmedMaintenance();
        if (empty($confirmed)) {
            return;
        }
        foreach ($confirmed as $value) {
            $mod = $this->loadModule($value);
            $mod->doMaintenance();
        }
    }

    /**
     * Determines which maintenance operations are available for this login.
     *
     * @access public
     *
     * @return array $tasks  Array of tasks that could be performed
     *                       during this login.
     */
    function availableMaintenance()
    {
        global $prefs;

        /* If we have cached results of available_maintenance(), we can return
           the cached results now. */
        if (isset($this->available)) {
            return $this->available;
        }

        $this->available = array();

        /* If last_login is empty (= 0), this is the first time the user
           has logged in.  We shouldn't do anything then. */
        if (empty($this->last_login)) {
            return $this->available;
        }

        /* Create time objects for today's date and last login date. */
        $last_date = getdate($this->last_login);
        $cur_date  = getdate();

        /* Go through each item in $maint_tasks and determine if we need to
           run it during this login. */
        foreach ($this->maint_tasks as $key => $val) {

            /* Skip item if it already appears in the tasks list. */
            if (in_array($key, $this->available)) {
                continue;
            }

            /* Determine the correct interval for the item. */
            $val = ($prefs->getValue($key . '_interval'))
              ? $prefs->getValue($key . '_interval')
              : $val;

            /* YEARLY_OPERATIONS */
            if ($val == MAINTENANCE_YEARLY && $cur_date['year'] > $last_date['year']) {
                $this->available[] = $key;
            }

            /* MONTHLY OPERATIONS */
            elseif ($val == MAINTENANCE_MONTHLY && ($cur_date['year'] > $last_date['year'] || $cur_date['mon'] > $last_date['mon'])) {
                $this->available[] = $key;
            }

            /* WEEKLY OPERATIONS */
            elseif ($val == MAINTENANCE_WEEKLY && ($cur_date['wday'] < $last_date['wday'] || time() - 604800 >  $this->last_login)) {
                $this->available[] = $key;
            }

            /* DAILY OPERATIONS */
            elseif ($val == MAINTENANCE_DAILY && ($cur_date['year'] > $last_date['year'] || $cur_date['yday'] > $last_date['yday'])) {
                $this->available[] = $key;
            }

            /* EVERY LOGIN OPERATIONS */
            elseif ($val == MAINTENANCE_EVERY) {
                $this->available[] = $key;
            }

        }

        return $this->available;
    }

    /**
     * Determines which operations have been confirmed and should actually
     * be run.
     * Confirmation can come from the user (through the confirmation page) or
     * the admin (locked configurations that are set to yes).
     *
     * @access public
     *
     * @return array $tasks  Array of tasks that will be performed
     *                       during this login.
     */
    function confirmedMaintenance()
    {
        global $prefs;

        $return_array = array();

        /* Get list of available tasks.
           Return if none available. */
        $available = $this->availableMaintenance();
        if (empty($available)) {
            return;
        }

        /* Determine which of these tasks have been confirmed. */
        foreach ($available as $value) {
            if (Horde::getFormData($value . '_confirm') ||
                (($prefs->isLocked($value) || !$prefs->getValue('confirm_maintenance'))
                 && $prefs->getValue($value))) {
                $return_array[] = $value;
            }
        }

        return $return_array;
    }

    /**
     * Returns the informational text message on what the operation is about
     * to do.  Also indicates whether the box should be checked by default or
     * not.  Operations that have been locked by the admin will return null.
     *
     * @access public
     *
     * @param string $pref  Name of the operation to get information for.
     *
     * @return array $info  1st element - Description of what the operation is
     *                                    about to do during this login.
     *                      2nd element - Whether the preference is set to on
     *                                    or not.
     */
    function infoMaintenance($pref)
    {
        global $prefs;

        /* If the preference has been locked by the admin, do not show
           the user. */
        if ($prefs->isLocked($pref)) {
            return;
        }

        $mod = $this->loadModule($pref);
        return array($mod->describeMaintenance(), $prefs->getValue($pref));
    }

    /**
     * Return list of tasks that need to be confirmed by the user.
     *
     * @access public
     *
     * @return array $tasks  An array of tasks that need to be confirmed by
     *                       user for this login.
     */
    function confirmationMaintenance()
    {
        global $prefs;

        $return_array = array();

        /* Get list of available tasks.
           Return if none available. */
        $available = $this->availableMaintenance();
        if (empty($available)) {
            return;
        }

        /* Go through list of tasks and determine which need to be confirmed
           by the user - namely those tasks that are active for this login
           and are not locked. */
        foreach ($available as $task) {
            if (!$prefs->isLocked($task)) {
                $return_array[] = $task;
            }
        }

        return $return_array;
    }

    /**
     * Export variable names to use for creating select tables in the
     * preferences menu.
     *
     * @access public
     *
     * @return array $var_names  An array of variable names to be imported
     *                           into the prefs.php namespace.
     */
    function exportIntervalPrefs()
    {
        global $prefs;

        $return_array = array();

        foreach (array_keys($this->maint_tasks) as $val) {
            if (!$prefs->isLocked($val . '_interval')) {
                $return_array[] = $val . '_interval_options';
            }
        }

        return $return_array;
    }

    /**
     * Load module (if not already loaded).
     *
     * @access private
     *
     * @param string $modname  Name of the module to load.
     *
     * @return scalar $module  A reference to the requested module.
     */
    function loadModule($modname)
    {
        global $registry;

        if (!isset($this->modules[$modname])) {
            @include_once $registry->getParam('fileroot', $this->horde_module) . '/lib/Maintenance/Task/' . $modname . '.php';
            $class = 'Maintenance_Task_' . $modname;
            if (class_exists($class)) {
                $this->modules[$modname] = new $class;
            } else {
                Horde::fatal(new PEAR_Error(sprintf(_("Could not open Maintenance_Task module %s"), $class)), __FILE__, __LINE__);
            }
        }

        return $this->modules[$modname];
    }

    /**
     * Get the URL of the form target for the module.
     *
     * @access public
     *
     * @return string A reference to the requested module's maintenance form
     * target.
     */
    function getTarget()
    {
        return Horde::url($GLOBALS['registry']->getParam('webroot', $this->horde_module) . '/' . $this->postpage);
    }

    /**
     * Return form vars for the module, based on the 'getvars' form
     * data. Returns either a GET-string starting with '?', or hidden
     * form elements for a POST form.
     *
     * @access public
     *
     * @param optional string $method  Either 'get' or 'post'. Defaults to
     * 'post'.
     *
     * @return string  The form data.
     */
    function getFormVars($method = 'post')
    {
        $return = '';
        $getvars = Horde::getFormData('getvars', '');
        if ($getvars) {
            if ($method == 'get') {
                $return = '?' . $getvars;
            } else {
                parse_str($getvars, $form_array);
                foreach ($form_array as $name => $value) {
                    $return .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />' . "\n";
                }
            }
        }

        return $return;
    }

}


/**
 * Abstract class to allow for modularization of specific maintenace tasks.
 *
 * For this explanation, the specific Horde application you want to create
 * maintenance actions for will be labeled HORDEAPP.
 *
 * To add a new maintenance task, you need to do the following:
 * [1] Add preference to "HORDEAPP/config/prefs.php" file.
 *     (The name of this preference will be referred to as PREFNAME)
 *     This preference should be of type 'checkbox' (i.e. 1 = on; 0 = off).
 *     [Optional:]  Add a preference in prefs.php of the name
 *                  'PREFNAME_interval' to allow the user to set the interval.
 *                  'default' value should be set to the values of the interval
 *                  constants above.
 *                  If this preference doesn't exist, the default interval
 *                  used will be the one that appears in $maint_tasks.
 * [2] Create a directory named "HORDEAPP/lib/Maintenance".
 * [3] Create a class entitled Maintenance_HORDEAPP that extends the
 *     class Maintenance.
 *     This class should contain only two items - the application specific
 *     definitions of $maint_tasks and $postpage (see above for description).
 *     Save this file as "HORDEAPP/lib/Maintenance/HORDEAPP.php".
 * [4] Create a directory titled "HORDEAPP/lib/Maintenance/Task".
 * [5] Create modules in HORDEAPP/lib/Maintenance/Task named 'PREFNAME.php'
 *     that extend the Maintenance_Task class.
 *     The class should be named Maintenance_Task_PREFNAME.
 *     The class should declare the following two methods:
 *       'doMaintenance' - This is the function that is run to do the
 *                         specified maintenance operation.
 *       'describeMaintenance' - This function sets the preference text
 *                               and text to be used on the confirmation
 *                               page.  Should return a description of what
 *                               your 'doMaintenance' function is about to do.
 *     Neither function requires any parameters passed in.
 */
class Maintenance_Task {

    function Maintenance_Task()
    {
    }

    function doMaintenance()
    {
    }

    function describeMaintenance()
    {
    }

}

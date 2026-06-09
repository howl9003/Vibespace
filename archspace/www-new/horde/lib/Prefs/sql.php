<?php
/*
 * $Horde: horde/lib/Prefs/sql.php,v 1.27.2.19 2003/06/04 01:01:13 marcus Exp $
 *
 * Copyright 1999-2003 Jon Parise <jon@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * Preferences storage implementation for PHP's PEAR database abstraction
 * layer.
 *
 * Required values for $params:
 *      'phptype'       The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *      'table'         The name of the preferences table in 'database'.
 *
 * Required by some database implementations:
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.
 *
 * The table structure for the preferences is as follows:
 *
 *  create table horde_prefs (
 *      pref_uid        varchar(255) not null,
 *      pref_scope      varchar(16) not null default '',
 *      pref_name       varchar(32) not null,
 *      pref_value      text null,
 *      primary key (pref_uid, pref_scope, pref_name)
 *  );
 *
 * @author  Jon Parise <jon@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.prefs
 */
class Prefs_sql extends Prefs {

    /** Hash containing connection parameters. */
    var $params = array();

    /** Handle for the current database connection.
        @var object DB $db */
    var $db;

    /** Boolean indicating whether or not we're connected to the SQL server. */
    var $_connected = false;

    /**
     * Constructs a new SQL preferences object.
     *
     * @param string $user      The user who owns these preferences.
     * @param string $password  The password associated with $user. (unused)
     * @param string $scope     The current preferences scope.
     * @param array  $params    A hash containing connection parameters.
     * @param boolean $caching  (optional) Should caching be used?
     */
    function Prefs_sql($user, $password = '', $scope = '', $params = array(),
                       $caching = false)
    {
        $this->user = $user;
        $this->scope = $scope;
        $this->params = $params;
        $this->caching = $caching;
    }

    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return mixed       True on success or a PEAR_Error object on failure.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->params)) {
                Horde::fatal(new PEAR_Error(_("No configuration information specified for SQL Preferences.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['phptype'])) {
                Horde::fatal(new PEAR_Error(_("Required 'phptype' not specified in preferences configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['hostspec'])) {
                Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in preferences configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['username'])) {
                Horde::fatal(new PEAR_Error(_("Required 'username' not specified in preferences configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['password'])) {
                Horde::fatal(new PEAR_Error(_("Required 'password' not specified in preferences configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['database'])) {
                Horde::fatal(new PEAR_Error(_("Required 'database' not specified in preferences configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->params['table'])) {
                Horde::fatal(new PEAR_Error(_("Required 'table' not specified in preferences configuration.")), __FILE__, __LINE__);
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->db = &DB::connect($this->params, true);
            if (DB::isError($this->db)) {
                Horde::fatal($this->db, __FILE__, __LINE__);
            }

            /* Enable the "portability" option. */
            $this->db->setOption('optimize', 'portability');

            $this->_connected = true;
        }

        return true;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return boolean     true on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->db->disconnect();
        }

        return true;
    }

    /**
     * Retrieve a value or set of values for a specified user.
     *
     * @since Horde 2.2
     *
     * @access public
     *
     * @param string $user            The user to retrieve prefs for.
     * @param mixed $retrieve         A string or array with the preferences
     *                                to retrieve.
     * @param optional string $scope  The preference scope to look in.
     *                                Defaults to horde.
     *
     * @return mixed  If a single value was requested, the value for that
     *                preference. Otherwise, a hash, indexed by pref names,
     *                with the requested values.
     */
    function getPref($user, $retrieve, $scope = 'horde')
    {
        /* Make sure we're connected. */
        $this->_connect();

        if ($scope != 'horde') {
            $scope = "pref_scope = " . $this->db->quote($scope) . " OR pref_scope = 'horde'";
        } else {
            $scope = "pref_scope = 'horde'";
        }

        if (is_array($retrieve)) {
            $where = '';
            foreach ($retrieve as $pref) {
                if (!empty($where)) {
                    $where .= ' OR ';
                }
                $where .= 'pref_name=' . $this->db->quote($pref);
            }
            return $this->db->getAssoc('SELECT pref_name, pref_value FROM ' . $this->_params['table'] . ' WHERE pref_uid=' . $this->db->quote($user) . ' AND (' . $where . ') AND (' . $scope . ')');
        } else {
            return $this->db->getOne('SELECT pref_value FROM ' . $this->_params['table'] . ' WHERE pref_uid=' . $this->db->quote($user) . ' AND pref_name=' . $this->db->quote($retrieve) . ' AND (' . $scope . ')');
        }
    }

    /**
     * Retrieves the requested set of preferences from the user's database
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
            $prefs = $this->listAll();
        }
        if (!is_array($prefs) || (count($prefs) == 0)) {
            return (new PEAR_Error(_("No preferences are available.")));
        }

        /* Attempt to pull the values from the session cache first. */
        if ($this->cacheLookup($prefs)) {
            return true;
        }

        /* Make sure we're connected. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'SELECT pref_scope, pref_name, pref_value FROM ';
        $query .= $this->params['table'] . ' ';
        $query .= 'where pref_uid = ' . $this->db->quote($this->user);
        $query .= ' and (pref_scope = ' . $this->db->quote($this->scope);
        $query .= " or pref_scope = 'horde') order by pref_scope";

        /* Execute the query. */
        $result = $this->db->query($query);

        if (isset($result) && !DB::isError($result)) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            if (DB::isError($row)) {
                Horde::logMessage('No preferences were retrieved.', __FILE__, __LINE__);
                return;
            }

            /* Set the requested values in the $this->prefs hash based
             * on the contents of the SQL result.
             *
             * Note that Prefs::setValue() can't be used here because
             * of the check for the "changeable" bit.  We want to
             * override that check when populating the $this->prefs
             * hash from the SQL server. */
            while ($row && !DB::isError($row)) {
                $name = trim($row['pref_name']);
                if (in_array($name, $prefs)) {
                    $this->prefs[$name]['val'] = $row['pref_value'];
                    $this->prefs[$name]['default'] = false;
                    $this->setDirty($name, false);
                }
                $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            }
            $result->free();

        } else {
            Horde::logMessage('No preferences were retrieved.', __FILE__, __LINE__);
            return;
        }

        /* Update the session cache. */
        $this->cacheUpdate();

        return true;
    }

    /**
     * Stores preferences to SQL server.
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
            $prefs = $this->listAll();
        }
        if (!is_array($prefs)) {
            return (new PEAR_Error(_("No preferences are available.")));
        }

        /* Check for any "dirty" preferences. */
        $dirty_prefs = array();
        foreach ($prefs as $pref) {
            if ($this->isDirty($pref)) {
                $dirty_prefs[] = $pref;
            }
        }

        /*
         * If no "dirty" preferences were found, there's no need to update
         * the SQL server.  Exit successfully.
         */
        if (count($dirty_prefs) == 0) {
            return true;
        }

        /* Make sure we're connected. */
        $this->_connect();

        /*
         * Loop through the "dirty" preferences.  If a row already exists for
         * this preference, attempt to update it.  Otherwise, insert a new row.
         */
        foreach ($dirty_prefs as $name) {
            $scope = $this->getScope($name);

            /* Does an entry already exist for this preference? */
            $query = 'select pref_value from ';
            $query .= $this->params['table'] . ' ';
            $query .= 'where pref_uid = ' . $this->db->quote($this->user);
            $query .= ' and pref_name = ' . $this->db->quote($name);
            $query .= ' and (pref_scope = ' . $this->db->quote($scope);
            $query .= " or pref_scope = 'horde')";

            /* Execute the query. */
            $result = $this->db->query($query);

            /* Return an error if the query fails. */
            if (!isset($result) || DB::isError($result)) {
                Horde::logMessage('Failed retrieving prefs for ' . $this->user, __FILE__, __LINE__, LOG_NOTICE);
                return (new PEAR_Error(_("Failed retrieving preferences.")));
            }

            /* Is there an existing row for this preference? */
            $row = $result->fetchRow();
            if ($row && !DB::isError($row)) {
                /* Update the existing row. */
                $query = 'update ' . $this->params['table'] . ' ';
                $query .= 'set pref_value = ' . $this->db->quote((string)$this->getValue($name));
                $query .= ' where pref_uid = ' . $this->db->quote($this->user);
                $query .= ' and pref_name = ' . $this->db->quote($name);
                $query .= ' and pref_scope = ' . $this->db->quote($scope);
                $result = $this->db->query($query);

                /* Return an error if the update fails. */
                if (PEAR::isError($result)) {
                    Horde::fatal($result, __FILE__, __LINE__);
                }
            } else {
                /* Insert a new row. */
                $query  = 'insert into ' . $this->params['table'] . ' ';
                $query .= '(pref_uid, pref_scope, pref_name, pref_value) values';
                $query .= '(' . $this->db->quote($this->user) . ', ';
                $query .= $this->db->quote($scope) . ', ' . $this->db->quote($name) . ', ';
                $query .= $this->db->quote((string)$this->getValue($name)) . ')';
                $result = $this->db->query($query);

                /* Return an error if the insert fails. */
                if (PEAR::isError($result)) {
                    Horde::fatal($result, __FILE__, __LINE__);
                }
            }

            /* Mark this preference as "clean" now. */
            $this->setDirty($name, false);
        }

        /* Update the session cache. */
        $this->cacheUpdate();

        return true;
    }

    /**
     * Perform cleanup operations.
     *
     * @param boolean  $all    (optional) Cleanup all Horde preferences.
     */
    function cleanup($all = false)
    {
        /* Close the database connection. */
        if (isset($this->db) && is_object($this->db)) {
            $this->db->disconnect();
        }

        parent::cleanup($all);
    }

}

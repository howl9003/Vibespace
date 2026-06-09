<?php
/*
 * $Horde: horde/lib/Auth/sql.php,v 1.21.2.7 2003/02/18 00:33:02 jan Exp $
 *
 * Copyright 1999-2003 Chuck Hagenbuch <chuck@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Auth_sql class provides a sql implementation of the Horde
 * authentication system.
 *
 * Required values for $params:
 *      'phptype'       The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *
 * Optional values:
 *      'table'         The name of the auth table in 'database'. Defaults to 'horde_users'.
 *
 * Required by some database implementations:
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.
 *
 * The table structure for the auth system is as follows:
 *
 *  create table horde_users (
 *      user_uid        varchar(255) not null,
 *      user_pass       varchar(255) not null,
 *      primary key (user_uid)
 *  );
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.auth
 */
class Auth_sql extends Auth {

    /** An array of capabilities, so that the driver can report which
        operations it supports and which it doesn't.
        @var array $capabilities */
    var $capabilities = array('add'         => true,
                              'update'      => true,
                              'remove'      => true,
                              'list'        => true,
                              'transparent' => false,
                              'loginscreen' => false);

    /**
     * Hash containing connection parameters.
     * @var array $_params
     */
    var $_params = array();

    /**
     * Handle for the current database connection.
     * @var object DB $_db
     */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     * @var boolean $connected
     */
    var $_connected = false;

    /**
     * Constructs a new SQL authentication object.
     *
     * @param array $params   A hash containing connection parameters.
     */
    function Auth_sql($params = array())
    {
        $this->_params = $params;
    }


    /**
     * Attempts to open a persistent connection to the SQL server.
     *
     * @return mixed True on success or a PEAR_Error object on failure.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->_params)) {
                Horde::fatal(new PEAR_Error(_("No configuration information specified for SQL authentication.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['phptype'])) {
                Horde::fatal(new PEAR_Error(_("Required 'phptype' not specified in authentication configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['hostspec'])) {
                Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in authentication configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['username'])) {
                Horde::fatal(new PEAR_Error(_("Required 'username' not specified in authentication configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['password'])) {
                Horde::fatal(new PEAR_Error(_("Required 'password' not specified in authentication configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['database'])) {
                Horde::fatal(new PEAR_Error(_("Required 'database' not specified in authentication configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['table'])) {
                $this->_params['table'] = 'horde_users';
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, true);
            if (DB::isError($this->_db)) {
                Horde::fatal(new PEAR_Error(_("Unable to connect to SQL server.")), __FILE__, __LINE__);
            }

            /* Enable the "portability" option. */
            $this->_db->setOption('optimize', 'portability');

            $this->_connected = true;
        }

        return true;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return boolean true on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_db->disconnect();
        }

        return true;
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userID       The userID to check.
     * @param array  $credentials  The credentials to use.
     *
     * @return boolean Whether or not the credentials are valid.
     */
    function authenticate($userID, $credentials)
    {
        if (Auth::checkAuth($userID)) {
            return true;
        }

        /* _connect() will die with Horde::fatal() upon failure. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'SELECT user_uid FROM ' . $this->_params['table'];
        $query .= ' WHERE user_uid = ' . $this->_db->quote($userID);
        $query .= ' AND user_pass = ' . $this->_db->quote(md5($credentials['password']));

        /* Execute the query. */
        $result = $this->_db->query($query);

        if (!DB::isError($result)) {
            $row = $result->fetchRow(DB_GETMODE_ASSOC);
            if (is_array($row)) {
                $result->free();
                Auth::setAuth($userID, $credentials);
                return true;
            } else {
                $result->free();
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userID       The userID to add.
     * @param array  $credentials  The credentials to add.
     *
     * @return mixed        True on success or a PEAR_Error object on failure.
     */
    function addUser($userID, $credentials)
    {
        /* _connect() will die with Horde::fatal() upon failure. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'INSERT INTO ' . $this->_params['table'] . ' (user_uid, user_pass) ';
        $query .= 'VALUES (' . $this->_db->quote($userID) . ', ' . $this->_db->quote(md5($credentials['password'])) . ')';

        /* Execute the query. */
        $result = $this->_db->query($query);

        if ($result !== DB_OK) {
            return (new PEAR_Error(_("Database query failed.")));
        }

        return true;
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
        /* _connect() will die with Horde::fatal() upon failure. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'UPDATE ' . $this->_params['table'] . ' SET user_uid = ' . $this->_db->quote($newID) . ', user_pass = ' . $this->_db->quote(md5($credentials['password']));
        $query .= 'WHERE user_uid = ' . $this->_db->quote($oldID);

        /* Execute the query. */
        $result = $this->_db->query($query);

        if ($result !== DB_OK) {
            return (new PEAR_Error(_("Database query failed.")));
        }

        return true;
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userID  The userID to delete.
     * @return boolean        Success or failure.
     */
    function removeUser($userID)
    {
        /* _connect() will die with Horde::fatal() upon failure. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'DELETE FROM ' . $this->_params['table'];
        $query .= ' WHERE user_uid = ' . $this->_db->quote($userID);

        /* Execute the query. */
        $result = $this->_db->query($query);

        if ($result !== DB_OK) {
            return (new PEAR_Error(_("Database query failed.")));
        }

        return true;
    }

    /**
     * List all users in the system.
     *
     * @return mixed   The array of userIDs, or false on failure/unsupported.
     */
    function listUsers()
    {
        /* _connect() will die with Horde::fatal() upon failure. */
        $this->_connect();

        /* Build the SQL query. */
        $query = 'SELECT user_uid from ' . $this->_params['table'];

        /* Execute the query. */
        $result = $this->_db->getAll($query, null, DB_FETCHMODE_ORDERED);

        /* Loop through and build return value. */
        $users = array();
        foreach ($result as $ar) {
            $users[] = $ar[0];
        }

        return $users;
    }

}
?>

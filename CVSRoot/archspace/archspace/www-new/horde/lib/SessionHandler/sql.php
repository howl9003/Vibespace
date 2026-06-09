<?php
/**
 * SessionHandler implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'phptype'       The database type (e.g. 'pgsql', 'mysql', etc.).
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *      'table'         The name of the sessiondata table in 'database'.</pre>
 *
 * Required by some database implementations:
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.
 *
 * Optional parameters:
 *      'persistent'     Use persistent DB connections? (boolean)
 *
 * The table structure can be created by the scripts/db/sessionhandler.sql
 * script.
 *
 * $Horde: horde/lib/SessionHandler/sql.php,v 1.9.2.3 2003/05/19 17:56:11 slusarz Exp $
 *
 * Copyright 2002-2003 Mike Cochrane <mike@graftonhall>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrame <mike@graftonhall.co.nz>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 2.2
 * @package horde.session
 */
class SessionHandler_sql extends SessionHandler {

    /** Hash containing connection parameters. */
    var $_params = array();

    /** Handle for the current database connection.
        @var object DB $_db */
    var $_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL
     * server. */
    var $_connected = false;

    /**
     * Constructs a new SQL SessionHandler object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function SessionHandler_sql($params = array())
    {
        $this->_params = $params;
    }

    function open($save_path, $session_name)
    {
        /* Connect to database */
        $this->_connect();
    }

    function close()
    {
        /* Disconnect from Database */
        $this->_disconnect();
    }

    function read($id)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        require_once HORDE_BASE . '/lib/SQL.php';

        /* Execute the query. */
        $result = Horde_SQL::readBlob($this->_db, $this->_params['table'], 'session_data',
                                      array('session_id' => $id));

        if (DB::isError($result)) {
            Horde::logMessage($result, __FILE__, __LINE__, LOG_ERR);
            return '';
        }

        return $result;
    }

    function write($id, $session_data)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('SELECT session_id FROM %s WHERE session_id = %s',
                         $this->_params['table'], $this->_db->quote($id));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by SessionHandler_sql::write(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->getOne($query);
        if (DB::isError($result)) {
            Horde::logMessage($result, __FILE__, __LINE__, LOG_ERR);
            return false;
        }

        require_once HORDE_BASE . '/lib/SQL.php';
        if ($result) {
            $result = Horde_SQL::updateBlob($this->_db, $this->_params['table'], 'session_data',
                                            $session_data, array('session_id' => $id),
                                            array('session_lastmodified' => time()));
        } else {
            $result = Horde_SQL::insertBlob($this->_db, $this->_params['table'], 'session_data',
                                            $session_data, array('session_id' => $id,
                                                                 'session_lastmodified' => time()));
        }

        if (DB::isError($result)) {
            Horde::logMessage($result, __FILE__, __LINE__, LOG_ERR);
            return false;
        }

        return true;
    }

    function destroy($id)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE session_id = %s',
                         $this->_params['table'], $this->_db->quote($id));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by SessionHandler_sql::destroy(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            Horde::logMessage($result, __FILE__, __LINE__, LOG_ERR);
            return false;
        }

        return true;
    }

    function gc($maxlifetime = 300) 
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE session_lastmodified < %s',
                         $this->_params['table'], $this->_db->quote(time() - $maxlifetime));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by SessionHandler_sql::gc(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($query);
        if (DB::isError($result)) {
            Horde::logMessage($result, __FILE__, __LINE__, LOG_ERR);
            return false;
        }

        return true;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean    True on success; exits (Horde::fatal()) on error.
     */
    function _connect()
    {
        if (!$this->_connected) {
            if (!is_array($this->_params)) {
                Horde::fatal(new PEAR_Error(_("No configuration information specified for SQL SessionHandler.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['phptype'])) {
                Horde::fatal(new PEAR_Error(_("Required 'phptype' not specified in SessionHandler configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['hostspec'])) {
                Horde::fatal(new PEAR_Error(_("Required 'hostspec' not specified in SessionHandler configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['username'])) {
                Horde::fatal(new PEAR_Error(_("Required 'username' not specified in SessionHandler configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['password'])) {
                Horde::fatal(new PEAR_Error(_("Required 'password' not specified in SessionHandler configuration.")), __FILE__, __LINE__);
            }
            if (!isset($this->_params['database'])) {
                Horde::fatal(new PEAR_Error(_("Required 'database' not specified in SessionHandler configuration.")), __FILE__, __LINE__);
            }
            if (!array_key_exists('table', $this->_params)) {
                $this->_params['table'] = 'horde_sessionhandler';
            }

            /* Use persistent connections? */
            if (empty($this->_params['persistent'])) {
                $persistent = false;
            } else {
                $persistent = true;
            }

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params, $persistent);
            if (DB::isError($this->_db)) {
                Horde::fatal($this->_db, __FILE__, __LINE__);
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
     * @return boolean     true on success, false on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_db->disconnect();
        }

        return true;
    }

}

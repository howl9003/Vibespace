<?php

require_once(HORDE_BASE . '/lib/SessionHandler/sql.php');

/**
 * SessionHandler implementation for PHP's PEAR database abstraction layer.
 *
 * Required values for $params:<pre>
 *      'hostspec'      The hostname of the database server.
 *      'protocol'      The communication protocol ('tcp', 'unix', etc.).
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *      'table'         The name of the sessiondata table in 'database'.</pre>
 *
 * The table structure can be created by the scripts/db/sessionhandler.sql
 * script.
 *
 * $Horde: horde/lib/SessionHandler/sapdb.php,v 1.7.2.2 2003/02/03 10:54:47 jan Exp $
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
class SessionHandler_sapdb extends SessionHandler_sql {

    /** Hash containing connection parameters. */
    var $_params = array();

    /** Handle for the current database connection.
        @var object DB $db */
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
    function SessionHandler_sapdb($params = array())
    {
        $this->_params = $params;
        $this->_params['phptype'] = 'odbc';
    }

    function read($id)
    {
        /* Make sure we have a valid database connection. */
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('SELECT session_data FROM %s WHERE session_id = %s',
                         $this->_params['table'],
                         $this->_db->quote($id));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('SQL Query by SessionHandler_sql::read(): query = "%s"', $query),
                          __FILE__, __LINE__, LOG_DEBUG);

        /* Execute the query */
        $result = odbc_exec($this->_db->connection, $query);
        odbc_longreadlen($result, 1024*1024);

        /* Fetch the value */
        odbc_fetch_row($result, 0);
        $data = odbc_result($result, 'session_data');

        /* Clean up */
        odbc_free_result($result);

        return $data;
    }

}

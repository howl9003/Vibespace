<?php
/*
 * $Horde: horde/lib/Token/sql.php,v 1.4.2.6 2003/01/03 12:48:43 jan Exp $
 *
 * Copyright 1999-2003 Max Kalika <max@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * Token tracking implementation for PHP's PEAR database abstraction
 * layer.
 *
 * Required values for $params:
 *      'phptype'       The database type (ie. 'pgsql', 'mysql, etc.).
 *      'hostspec'      The hostname of the database server.
 *      'username'      The username with which to connect to the database.
 *      'password'      The password associated with 'username'.
 *      'database'      The name of the database.
 *      'table'         The name of the connections table in 'database'.
 *
 * Required by some database implementations:
 *      'options'       Additional options to pass to the database.
 *      'tty'           The TTY on which to connect to the database.
 *      'port'          The port on which to connect to the database.
 *
 * Optional value for $params:
 *      'timeout'       The period (in seconds) after which an id is purged.
 *
 * The table structure for the connections is as follows:
 *
 *  create table tokens (
 *      token_addr  varchar(8)  not null,
 *      token_id    varchar(32) not null,
 *      token_ts    int(14)     not null,
 *      primary key (token_addr, token_id)
 *  );
 *
 * @author  Max Kalika <max@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.token
 */
class Token_sql extends Token {

    /** Handle for the current database connection. */
    var $db = '';

    /** Boolean indicating whether or not we're connected to the SQL server. */
    var $connected = false;

    /**
     * Constructs a new SQL connection object.
     *
     * @param optional array $params   A hash containing connection parameters.
     */
    function Token_sql($params = array())
    {
        $this->params = $params;

        /* Set timeout to 24 hours if not specified. */
        if (!isset($this->params['timeout'])) {
            $this->params['timeout'] = 86400;
        }
    }

    /**
     * Opens a connection to the SQL server.
     *
     * @return bool         TOKEN_OK on success, TOKEN_ERROR_* on failure.
     */
    function connect()
    {
        if (!$this->connected) {
            if (!is_array($this->params)) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['phptype'])) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['hostspec'])) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['username'])) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['password'])) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['database'])) return TOKEN_ERROR_PARAMS;
            if (!isset($this->params['table'])) return TOKEN_ERROR_PARAMS;

            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->db = &DB::connect($this->params, true);
            if (DB::isError($this->db)) {
                return TOKEN_ERROR_CONNECT;
            }

            /* Enable the "portability" option. */
            $this->db->setOption('optimize', 'portability');

            $this->connected = true;
        }

        return TOKEN_OK;
    }

    /**
     * Disconnect from the SQL server and clean up the connection.
     *
     * @return bool         true on success, false on failure.
     */
    function disconnect()
    {
        if ($this->connected) {
            $this->connected = false;
            return $this->db->disconnect();
        }

        return true;
    }

    /**
     * Deletes all expired connection id's from the SQL server.
     *
     * @return bool         True on success, a PEAR_Error object on failure.
     */
    function purge()
    {
        /* If we're not already connected, invoke the connect(). */
        if (!$this->connected) {
            if ($this->connect() != TOKEN_OK) return TOKEN_ERROR_CONNECT;
        }

        /* Build SQL query. */
        $query = 'delete from ' . $this->params['table'] . ' where ';
        $query .= 'token_ts < ' . (time() - $this->params['timeout']);

        $result = $this->db->query($query, $this->db);

        /* Return an error if the update fails, too. */
        if ($result !== DB_OK) return TOKEN_ERROR;

        return TOKEN_OK;
    }

    function exists($connID)
    {
        /* If we're not already connected, invoke the connect(). */
        if (!$this->connected) {
            if ($this->connect() != TOKEN_OK) return TOKEN_ERROR_CONNECT;
        }

        /* Build SQL query. */
        $query  = 'SELECT token_id FROM ' . $this->params['table'];
        $query .= ' WHERE token_addr = ' . $this->db->quote($this->hexRemoteAddr());
        $query .= ' AND token_id = ' . $this->db->quote($connID);

        $result = $this->db->query($query);

        if (isset($result) && !DB::isError($result)) {
            $row = $result->fetchRow();
        }

        if (empty($row) || DB::isError($row)) {
            return false;
        }
        return true;
    }

    function add($connID)
    {
        /* If we're not already connected, invoke the connect(). */
        if (!$this->connected) {
            if ($this->connect() != TOKEN_OK) return TOKEN_ERROR_CONNECT;
        }

        /* Build SQL query. */
        $query  = 'INSERT INTO ' . $this->params['table'];
        $query .= ' VALUES (' . $this->db->quote($this->hexRemoteAddr());
        $query .= ', ' . $this->db->quote($connID) . ', ' . time() . ')';

        $result = $this->db->query($query);

        /* Return an error if the update fails, too. */
        if ($result !== DB_OK) return TOKEN_ERROR;

        return TOKEN_OK;
    }
}
?>

<?php
/*
 * $Horde: horde/lib/Token/file.php,v 1.4.2.3 2003/01/03 12:48:43 jan Exp $
 *
 * Copyright 1999-2003 Max Kalika <max@horde.org>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * Token tracking implementation for stub files.
 *
 * Optional values for $params:
 *      'stub_dir'      The directory where to keep connection stub files.
 *      'timeout'       The period (in seconds) after which an id is purged.
 *
 * @author  Max Kalika <max@horde.org>
 * @version $Revision: 1.1.1.1 $
 * @since   Horde 1.3
 * @package horde.token
 */
class Token_file extends Token {

    /** Handle for the open file descriptor. */
    var $fd = '';

    /** Boolean indicating whether or not we have an open file descriptor. */
    var $connected = false;

    /**
     * Create a new file based tracking storage container.
     *
     * @param optional array $params   A hash containing storage parameters.
     */
    function Token_file($params = array())
    {
        $this->params = $params;

        /* Choose the directory to save the stub files. */
        if (!isset($this->params['stub_dir'])) {
            if (ini_get('upload_tmp_dir')) {
                $this->params['stub_dir'] = ini_get('upload_tmp_dir');
            }
            elseif (getenv('TMPDIR')) {
                $this->params['stub_dir'] = getenv('TMPDIR');
            }
            else {
                $this->params['stub_dir'] = '/tmp';
            }
        }

        /* Set timeout to 24 hours if not specified. */
        if (!isset($this->params['timeout'])) {
            $this->params['timeout'] = 86400;
        }
    }

    /**
     * Opens a file descriptor to a new or existing file.
     *
     * @return bool         TOKEN_OK on success, TOKEN_ERROR_* on failure.
     */
    function connect()
    {
        if (!$this->connected) {

            /*
             * Open a file descriptor to the connection stub file.
             */
            $this->fd = fopen($this->params['stub_dir'] . "/conn_" . $this->hexRemoteAddr(), "a");
            if (!$this->fd) {
                return TOKEN_ERROR_CONNECT;
            }

            $this->connected = true;
        }

        return TOKEN_OK;
    }

    /**
     * Closes the file descriptor.
     *
     * @return bool         true on success, false on failure.
     */
    function disconnect()
    {
        if ($this->connected) {
            $this->connected = false;
            return fclose($this->fd);
        }

        return true;
    }

    /**
     * Deletes all expired connection id's from the SQL server.
     *
     * @return bool         TOKEN_OK on success, TOKEN_ERROR_* on failure.
     */
    function purge()
    {
        /* If we're already connected, disconnect as we will be unlinking files. */
        if ($this->connected) {
            if ($this->disconnect()) return TOKEN_ERROR_CONNECT;
        }

        /* Build stub file list. */
        if (!$dirFD = opendir($this->params['stub_dir'])) {
            return TOKEN_ERROR_CONNECT;
        }

        /* Find expired stub files */
        while (($dirEntry = readdir($dirFD)) != '') {
            if (preg_match('|^conn_\w{8}$|', $dirEntry) && (time() - filemtime($this->params['stub_dir'] . "/" . $dirEntry) >= $this->params['timeout'])) {
                if (!unlink($this->params['stub_dir'] . "/" . $dirEntry)) {
                    return TOKEN_ERROR;
                }
            }
        }

        closedir($dirFD);

        return TOKEN_OK;
    }

    function exists($connID)
    {
        /* If we're already connected, disconnect as we will be reading one file. */
        if ($this->connected) {
            if ($this->disconnect()) return TOKEN_ERROR_CONNECT;
        }

        /* Find already used IDs */
        $fileContents = @file($this->params['stub_dir'] . '/conn_' . $this->hexRemoteAddr());
        if ($fileContents) {
            for ($i = 0; $i < count($fileContents); $i++) {
                if (chop($fileContents[$i]) == $connID) {
                    return true;
                }
            }
        }

        return false;
    }

    function add($connID)
    {
        /* If we're not already connected, invoke the connect(). */
        if (!$this->connected) {
            if ($this->connect() != TOKEN_OK) return TOKEN_ERROR_CONNECT;
        }

        /* Write the entry. */
        fwrite($this->fd, "$connID\n");

        /* Return an error if the update fails, too. */
        if (!$this->disconnect()) return TOKEN_ERROR;

        return TOKEN_OK;
    }

}
?>
